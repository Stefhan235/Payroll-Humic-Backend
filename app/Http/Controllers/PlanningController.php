<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planning;
use App\Models\Item;
use Illuminate\Support\Facades\Validator;

class PlanningController extends Controller
{
    public function getAllPlanning(Request $request)
    {
        $limit = $request->input('limit', 10);
        $year = $request->input('year', date('Y'));

        $planningData = Planning::whereYear('start_date', $year)
                                ->withCount(['item' => function ($query) {
                                    $query->where('isAddition', 0);
                                }])
                                ->withSum(['item' => function ($query) {
                                    $query->where('isAddition', 0);
                                }], 'netto_amount')
                                ->orderBy('created_at', 'desc')
                                ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $planningData
        ], 200);
    }

    public function getPlanningByID($id)
    {
        $planningData = Planning::with(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }])
                                ->withCount(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }])
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'bruto_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'tax_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'netto_amount')
                                ->find($id);

        if (!$planningData) {
            return response()->json([
                'status' => false,
                'message' => "Planning Data With id {$id} Not Found.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $planningData
        ], 200);
    }

    public function getAllRealization(Request $request)
    {
        $limit = $request->input('limit', 10);
        $year = $request->input('year', date('Y'));

        $realizationData = Planning::where('status', 'approve')
                                    ->whereYear('start_date', $year)
                                    ->withCount(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                    ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'netto_amount')
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $realizationData
        ], 200);
    }

    public function getRealizationByID($id)
    {                          
        $realizationData =  Planning::with(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                ->withCount(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'bruto_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'tax_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'netto_amount')
                                ->find($id);

        if (!$realizationData) {
            return response()->json([
                'status' => false,
                'message' => "Realization Data With id {$id} Not Found.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $realizationData
        ], 200);
    }

    public function postPlanning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();

        $planning = Planning::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $planningData = Planning::find($planning->id);

        return response()->json([
            'status' => true,
            'message' => 'Planning Created Successfully',
            'planning' => $planningData
        ], 201);
    }

    public function updatePlanning(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
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
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        $planningData = Planning::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Planning Updated Successfully',
            'planning' => $planningData
        ], 200);
    }

    public function updatePlanningStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approve,pending,decline,deleted',
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
            'status' => $request->status,
        ]);

        if ($request->status === 'approve') {
            $items = Item::where('planning_id', $id)->where('isAddition', 0)->get();

            foreach ($items as $item) {
                Item::create([
                    'planning_id' => $item->planning_id,
                    'date' => $item->date,
                    'information' => $item->information,
                    'bruto_amount' => $item->bruto_amount,
                    'tax_amount' => $item->tax_amount,
                    'netto_amount' => $item->netto_amount,
                    'category' => $item->category,
                    'isAddition' => 1,
                ]);
            }
        }

        $planningData = Planning::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Planning Status Updated Successfully',
            'planning' => $planningData
        ], 200);
    }

    public function deletePlanning($id)
    {
        $planningData = Planning::find($id);

        if (!$planningData) {
            return response()->json([
                'status' => false,
                'message' => "Planning Data With id ${id} Not Found",
            ], 404);
        }

        $planningData->delete();

        return response()->json([
            'status' => true,
            'message' => 'Planning Data Deleted Successfully.',
        ], 200);
    }

    public function getComparePlanning(Request $request)
    {
        $limit = $request->input('limit', 10);
        $year = $request->input('year', date('Y'));

        $comparePlanningData = Planning::where('status', 'approve')
                                    ->whereYear('start_date', $year)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $comparePlanningData
        ], 200);
    }

    public function getCompareDataByID($id){
        $planningData = Planning::with(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }])
                                ->withCount(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }])
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'bruto_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'tax_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 0);
                                    }], 'netto_amount')
                                ->find($id);

        $realizationData = Planning::with(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                ->withCount(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }])
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'bruto_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'tax_amount')
                                ->withSum(['item' => function ($query) {
                                        $query->where('isAddition', 1);
                                    }], 'netto_amount')
                                ->find($id);

        if (!$planningData) {
            return response()->json([
                'status' => false,
                'message' => "Compare Planning Data With id {$id} Not Found.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'planning' => $planningData,
                'realization' => $realizationData
            ]
        ], 200);
    }

    public function getApprovalPlanning(Request $request){
        $limit = $request->input('limit', 10);

        $itemQuery = function ($query) {
            $query->where('isAddition', 0);
        };

        $pendingPlanningData = Planning::with(['item' => $itemQuery])
            ->where('status', 'pending')
            ->withCount(['item' => $itemQuery])
            ->withSum(['item' => $itemQuery], 'bruto_amount')
            ->withSum(['item' => $itemQuery], 'tax_amount')
            ->withSum(['item' => $itemQuery], 'netto_amount')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $pendingPlanningData
        ], 200);
    }
}
