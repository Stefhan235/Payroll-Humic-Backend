<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planning;
use App\Models\Finance;
use Illuminate\Support\Facades\Validator;

class PlanningController extends Controller
{
    public function postPlanning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'deadline' => 'required|date',
            'target_amount' => 'required|numeric',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $planning = Planning::create([
            'title' => $request->title,
            'deadline' => $request->deadline,
            'target_amount' => $request->target_amount,
            'content' => $request->content,
        ]);
        
        $planningData = Planning::find($planning->id);

        return response()->json([
            'status' => true,
            'message' => 'Planning Created Successfully',
            'planning' => $planningData
        ], 201);
    }

    public function getAllPlanning(Request $request)
    {
        $limit = $request->input('limit', 10); 
        
        $planningData = Planning::paginate($limit);

        // GET Current Balance
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

        return response()->json([
            'status' => true,
            'current_balance' => $totalBalance,
            'data' => $planningData
        ], 200);
    }

    public function getPlanningByID($id)
    {
        $planning = Planning::find($id);

        if (!$planning) {
            return response()->json([
                'status' => false,
                'message' => "Planning Data With id {$id} Not Found.",
            ], 404);
        }

        // GET Current Balance
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

        return response()->json([
            'status' => true,
            'current_balance' => $totalBalance,
            'data' => $planning
        ], 200);
    }

    public function deletePlanningById($id)
    {
        $planning = Planning::find($id);

        if (!$planning) {
            return response()->json([
                'status' => false,
                'message' => "Planning Data With id ${id} Not Found",
            ], 404);
        }

        $planning->delete();

        return response()->json([
            'status' => true,
            'message' => 'Planning Data Deleted Successfully.',
        ], 200);
    }

    public function updatePlanning(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'deadline' => 'required|date',
            'target_amount' => 'required|numeric',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $planning = Planning::find($id);

        if (!$planning) {
            return response()->json([
                'status' => false,
                'message' => "Planning Data With id ${id} Not Found",
            ], 404);
        }

        $planning->update([
            'title' => $request->title,
            'deadline' => $request->deadline,
            'target_amount' => $request->target_amount,
            'content' => $request->content,
        ]);

        $planningData = Planning::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Planning Updated Successfully',
            'planning' => $planningData
        ], 200);
    }
}
