<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/exportar/rfm/{campanhaId}', [ExportController::class, 'exportarRfm']);
    Route::get('/exportar/churn/{campanhaId}', [ExportController::class, 'exportarChurn']);
    Route::get('/exportar/recommendation/{campanhaId}', [ExportController::class, 'exportarRecommendation']);
});
