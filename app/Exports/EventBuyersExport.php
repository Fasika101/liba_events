<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventBuyersExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function __construct(
        protected $event,
        protected $tickets
    ) {}

    public function collection()
    {
        return $this->tickets->map(function ($t, $i) {
            return [
                $i + 1,
                $t->buyer_name,
                $t->buyer_phone ?? '',
                $t->buyer_email ?? '',
                $t->buyer_address ?? '',
                $t->buyer_occupation ?? '',
                $t->yeneshaa_abat ? 'Yes' : 'No',
                $t->ticket_code,
                $t->agent->name ?? '',
                $t->sold_at ? $t->sold_at->format('Y-m-d H:i') : '',
                $t->price_paid,
                $t->currency,
            ];
        });
    }

    public function headings(): array
    {
        return [
            '#',
            'Buyer Name',
            'Phone',
            'Email',
            'Address',
            'Occupation',
            'Yeneshaa Abat',
            'Ticket Code',
            'Sold By',
            'Date Sold',
            'Amount Paid',
            'Currency',
        ];
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
            'E' => 26,
            'F' => 16,
            'G' => 14,
            'H' => 18,
            'I' => 18,
            'J' => 16,
            'K' => 12,
            'L' => 10,
        ];
    }
}
