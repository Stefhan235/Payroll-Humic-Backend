<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\File;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:jpeg,png,jpg,gif,pdf,docx,xlsx',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('files');

        $fileType = $file->getClientOriginalExtension();

        $uploadedFile = File::create([
            'file_path' => $path,
            'type' => $fileType,
            'article_id' => $request->input('article_id', null),
        ]);

        return response()->json([
            'status' => true,
            'url' => url('storage/' . $path),
            'type' => $fileType
        ], 200);
    }
}
