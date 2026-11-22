<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MetricsExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(private readonly array $rows) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Метрика', 'Тип', 'Кол-во значений', 'Показатель'];
    }
}
