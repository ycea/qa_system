<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MetricsByStatusSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
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
            $row['status'],
            $row['count'],
            $row['avg'],
        ], $this->rows);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Метрика', 'Статус', 'Кол-во значений', 'Среднее'];
    }

    public function title(): string
    {
        return 'По статусам';
    }
}
