<?php

namespace App\Http\Controllers;

use App\Models\SmartReplyTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SmartReplyTemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SmartReplyTemplate::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('keywords', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('success_rate', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(10);

        return response()->json($templates);
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), SmartReplyTemplate::rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template = SmartReplyTemplate::create($request->all());

        return response()->json($template, 201);
    }

    /**
     * Display the specified template.
     */
    public function show(SmartReplyTemplate $template): JsonResponse
    {
        return response()->json($template);
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, SmartReplyTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), SmartReplyTemplate::rules());

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update($request->all());

        return response()->json($template);
    }

    /**
     * Remove the specified template.
     */
    public function destroy(SmartReplyTemplate $template): JsonResponse
    {
        $template->delete();

        return response()->json(null, 204);
    }

    /**
     * Get template categories.
     */
    public function categories(): JsonResponse
    {
        $categories = SmartReplyTemplate::distinct()
            ->pluck('category')
            ->values();

        return response()->json($categories);
    }

    /**
     * Update template success rate.
     */
    public function updateSuccessRate(Request $request, SmartReplyTemplate $template): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'success_rate' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $template->update(['success_rate' => $request->success_rate]);

        return response()->json($template);
    }
} 