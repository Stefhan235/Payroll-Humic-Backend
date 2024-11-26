<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planning;
use App\Models\Item;
use Illuminate\Support\Facades\Validator;
use App\Exports\ItemExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

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
            'document_evidence' => 'nullable|file|mimes:xlsx,pdf',
            'image_evidence' => 'nullable|image|mimes:jpg,jpeg,png',
            'isAddition' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $documentPath = null;
        $imagePath = null;

        if ($request->hasFile('document_evidence')) {
            $documentPath = $request->file('document_evidence')->store('item/documents');
        }

        if ($request->hasFile('image_evidence')) {
            $imagePath = $request->file('image_evidence')->store('item/images');
        }

        $item = Item::create([
            'planning_id' => $request->planning_id,
            'date' => $request->date,
            'information' => $request->information,
            'bruto_amount' => $request->bruto_amount,
            'tax_amount' => $request->tax_amount,
            'netto_amount' => $request->netto_amount,
            'category' => $request->category,
            'document_evidence' => $documentPath,
            'image_evidence' => $imagePath,
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
            'document_evidence' => 'nullable|file|mimes:xlsx,pdf',
            'image_evidence' => 'nullable|image|mimes:jpg,jpeg,png',
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

        $documentPath = $item->document_evidence;
        $imagePath = $item->image_evidence;

        if ($request->hasFile('document_evidence')) {
            if ($documentPath) {
                Storage::delete($documentPath);
            }
            $documentPath = $request->file('document_evidence')->store('item/documents');
        }

        if ($request->hasFile('image_evidence')) {
            if ($imagePath) {
                Storage::delete($imagePath);
            }
            $imagePath = $request->file('image_evidence')->store('item/images');
        }

        $item->update([
            'planning_id' => $request->planning_id,
            'date' => $request->date,
            'information' => $request->information,
            'bruto_amount' => $request->bruto_amount,
            'tax_amount' => $request->tax_amount,
            'netto_amount' => $request->netto_amount,
            'category' => $request->category,
            'document_evidence' => $documentPath,
            'image_evidence' => $imagePath,
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

        if ($itemData->document_evidence) {
            Storage::delete($itemData->document_evidence);
        }

        if ($itemData->image_evidence) {
            Storage::delete($itemData->image_evidence);
        }

        $itemData->delete();

        return response()->json([
            'status' => true,
            'message' => 'Item Data Deleted Successfully.',
        ], 200);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $type = $request->input('type');
        $category = $request->input('category');

        if ($type == 'excel') {
            $fileName = 'items_data_' . $startDate . '_to_' . $endDate . '.xlsx';
            return Excel::download(new ItemExport($startDate, $endDate, $category), $fileName, \Maatwebsite\Excel\Excel::XLSX);
        } elseif ($type == 'pdf') {
            $fileName = 'items_data_' . $startDate . '_to_' . $endDate . '.pdf';
            return Excel::download(new ItemExport($startDate, $endDate, $category), $fileName, \Maatwebsite\Excel\Excel::MPDF);
        }
    }   
}
