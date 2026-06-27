<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class EnterpriseReportExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $headings;
    protected $collection;

    public function __construct(array $headings, array $rows)
    {
        $this->headings = $headings;
        $this->collection = collect($rows);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function collection()
    {
        return $this->collection;
    }
}
