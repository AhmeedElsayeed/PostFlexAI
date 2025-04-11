<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Create a new subscription for a team.
     *
     * @param Team $team
     * @param Plan $plan
     * @param string $billingCycle
     * @param bool $isAutoRenew
     * @param string|null $paymentMethod
     * @return Subscription
     */
    public function createSubscription(
        Team $team,
        Plan $plan,
        string $billingCycle = 'monthly',
        bool $isAutoRenew = false,
        ?string $paymentMethod = null
    ): Subscription {
        // Check if team already has an active subscription
        $existingSubscription = $team->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->first();

        if ($existingSubscription) {
            throw new \Exception('Team already has an active subscription');
        }

        // Calculate subscription dates
        $startDate = now();
        $trialEndDate = $startDate->copy()->addDays(14); // 14-day trial
        $endDate = $this->calculateEndDate($startDate, $billingCycle);

        // Create the subscription
        $subscription = new Subscription([
            'team_id' => $team->id,
            'plan_id' => $plan->id,
            'status' => 'trial',
            'started_at' => $startDate,
            'ends_at' => $endDate,
            'renewal_date' => $endDate,
            'trial_ends_at' => $trialEndDate,
            'payment_method' => $paymentMethod,
            'is_auto_renew' => $isAutoRenew
        ]);

        $subscription->save();

        // Create initial invoice
        $this->createInvoice($subscription, 0);

        return $subscription;
    }

    /**
     * Activate a subscription after trial or payment.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function activateSubscription(Subscription $subscription): bool
    {
        if ($subscription->isActive()) {
            return true;
        }

        $subscription->status = 'active';
        $subscription->started_at = now();
        $subscription->ends_at = $this->calculateEndDate(now(), $subscription->plan->billing_cycle);
        $subscription->renewal_date = $subscription->ends_at;

        return $subscription->save();
    }

    /**
     * Cancel a subscription.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function cancelSubscription(Subscription $subscription): bool
    {
        if ($subscription->isCanceled() || $subscription->isExpired()) {
            return true;
        }

        $subscription->status = 'canceled';
        $subscription->is_auto_renew = false;

        return $subscription->save();
    }

    /**
     * Renew a subscription.
     *
     * @param Subscription $subscription
     * @return bool
     */
    public function renewSubscription(Subscription $subscription): bool
    {
        if (!$subscription->isActive() && !$subscription->isCanceled()) {
            return false;
        }

        $subscription->status = 'active';
        $subscription->started_at = now();
        $subscription->ends_at = $this->calculateEndDate(now(), $subscription->plan->billing_cycle);
        $subscription->renewal_date = $subscription->ends_at;

        return $subscription->save();
    }

    /**
     * Upgrade a subscription to a new plan.
     *
     * @param Subscription $subscription
     * @param Plan $newPlan
     * @return bool
     */
    public function upgradeSubscription(Subscription $subscription, Plan $newPlan): bool
    {
        if ($subscription->plan_id === $newPlan->id) {
            return true;
        }

        // Calculate prorated amount if needed
        $proratedAmount = $this->calculateProratedAmount($subscription, $newPlan);

        // Create invoice for the upgrade if there's a charge
        if ($proratedAmount > 0) {
            $this->createInvoice($subscription, $proratedAmount);
        }

        // Update the subscription
        $subscription->plan_id = $newPlan->id;
        $subscription->ends_at = $this->calculateEndDate(now(), $newPlan->billing_cycle);
        $subscription->renewal_date = $subscription->ends_at;

        return $subscription->save();
    }

    /**
     * Create an invoice for a subscription.
     *
     * @param Subscription $subscription
     * @param float $amount
     * @return Invoice
     */
    public function createInvoice(Subscription $subscription, float $amount): Invoice
    {
        $invoice = new Invoice([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => $amount > 0 ? 'unpaid' : 'paid',
            'issued_at' => now(),
            'paid_at' => $amount > 0 ? null : now(),
            'payment_reference' => $amount > 0 ? null : 'system_generated'
        ]);

        $invoice->save();

        return $invoice;
    }

    /**
     * Process auto-renewals for subscriptions.
     *
     * @return int Number of subscriptions renewed
     */
    public function processAutoRenewals(): int
    {
        $renewedCount = 0;

        $subscriptions = Subscription::autoRenewing()
            ->where('renewal_date', '<=', now()->addDays(7))
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                // Create invoice for renewal
                $this->createInvoice($subscription, $subscription->plan->price);

                // Renew the subscription
                $this->renewSubscription($subscription);
                $renewedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to auto-renew subscription: ' . $e->getMessage(), [
                    'subscription_id' => $subscription->id,
                    'team_id' => $subscription->team_id
                ]);
            }
        }

        return $renewedCount;
    }

    /**
     * Check and update expired subscriptions.
     *
     * @return int Number of subscriptions updated
     */
    public function checkExpiredSubscriptions(): int
    {
        $updatedCount = 0;

        $subscriptions = Subscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $subscription->status = 'expired';
            $subscription->save();
            $updatedCount++;
        }

        return $updatedCount;
    }

    /**
     * Calculate the end date for a subscription based on billing cycle.
     *
     * @param Carbon $startDate
     * @param string $billingCycle
     * @return Carbon
     */
    private function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        if ($billingCycle === 'monthly') {
            return $startDate->copy()->addMonth();
        }

        return $startDate->copy()->addYear();
    }

    /**
     * Calculate prorated amount for upgrading a subscription.
     *
     * @param Subscription $subscription
     * @param Plan $newPlan
     * @return float
     */
    private function calculateProratedAmount(Subscription $subscription, Plan $newPlan): float
    {
        // If upgrading from trial, no charge
        if ($subscription->isTrial()) {
            return 0;
        }

        // Calculate remaining days in current subscription
        $remainingDays = $subscription->getDaysRemaining();
        $totalDays = $subscription->started_at->diffInDays($subscription->ends_at);

        // Calculate prorated amount for remaining days
        $proratedAmount = ($subscription->plan->price / $totalDays) * $remainingDays;

        // Calculate cost of new plan for remaining days
        $newPlanAmount = ($newPlan->price / $totalDays) * $remainingDays;

        // Return the difference (could be negative if downgrading)
        return max(0, $newPlanAmount - $proratedAmount);
    }

    /**
     * Get subscription statistics.
     *
     * @return array
     */
    public function getSubscriptionStats(): array
    {
        $stats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::active()->count(),
            'trial_subscriptions' => Subscription::trial()->count(),
            'canceled_subscriptions' => Subscription::canceled()->count(),
            'expired_subscriptions' => Subscription::expired()->count(),
            'auto_renewing' => Subscription::autoRenewing()->count(),
            'monthly_revenue' => Invoice::paid()
                ->paidInLastDays(30)
                ->sum('amount'),
            'yearly_revenue' => Invoice::paid()
                ->paidInLastDays(365)
                ->sum('amount'),
            'unpaid_invoices' => Invoice::unpaid()->count(),
            'unpaid_amount' => Invoice::unpaid()->sum('amount')
        ];

        return $stats;
    }
} 