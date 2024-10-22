<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planning;
use App\Models\Item;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    public function getAllItem(Request $request)
    {
        $limit = $request->input('limit', 10); 
        
        $itemData = Item::paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $itemData
        ], 200);
    }

    public function getItemByID($id)
    {
        $itemData = Item::find($id);

        if (!$itemData) {
            return response()->json([
                'status' => false,
                'message' => "Item Data With id {$id} Not Found.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $itemData
        ], 200);
    }

    public function postItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'planning_id' => 'required|exists:plannings,id',
            'date' => 'required|date',
            'information' => 'required|string',
            'bruto_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'netto_amount' => 'required|numeric',
            'category' => 'required|in:internal,eksternal,rka',
            'isAddition' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $item = Item::create([
            'planning_id' => $request->planning_id,
            'date' => $request->date,
            'information' => $request->information,
            'bruto_amount' => $request->bruto_amount,
            'tax_amount' => $request->tax_amount,
            'netto_amount' => $request->netto_amount,
            'category' => $request->category,
            'isAddition' => $request->isAddition,
        ]);

        $itemData = Item::find($item->id);

        return response()->json([
            'status' => true,
            'message' => 'Item Created Successfully',
            'planning' => $itemData
        ], 201);
    }

    public function updateItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'planning_id' => 'required|exists:plannings,id',
            'date' => 'required|date',
            'information' => 'required|string',
            'bruto_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'netto_amount' => 'required|numeric',
            'category' => 'required|in:internal,eksternal,rka',
            'isAddition' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'status' => false,
                'message' => "Item Data With id ${id} Not Found",
            ], 404);
        }

        $item->update([
            'planning_id' => $request->planning_id,
            'date' => $request->date,
            'information' => $request->information,
            'bruto_amount' => $request->bruto_amount,
            'tax_amount' => $request->tax_amount,
            'netto_amount' => $request->netto_amount,
            'category' => $request->category,
            'isAddition' => $request->isAddition,
        ]);

        $itemData = Item::find($id);

        return response()->json([
            'status' => true,
            'message' => 'Item Updated Successfully',
            'planning' => $itemData
        ], 200);
    }

    public function deleteItem($id)
    {
        $itemData = Item::find($id);

        if (!$itemData) {
            return response()->json([
                'status' => false,
                'message' => "Item Data With id ${id} Not Found",
            ], 404);
        }

        $itemData->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item Data Deleted Successfully.',
        ], 200);
    }
}
