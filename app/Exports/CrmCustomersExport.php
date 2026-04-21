<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CrmCustomersExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        protected $customers
    ) {}

    public function collection()
    {
        return $this->customers->map(function ($c, $i) {
            $companyName = $c->event?->company?->name ?? '';

            return [
                $i + 1,
                $c->buyer_name,
                $c->buyer_phone ?? '',
                $c->buyer_email ?? '',
                $c->buyer_address ?? '',
                $c->buyer_occupation ?? '',
                $c->yeneshaa_abat ? 'Yes' : 'No',
                $companyName,
                $c->event->title ?? '',
                $c->sold_at ? $c->sold_at->format('Y-m-d') : '',
            ];
        });
    }

    public function headings(): array
    {
        return ['#', 'Name', 'Phone', 'Email', 'Address', 'Occupation', 'Yeneshaa Abat', 'Company', 'Last Event', 'Last Purchase'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F6EF7'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 22,
            'C' => 16,
            'D' => 28,
            'E' => 28,
            'F' => 18,
            'G' => 14,
            'H' => 22,
            'I' => 25,
            'J' => 14,
        ];
    }
}
