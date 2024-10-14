<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AuthController extends Controller
{
    // Register API
    public function register(Request $request)
    {
        // Melakukan validasi data request
        $validatedData = Validator::make($request->all(),
        [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        // Mengecek validasi data dan menampilkan pesan error
        if($validatedData->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('users');
        }

        // Membuat user pada database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'image' => $imagePath
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Response register sukses
        return response()->json([
            'status' => true,
            'message' => "User Created Successfully",
            'token' => $token,
        ], 201);
    }

    // Login API
    public function login(Request $request)
    {
        $validatedData = Validator::make($request->all(),
        [
            'email' => 'required|string|email',
            'password' => 'required'
        ]);

        if($validatedData->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        if(!Auth::attempt($request->only(['email', 'password']))){
            return response()->json([
                'status' => false,
                'message' => 'Email and Password does not match',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'token' => $token
        ], 200);
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'User Logged Out Successfully'
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if($validatedData->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Old Password is Incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status'=> true,
            'message' => 'Password Change Successfully'
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $user = auth()->user();

        $user->name = $request->name;

        if ($request->hasFile('image')) {
            if ($user->image) {
                Storage::disk('public')->delete($user->image);
            }

            $imagePath = $request->file('image')->store('users');
            $user->image = $imagePath;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile Updated Successfully.',
        ], 200);
    }
}
