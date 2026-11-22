<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MetricsSummarySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return array_map(fn (array $row) => [
            $row['metric'],
            $row['type'],
            $row['count'],
            $row['avg'],
            $row['min'],
            $row['max'],
            $row['sum'],
            $row['distribution'],
        ], $this->rows);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Метрика',
            'Тип',
            'Кол-во значений',
            'Среднее',
            'Мин',
            'Макс',
            'Сумма',
            'Распределение',
        ];
    }

    public function title(): string
    {
        return 'Сводка';
    }
}
