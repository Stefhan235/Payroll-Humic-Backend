<?php

namespace App\Exports;

use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Carbon;

class ItemExport implements FromCollection, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $category;

    public function __construct($startDate, $endDate, $category)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->category = $category;
    }

    public function collection()
    {
        $items = Item::whereBetween(DB::raw('DATE(date)'), [$this->startDate, $this->endDate])
            ->where('category', $this->category)
            ->where('isAddition', 1)
            ->select([
                'id',
                'information',
                'bruto_amount',
                'tax_amount',
                'netto_amount',
                'category',
                'date'
            ])
            ->orderBy('date', 'desc')
            ->get();

        $numberedItems = $items->map(function ($item, $key) {
            return [
                'No' => $key + 1,
                'Information' => $item->information,
                'Bruto Amount' => (string)$item->bruto_amount,
                'Tax Amount' => (string)$item->tax_amount,
                'Netto Amount' => (string)$item->netto_amount,
                'Category' => ucfirst($item->category),
                'Date' => Carbon::parse($item->date)->format('d-m-Y'),
            ];
        });

        // Calculate the total values
        $totalBruto = $items->sum('bruto_amount');
        $totalTax = $items->sum('tax_amount');
        $totalNetto = $items->sum('netto_amount');

        // Add the total row at the end
        $numberedItems->push([
            'No' => '',
            'Information' => 'Total',
            'Bruto Amount' => (string)$totalBruto,
            'Tax Amount' => (string)$totalTax,
            'Netto Amount' => (string)$totalNetto,
            'Category' => '',
            'Date' => '',
        ]);

        return $numberedItems;
    }


    public function headings(): array
    {
        return [
            'No',
            'Information',
            'Bruto Amount',
            'Tax Amount',
            'Netto Amount',
            'Category',
            'Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', 'Laporan Data Items');
        $sheet->setCellValue('A2', 'Tanggal: ' . Carbon::parse($this->startDate)->format('d-m-Y') . ' - ' . Carbon::parse($this->endDate)->format('d-m-Y') . ' | Kategori: ' . ucfirst($this->category));

        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->insertNewRowBefore(3, 1);

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Bold the header row
        $sheet->getStyle('A4:' . $highestColumn . '4')->getFont()->setBold(true);

        // Center alignment for all cells
        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setHorizontal('center');

        // Set columns to auto-size
        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add border to the table
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
        $sheet->getStyle('A4:' . $highestColumn . $highestRow)->applyFromArray($styleArray);

        // Highlight the total row
        $totalRow = $highestRow; // The last row is the total row
        $sheet->getStyle('A' . $totalRow . ':' . $highestColumn . $totalRow)->getFont()->setBold(true);
    }

}
