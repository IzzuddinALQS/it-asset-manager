<?php

namespace App\Http\Controllers\Reports;

use App\Actions\Reports\GenerateReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportFilterRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams the same report the page shows as CSV (UTF-8 with BOM so Excel reads it correctly).
 */
class ExportReportController extends Controller
{
    public function __invoke(ReportFilterRequest $request, GenerateReport $generateReport): StreamedResponse
    {
        $type = $request->reportType();
        $from = $request->from();
        $to = $request->to();
        $report = $generateReport->handle($type, $from, $to);

        $period = $from === null && $to === null
            ? ''
            : '_'.($from?->format('Ymd') ?? 'awal').'-'.($to?->format('Ymd') ?? 'kini');
        $filename = "laporan-{$type->value}{$period}.csv";

        return response()->streamDownload(function () use ($report): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            $keys = array_column($report['columns'], 'key');
            fputcsv($output, array_column($report['columns'], 'label'), escape: '');

            foreach ([...$report['rows'], ...($report['totals'] === null ? [] : [$report['totals']])] as $row) {
                fputcsv($output, array_map(fn (string $key) => $row[$key] ?? '', $keys), escape: '');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
