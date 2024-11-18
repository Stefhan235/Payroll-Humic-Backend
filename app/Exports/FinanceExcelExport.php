<?php

namespace App\Exports;

use App\Models\Finance;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Carbon;

class FinanceExcelExport implements FromCollection, WithHeadings, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        $finances = Finance::whereBetween(DB::raw('DATE(date)'), [$this->startDate, $this->endDate])
            ->select([
                'id',
                'activity_name',
                'transaction_type',
                'amount',
                'tax_amount',
                'document_evidence',
                'image_evidence',
                'status',
                'date'
            ])
            ->orderBy('date', 'desc')
            ->get();

        $numberedFinances = $finances->map(function ($finance, $key) {
            return [
                'No' => $key + 1,
                'Activity Name' => $finance->activity_name,
                'Transaction Type' => $finance->transaction_type,
                'Amount' => (string)$finance->amount,
                'Tax Amount' => (string)$finance->tax_amount,
                'Document Evidence' => url('storage/app/public/' . $finance->document_evidence),
                'Image Evidence' => url('storage/app/public/' . $finance->image_evidence),
                'Status' => $finance->status,
                'Date' => Carbon::parse($finance->date)->format('d-m-Y'),
            ];
        });

        return $numberedFinances;
    }

    public function headings(): array
    {
        return [
            'No',
            'Activity Name',
            'Transaction Type',
            'Amount',
            'Tax Amount',
            'Document Evidence',
            'Image Evidence',
            'Status',
            'Date'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->insertNewRowBefore(1, 2);
        $sheet->setCellValue('A1', 'Laporan Keuangan');
        $sheet->setCellValue('A2', 'Tanggal: ' . Carbon::parse($this->startDate)->format('d-m-Y') . ' - ' . Carbon::parse($this->endDate)->format('d-m-Y'));

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->insertNewRowBefore(3, 1);

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A4:' . $highestColumn . '4')->getFont()->setBold(true);

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->getAlignment()->setHorizontal('center');

        foreach (range('A', $highestColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];

        $sheet->getStyle('A4:' . $highestColumn . $highestRow)->applyFromArray($styleArray);
    }
}
