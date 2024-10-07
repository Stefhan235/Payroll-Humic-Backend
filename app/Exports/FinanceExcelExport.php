<?php

namespace App\Exports;

use App\Models\Finance;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
        $finances = Finance::whereBetween(DB::raw('DATE(created_at)'), [$this->startDate, $this->endDate])
            ->select([
                'id',
                'activity_name',
                'transaction_type',
                'amount',
                'tax_amount',
                'document_evidence',
                'image_evidence',
                'status',
                'created_at'
            ])->get();

        $numberedFinances = $finances->map(function ($finance, $key) {
            return [
                'No' => $key + 1,
                // 'ID' => $finance->id,
                'Activity Name' => $finance->activity_name,
                'Transaction Type' => $finance->transaction_type,
                'Amount' => (string)$finance->amount,
                'Tax Amount' => (string)$finance->tax_amount,
                'Document Evidence' => $finance->document_evidence,
                'Image Evidence' => $finance->image_evidence,
                'Status' => $finance->status,
                'Created At' => $finance->created_at,
            ];
        });

        return $numberedFinances;
    }

    public function headings(): array
    {
        return [
            'No',
            // 'ID',
            'Activity Name',
            'Transaction Type',
            'Amount',
            'Tax Amount',
            'Document Evidence',
            'Image Evidence',
            'Status',
            'Created At'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

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

        $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);
    }
}
