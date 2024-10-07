<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Finance;
use Carbon\Carbon;
use App\Exports\FinanceExcelExport;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{
    public function getAllFinanceData()
    {
        $financeData = Finance::with('user')
                            ->orderBy('created_at', 'desc')
                            ->get();

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

    public function getAllIncome()
    {
        $financeIncomeData = Finance::where('transaction_type', 'income')
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'status' => true,
            'data' => $financeIncomeData
        ], 200);
    }

    public function getAllExpense()
    {
        $financeExpenseData = Finance::where('transaction_type', 'expense')
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'status' => true,
            'data' => $financeExpenseData
        ], 200);
    }

    public function getPendingFinance()
    {
        $financePendingData = Finance::where('status', 'pending')
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        return response()->json([
            'status' => true,
            'data' => $financePendingData
        ], 200);
    }

    public function getDashboardData(Request $request)
    {
        // Total ballance data
        $totalIncome = Finance::where('transaction_type', 'income')
                        ->where('status', 'approve')
                        ->sum('amount');

        $totalExpense = Finance::where('transaction_type', 'expense')
                        ->where('status', 'approve')
                        ->sum('amount');

        $totalExpenseTax = Finance::where('transaction_type', 'expense')
                            ->where('status', 'approve')
                            ->sum('tax_amount');

        $totalBallance = $totalIncome - $totalExpense - $totalExpenseTax;

        // Montly total expense and income
        $currentDate = Carbon::now()->setTimezone('Asia/Jakarta');
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;

        // Monthly total income
        $totalMonthlyIncome = Finance::where('transaction_type', 'income')
                                ->where('status', 'approve')
                                ->whereMonth('created_at', $currentMonth)
                                ->whereYear('created_at', $currentYear)
                                ->sum('amount');
        
        // Monthly total expense
        $monthlyExpense = Finance::where('transaction_type', 'expense')
                                ->where('status', 'approve')
                                ->whereMonth('created_at', $currentMonth)
                                ->whereYear('created_at', $currentYear)
                                ->sum('amount');

        $monthlyExpenseTax = Finance::where('transaction_type', 'expense')
                                ->where('status', 'approve')
                                ->whereMonth('created_at', $currentMonth)
                                ->whereYear('created_at', $currentYear)
                                ->sum('tax_amount');

        $totalMonthlyExpense = $monthlyExpense + $monthlyExpenseTax;

        // Transaction list with filtering (query params)
        $transactionType = $request->query('transaction_type');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : null;

        $query = Finance::when($transactionType, function ($query, $transactionType) {
                            return $query->where('transaction_type', $transactionType);
                        })
                        ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                            return $query->whereBetween('created_at', [$startDate, $endDate]);
                        });

        $transactionList = $query->orderBy('created_at', 'desc')->get();

        // User approval list
        // $user = auth()->user();
        // $approvalList = Finance::where('user_id', $user->id)->get();

        return response()->json([
            'status' => true,
            'data' => [
                'ballance' => $totalBallance,
                'monthlyIncome' => intval($totalMonthlyIncome),
                'monthlyExpense' => intval($totalMonthlyExpense),
                'transactionList' => $transactionList
                // 'approvalList' => $approvalList
            ]
        ], 200);
    }

    public function postFinanceData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'activity_name' => 'required|string|max:255',
            'transaction_type' => 'required|in:income,expense',
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

        $fileName = 'finance_data_' . $startDate . '_to_' . $endDate . '.xlsx';

        return Excel::download(new FinanceExcelExport($startDate, $endDate), $fileName);
    }
}
