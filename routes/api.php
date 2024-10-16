<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/update-password', [AuthController::class, 'updatePassword'])->middleware('auth:sanctum');
Route::post('/update-profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');

Route::get('/finances', [FinanceController::class, 'getAllFinanceData'])->middleware('auth:sanctum');
Route::get('/finance/{id}', [FinanceController::class, 'getFinanceDataById'])->middleware('auth:sanctum');
Route::get('/dashboard', [FinanceController::class, 'getDashboardData'])->middleware('auth:sanctum');

Route::post('/finance', [FinanceController::class, 'postFinanceData'])->middleware('auth:sanctum');
Route::delete('/finance/{id}', [FinanceController::class, 'deleteFinanceDataById'])->middleware(['auth:sanctum', 'role:superAdmin']);
Route::post('/finance/{id}', [FinanceController::class, 'updateFinanceStatus'])->middleware(['auth:sanctum', 'role:superAdmin']);

Route::get('/income',[FinanceController::class, 'getAllIncome'])->middleware('auth:sanctum');
Route::get('/expense',[FinanceController::class, 'getAllExpense'])->middleware('auth:sanctum');
Route::get('/pending',[FinanceController::class, 'getPendingFinance'])->middleware(['auth:sanctum', 'role:superAdmin']);

Route::get('/export', [FinanceController::class, 'export'])->middleware('auth:sanctum');

Route::get('/plannings', [PlanningController::class, 'getAllPlanning'])->middleware('auth:sanctum'); 
Route::get('/planning/{id}', [PlanningController::class, 'getPlanningByID'])->middleware('auth:sanctum');
Route::post('/planning', [PlanningController::class, 'postPlanning'])->middleware('auth:sanctum'); 
Route::post('/planning/{id}', [PlanningController::class, 'updatePlanning'])->middleware('auth:sanctum');
Route::delete('/planning/{id}', [PlanningController::class, 'deletePlanningById'])->middleware('auth:sanctum'); 
Route::post('/files/upload', [FileController::class, 'upload'])->middleware('auth:sanctum');  