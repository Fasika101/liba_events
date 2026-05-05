<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EventBuyersExport implements FromArray, WithColumnWidths, WithEvents, WithTitle
{
    /** All supported columns in display order */
    public const ALL_COLUMNS = [
        'number'           => '#',
        'buyer_name'       => 'Buyer Name',
        'buyer_phone'      => 'Phone',
        'buyer_email'      => 'Email',
        'buyer_address'    => 'Address',
        'buyer_occupation' => 'Occupation',
        'yeneshaa_abat'    => 'Yeneshaa Abat',
        'ticket_code'      => 'Ticket Code',
        'sold_by'          => 'Sold By',
        'date_sold'        => 'Date Sold',
        'amount_paid'      => 'Amount Paid',
        'currency'         => 'Currency',
    ];

    /** Columns actually included in this export (ordered subset of ALL_COLUMNS) */
    protected array $columns;

    public function __construct(
        protected $event,
        protected $tickets,
        array $columns = []
    ) {
        // Filter to only valid keys, preserving ALL_COLUMNS order
        $requested = array_flip(count($columns) > 0 ? $columns : array_keys(self::ALL_COLUMNS));
        $this->columns = array_filter(
            self::ALL_COLUMNS,
            fn ($key) => isset($requested[$key]),
            ARRAY_FILTER_USE_KEY
        );

        // Fallback: if nothing valid was selected, export everything
        if (empty($this->columns)) {
            $this->columns = self::ALL_COLUMNS;
        }
    }

    public function title(): string
    {
        return 'Buyers';
    }

    public function array(): array
    {
        $cols     = array_keys($this->columns);
        $colCount = count($cols);
        $pad      = array_fill(0, $colCount - 1, '');

        $rows = [];

        // Row 1 – Company name
        $rows[] = array_merge([$this->event->company->name ?? ''], $pad);

        // Row 2 – Event title
        $rows[] = array_merge([$this->event->title ?? ''], $pad);

        // Row 3 – Description
        $rows[] = array_merge([$this->event->description ?? ''], $pad);

        // Row 4 – Summary stats
        $date    = $this->event->start_at ? $this->event->start_at->format('d M Y') : '—';
        $count   = $this->tickets->count();
        $revenue = number_format((float) $this->tickets->sum('price_paid'), 2)
                   . ' ' . ($this->event->currency ?? '');

        if ($colCount >= 3) {
            // Split into 3 roughly equal sections
            $third  = (int) floor($colCount / 3);
            $row4   = array_fill(0, $colCount, '');
            $row4[0]          = 'Event Date:  ' . $date;
            $row4[$third]     = 'Tickets Sold:  ' . $count;
            $row4[$third * 2] = 'Total Revenue:  ' . $revenue;
        } else {
            $row4    = array_fill(0, $colCount, '');
            $row4[0] = "Date: {$date}  |  Tickets: {$count}  |  Revenue: {$revenue}";
        }
        $rows[] = $row4;

        // Row 5 – Separator
        $rows[] = array_fill(0, $colCount, '');

        // Row 6 – Column headers
        $rows[] = array_values($this->columns);

        // Rows 7+ – Data
        foreach ($this->tickets as $i => $t) {
            $row = [];
            foreach ($cols as $key) {
                $row[] = match ($key) {
                    'number'           => $i + 1,
                    'buyer_name'       => $t->buyer_name,
                    'buyer_phone'      => $t->buyer_phone  ?? '',
                    'buyer_email'      => $t->buyer_email  ?? '',
                    'buyer_address'    => $t->buyer_address    ?? '',
                    'buyer_occupation' => $t->buyer_occupation ?? '',
                    'yeneshaa_abat'    => $t->yeneshaa_abat ? 'Yes' : 'No',
                    'ticket_code'      => $t->ticket_code,
                    'sold_by'          => $t->agent->name ?? '',
                    'date_sold'        => $t->sold_at ? $t->sold_at->format('Y-m-d H:i') : '',
                    'amount_paid'      => (float) $t->price_paid,
                    'currency'         => $t->currency,
                    default            => '',
                };
            }
            $rows[] = $row;
        }

        // Total row
        $totalRow = array_fill(0, $colCount, '');
        if (in_array('buyer_name', $cols)) {
            $totalRow[array_search('buyer_name', $cols)] = 'TOTAL';
        }
        if (in_array('amount_paid', $cols)) {
            $totalRow[array_search('amount_paid', $cols)] =
                (float) $this->tickets->sum(fn ($t) => (float) $t->price_paid);
        }
        if (in_array('currency', $cols)) {
            $totalRow[array_search('currency', $cols)] = $this->event->currency ?? '';
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = [
            'number'           => 6,
            'buyer_name'       => 24,
            'buyer_phone'      => 17,
            'buyer_email'      => 28,
            'buyer_address'    => 26,
            'buyer_occupation' => 18,
            'yeneshaa_abat'    => 14,
            'ticket_code'      => 20,
            'sold_by'          => 18,
            'date_sold'        => 17,
            'amount_paid'      => 14,
            'currency'         => 10,
        ];

        $result = [];
        $letter = 'A';
        foreach (array_keys($this->columns) as $key) {
            $result[$letter] = $widths[$key] ?? 16;
            $letter++;
        }

        return $result;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $cols     = array_keys($this->columns);
                $colCount = count($cols);
                $dataRows = $this->tickets->count();

                $lastDataRow = 6 + $dataRows;
                $totalRow    = $lastDataRow + 1;
                $lastCol     = $this->colLetter($colCount);

                // ── Merge header rows ──────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");

                if ($colCount >= 3) {
                    $third = (int) floor($colCount / 3);
                    $c1end = $this->colLetter($third);
                    $c2end = $this->colLetter($third * 2);
                    $sheet->mergeCells("A4:{$c1end}4");
                    $sheet->mergeCells($this->colLetter($third + 1) . "4:{$c2end}4");
                    $sheet->mergeCells($this->colLetter($third * 2 + 1) . "4:{$lastCol}4");
                } else {
                    $sheet->mergeCells("A4:{$lastCol}4");
                }

                $sheet->mergeCells("A5:{$lastCol}5");

                // ── Row 1 – Company ────────────────────────────────────────
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A252F']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Row 2 – Event title ────────────────────────────────────
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E86C1']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(28);

                // ── Row 3 – Description ────────────────────────────────────
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF1B2631']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6EAF8']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(30);

                // ── Row 4 – Stats ──────────────────────────────────────────
                if ($colCount >= 3) {
                    $third = (int) floor($colCount / 3);
                    foreach (['A4', $this->colLetter($third + 1) . '4', $this->colLetter($third * 2 + 1) . '4'] as $cell) {
                        $sheet->getStyle($cell)->applyFromArray([
                            'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1A252F']],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD5D8DC']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        ]);
                    }
                } else {
                    $sheet->getStyle('A4')->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD5D8DC']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
                $sheet->getRowDimension(4)->setRowHeight(22);

                // ── Row 5 – Dark separator ─────────────────────────────────
                $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A252F']],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(4);

                // ── Row 6 – Column headers ─────────────────────────────────
                $sheet->getStyle("A6:{$lastCol}6")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF154360']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(22);

                // ── Data rows – alternating stripe ────────────────────────
                for ($row = 7; $row <= $lastDataRow; $row++) {
                    $bg = ($row % 2 === 0) ? 'FFEAF4FB' : 'FFFFFFFF';
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font'      => ['size' => 10],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }

                // ── Total row ──────────────────────────────────────────────
                $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E8449']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($totalRow)->setRowHeight(22);

                // ── Borders on the data section ────────────────────────────
                $sheet->getStyle("A6:{$lastCol}{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFBDC3C7'],
                        ],
                    ],
                ]);

                // ── Per-column alignment & formatting ─────────────────────
                $centerCols = ['number', 'buyer_phone', 'yeneshaa_abat', 'ticket_code', 'date_sold', 'currency'];
                $rightCols  = ['amount_paid'];
                $leftCols   = ['buyer_name', 'buyer_email', 'buyer_address', 'buyer_occupation', 'sold_by'];

                if ($dataRows > 0) {
                    $letterIdx = 1;
                    foreach ($cols as $key) {
                        $colLetter = $this->colLetter($letterIdx);
                        $range     = "{$colLetter}7:{$colLetter}{$lastDataRow}";

                        if (in_array($key, $centerCols)) {
                            $sheet->getStyle($range)->applyFromArray([
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            ]);
                        } elseif (in_array($key, $rightCols)) {
                            $sheet->getStyle($range)->applyFromArray([
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                            ]);
                            $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
                        } elseif (in_array($key, $leftCols)) {
                            $sheet->getStyle($range)->applyFromArray([
                                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
                            ]);
                        }

                        if ($key === 'buyer_name') {
                            $sheet->getStyle($range)->applyFromArray(['font' => ['bold' => true]]);
                        }

                        $letterIdx++;
                    }
                }
            },
        ];
    }

    /** Convert 1-based column index to Excel letter(s): 1→A, 26→Z, 27→AA … */
    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = (int) floor($index / 26);
        }

        return $letter;
    }
}
