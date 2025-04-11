<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;

/**
 * @OA\Tag(
 *     name="Reports",
 *     description="API Endpoints for report generation and management"
 * )
 */
class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * @OA\Post(
     *     path="/api/reports/generate",
     *     summary="Generate a new report",
     *     tags={"Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", description="Report type"),
     *             @OA\Property(property="date_range", type="object"),
     *             @OA\Property(property="metrics", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="format", type="string", enum={"pdf", "excel", "csv"})
     *         )
     *     ),
     *     @OA\Response(response="200", description="Report generated successfully")
     * )
     */
    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'date_range' => 'required|array',
            'date_range.start' => 'required|date',
            'date_range.end' => 'required|date|after:date_range.start',
            'metrics' => 'required|array',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        $report = $this->reportService->generateReport($request->all());
        return response()->json($report);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/scheduled",
     *     summary="Get scheduled reports",
     *     tags={"Reports"},
     *     @OA\Response(response="200", description="List of scheduled reports")
     * )
     */
    public function getScheduledReports()
    {
        $reports = $this->reportService->getScheduledReports();
        return response()->json($reports);
    }

    /**
     * @OA\Get(
     *     path="/api/reports/{id}/download",
     *     summary="Download a report",
     *     tags={"Reports"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Report ID"),
     *     @OA\Response(response="200", description="Report file")
     * )
     */
    public function download($id)
    {
        return $this->reportService->downloadReport($id);
    }

    /**
     * @OA\Post(
     *     path="/api/reports/schedule",
     *     summary="Schedule a report",
     *     tags={"Reports"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="schedule", type="string"),
     *             @OA\Property(property="recipients", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="format", type="string")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Report scheduled successfully")
     * )
     */
    public function schedule(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'schedule' => 'required|string',
            'recipients' => 'required|array',
            'recipients.*' => 'email',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        $scheduled = $this->reportService->scheduleReport($request->all());
        return response()->json($scheduled);
    }
} 