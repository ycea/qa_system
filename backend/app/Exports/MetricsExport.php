<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithProperties;

class MetricsExport implements Export, WithMultipleSheets, WithProperties
{
    /**
     * @param  array{summary: array<int, array<string, mixed>>, by_status: array<int, array<string, mixed>>}  $report
     */
    public function __construct(private readonly array $report) {}

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new MetricsSummarySheet($this->report['summary']),
            new MetricsByStatusSheet($this->report['by_status']),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function properties(): array
    {
        return [
            'creator' => 'QMS',
            'title' => 'Метрики проекта',
            'description' => 'Сформировано: '.now()->format('d.m.Y H:i'),
        ];
    }
}
