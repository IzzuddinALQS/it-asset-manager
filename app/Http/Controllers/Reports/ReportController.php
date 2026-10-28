<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\GenerateReport;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportFilterRequest;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, GenerateReport $generateReport): Response
    {
        $type = $request->reportType();
        $from = $request->from();
        $to = $request->to();

        return Inertia::render('Reports/Index', [
            'filters' => [
                'report' => $type->value,
                'from' => $from?->toDateString() ?? '',
                'to' => $to?->toDateString() ?? '',
            ],
            'reports' => array_map(
                fn (ReportType $report): array => [
                    'value' => $report->value,
                    'label' => $report->label(),
                    'date_label' => $report->dateLabel(),
                ],
                ReportType::cases(),
            ),
            'report' => $generateReport->handle($type, $from, $to),
        ]);
    }
}
