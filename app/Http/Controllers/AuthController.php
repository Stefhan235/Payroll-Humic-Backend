<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
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
        ]);

        // Mengecek validasi data dan menampilkan pesan error
        if($validatedData->fails()){
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validatedData->errors()
            ], 422);
        }

        // Membuat user pada database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin'
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
}
