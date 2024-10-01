<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Finance;
use Carbon\Carbon;

class FinanceController extends Controller
{
    public function getAllFinanceData()
    {
        $financeData = Finance::all();

        return response()->json([
            'status' => true,
            'data' => $financeData
        ], 200);
    }

    public function getDashboard(){
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

        $currentDate = Carbon::now()->setTimezone('Asia/Jakarta');
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;

        $totalMonthlyIncome = Finance::where('transaction_type', 'income')
                                ->where('status', 'approve')
                                ->whereMonth('created_at', $currentMonth)
                                ->whereYear('created_at', $currentYear)
                                ->sum('amount');
        
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

        return response()->json([
            'status' => true,
            'data' => [
                'ballance' => $totalBallance,
                'monthlyIncome' => intval($totalMonthlyIncome),
                'monthlyExpense' => intval($totalMonthlyExpense)
            ]
        ], 200);
    }
}
