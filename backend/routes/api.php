<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\CompanyInviteController;
use App\Http\Controllers\UserCompanyRoleController;


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

    //data mapping
    Route::post('/import/mapped-data', [DataImportController::class, 'storeMappedData']);
    Route::get('/tables/{tableName}/data', [DataImportController::class, 'getUserData']);

    //companies
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
    Route::post('/companies/{id}/submit', [CompanyController::class, 'submit']);
    
    Route::middleware(['auth:sanctum', 'is_superadmin'])->group(function () {
        Route::post('/companies/{id}/approve', [CompanyController::class, 'approveCompany']);
        Route::delete('/companies/{id}/reject', [CompanyController::class, 'rejectCompany']);
    });
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::get('/companies/{companyId}/user-role', [CompanyController::class, 'getUserRole']);
    Route::put('/companies/{id}/deactivate', [CompanyController::class, 'deactivate']);


    //campaigns
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);

    Route::post('/campaigns/{id}/users', [CampaignController::class, 'addUsers']);
    Route::get('/campaigns/{campaign}/users', [CampaignController::class, 'getUsers']);
    Route::delete('/campaigns/{campaign}/users/{user}', [CampaignController::class, 'removeUser']);


    //invites
    Route::post('/companies/{company}/invite', [CompanyInviteController::class, 'invite']);
    // Route::get('/invites/accept/{token}', [CompanyInviteController::class, 'accept']);
    Route::post('/invites/accept/{token}', [CompanyInviteController::class, 'accept']);

    Route::put('/user-company-roles/{id}/promote', [UserCompanyRoleController::class, 'promote']);
    Route::delete('/user-company-roles/{id}', [UserCompanyRoleController::class, 'destroy']);

    Route::put('/invites/{id}/resend', [CompanyInviteController::class, 'resend']);
    Route::delete('/invites/{id}/cancel', [CompanyInviteController::class, 'cancel']);
});
