<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Finance;
use App\Models\Planning;
use App\Models\Item;
use Carbon\Carbon;
use App\Exports\FinanceExcelExport;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{
    public function getAllFinanceData(Request $request)
    {
        $limit = $request->input('limit', 10); 
        
        $financeData = Finance::with('user')
                            ->orderBy('date', 'desc')
                            ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $financeData
        ], 200);
    }

    public function getFinanceDataById($id)
    {
        $finance = Finance::with('user')->find($id);

        if (!$finance) {
            return response()->json([
                'status' => false,
                'message' => "Finance Data With id {$id} Not Found.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $finance,
        ], 200);
    }

    public function getAllIncome(Request $request)
    {
        $limit = $request->input('limit', 10); 

        $financeIncomeData = Finance::where('transaction_type', 'income')
                                    ->orderBy('date', 'desc')
                                    ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $financeIncomeData
        ], 200);
    }

    public function getAllExpense(Request $request)
    {
        $limit = $request->input('limit', 10); 

        $financeExpenseData = Finance::where('transaction_type', 'expense')
                                    ->orderBy('date', 'desc')
                                    ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $financeExpenseData
        ], 200);
    }

    public function getPendingFinance(Request $request)
    {
        $limit = $request->input('limit', 10); 

        $financePendingData = Finance::where('status', 'pending')
                                    ->orderBy('date', 'desc')
                                    ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $financePendingData
        ], 200);
    }

    public function getDashboardData(Request $request)
    {
        // Total balance data
        $totalIncome = Finance::where('transaction_type', 'income')
                            ->where('status', 'approve')
                            ->sum('amount');

        $totalExpense = Finance::where('transaction_type', 'expense')
                            ->where('status', 'approve')
                            ->sum('amount');

        $totalExpenseTax = Finance::where('transaction_type', 'expense')
                                ->where('status', 'approve')
                                ->sum('tax_amount');

        $totalBalance = $totalIncome - $totalExpense - $totalExpenseTax;

        // Monthly total expense and income for the current month
        $currentDate = Carbon::now()->setTimezone('Asia/Jakarta');
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;

        // Check if year is provided in query params, otherwise use current year
        $selectedYear = $request->query('incomeExpenseYear', $currentYear);

        // Monthly total income
        $totalMonthlyIncome = Finance::where('transaction_type', 'income')
                                    ->where('status', 'approve')
                                    ->whereMonth('date', $currentMonth)
                                    ->whereYear('date', $currentYear)
                                    ->sum('amount');

        // Monthly total expense
        $monthlyExpense = Finance::where('transaction_type', 'expense')
                                    ->where('status', 'approve')
                                    ->whereMonth('date', $currentMonth)
                                    ->whereYear('date', $currentYear)
                                    ->sum('amount');

        $monthlyExpenseTax = Finance::where('transaction_type', 'expense')
                                    ->where('status', 'approve')
                                    ->whereMonth('date', $currentMonth)
                                    ->whereYear('date', $currentYear)
                                    ->sum('tax_amount');

        $totalMonthlyExpense = $monthlyExpense + $monthlyExpenseTax;

        // Monthly Expense and Income Chart Data
        $monthlyIncomeExpenseData = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        foreach ($months as $index => $monthName) {
            $income = Finance::where('transaction_type', 'income')
                            ->where('status', 'approve')
                            ->whereMonth('date', $index + 1)
                            ->whereYear('date', $selectedYear)
                            ->sum('amount');

            $expense = Finance::where('transaction_type', 'expense')
                            ->where('status', 'approve')
                            ->whereMonth('date', $index + 1)
                            ->whereYear('date', $selectedYear)
                            ->sum('amount');

            $expenseTax = Finance::where('transaction_type', 'expense')
                            ->where('status', 'approve')
                            ->whereMonth('date', $index + 1)
                            ->whereYear('date', $selectedYear)
                            ->sum('tax_amount');

            $monthlyIncomeExpenseData[] = [
                'name' => $monthName,
                'income' => intval($income),
                'expense' => intval($expense + $expenseTax),
            ];
        }

        // Finance Pending and Need Approval
        $financePendingData = Finance::where('status', 'pending')
                                    ->orderBy('date', 'desc')
                                    ->get();

        // Planning Pending and Need Approval
        $itemQuery = function ($query) {
            $query->where('isAddition', 0);
        };

        $planningPendingData = Planning::where('status', 'pending')
                                    ->withCount(['item' => $itemQuery])
                                    ->get();

        // Year filter from query params, default to current year
        $selectedYearForPieChart = $request->query('planningRealizationYear', $currentYear);

        // Planning Data with year filter
        $planningData = Planning::where('status', 'approve')
                                ->whereYear('start_date', $selectedYearForPieChart)
                                ->with(['item' => function ($query) {
                                    $query->where('isAddition', 0);
                                }])
                                ->get()
                                ->map(function ($planning) {
                                    return [
                                        'name' => $planning->title,
                                        'value' => $planning->item->sum('netto_amount'),
                                    ];
                                });

        // Calculate total planning value
        $totalPlanningValue = $planningData->sum('value');

        // Realization Data with year filter
        $realizationData = Planning::where('status', 'approve')
                                    ->whereYear('start_date', $selectedYearForPieChart)
                                    ->with(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                    ->get()
                                    ->map(function ($planning) {
                                        return [
                                            'name' => $planning->title,
                                            'value' => $planning->item->sum('netto_amount'),
                                        ];
                                    });

        // Calculate total realization value
        $totalRealizationValue = $realizationData->sum('value');

        // Transaction List With Filtering
        $transactionType = $request->query('transaction_type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : null;

        $query = Finance::when($transactionType, function ($query, $transactionType) {
                            return $query->where('transaction_type', $transactionType);
                        })
                        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                            return $query->whereBetween('date', [$startDate, $endDate]);
                        });

        $limit = $request->input('limit', 10); 
        $transactionList = $query->orderBy('date', 'desc')->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => [
                'balance' => $totalBalance,
                'monthlyIncome' => intval($totalMonthlyIncome),
                'monthlyExpense' => intval($totalMonthlyExpense),
                'monthlyIncomeExpenseData' => $monthlyIncomeExpenseData,
                'approval' => [
                    'transaction' => $financePendingData,
                    'planning' => $planningPendingData,
                ],
                'pieChart' => [
                    'totalPlanning' => $totalPlanningValue,
                    'planningData' => $planningData,
                    'totalRealization' => $totalRealizationValue,
                    'realizationData' => $realizationData,
                ],
                'transactionList' => $transactionList,
            ]
        ], 200);
    }

    public function postFinanceData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_name' => 'required|string|max:255',
            'transaction_type' => 'required|in:income,expense',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'document_evidence' => 'required|file|mimes:xlsx,pdf',
            'image_evidence' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $documentPath = $request->file('document_evidence')->store('documents');
        $imagePath = $request->file('image_evidence')->store('images');

        $user = auth()->user();

        $status = $user->role === 'superAdmin' ? 'approve' : 'pending';

        $finance = Finance::create([
            'user_id' => $user->id,
            'activity_name' => $request->activity_name,
            'transaction_type' => $request->transaction_type,
            'date' => $request->date,
            'amount' => $request->amount,
            'tax_amount' => $request->tax_amount,
            'document_evidence' => $documentPath,
            'image_evidence' => $imagePath,
            'status' => $status,
        ]);

        $financeData = Finance::with('user')->find($finance->id);

        return response()->json([
            'status' => true,
            'data' => $financeData,
        ], 201);
    }

    public function deleteFinanceDataById($id)
    {
        $finance = Finance::find($id);

        if (!$finance) {
            return response()->json([
                'status' => false,
                'message' => "Finance Data With id ${id} Not Found",
            ], 404);
        }

        if ($finance->document_evidence) {
            Storage::delete($finance->document_evidence);
        }
        
        if ($finance->image_evidence) {
            Storage::delete($finance->image_evidence);
        }

        $finance->delete();

        return response()->json([
            'status' => true,
            'message' => 'Finance Data Deleted Successfully.',
        ], 200);
    }

    public function updateFinanceStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approve,pending,decline',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $finance = Finance::with('user')->find($id);

        if (!$finance) {
            return response()->json([
                'status' => false,
                'message' => "Finance Data With id {$id} Not Found.",
            ], 404);
        }

        $finance->status = $request->status;
        $finance->save();

        return response()->json([
            'status' => true,
            'data' => $finance,
        ], 200);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $type = $request->input('type');

        if ($type == 'excel') {
            $fileName = 'finance_data_' . $startDate . '_to_' . $endDate . '.xlsx';
            return Excel::download(new FinanceExcelExport($startDate, $endDate), $fileName, \Maatwebsite\Excel\Excel::XLSX);
        }elseif ($type == 'pdf') {
            $fileName = 'finance_data_' . $startDate . '_to_' . $endDate . '.pdf';
            return Excel::download(new FinanceExcelExport($startDate, $endDate), $fileName, \Maatwebsite\Excel\Excel::MPDF);
        }
    }
}
