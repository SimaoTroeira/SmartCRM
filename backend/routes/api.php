<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\FileUploadController;

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

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [UserController::class, 'changePassword']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user', [UserController::class, 'getUserProfile']);
    Route::post('/upload', [FileUploadController::class, 'upload']);


    Route::get('/available-tables', [DataImportController::class, 'getAvailableTables']);
    Route::get('/table-columns/{table}', [DataImportController::class, 'getTableColumns']);
    
    Route::post('/import/mapped-data', [DataImportController::class, 'storeMappedData']);
    Route::get('/tables/{tableName}/data', [DataImportController::class, 'getUserData']);
});