<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\VendingMachineController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});

Route::group([
    'middleware' => ['api','role:admin'],
    'prefix' => 'auth'
], function ($router) {   
    Route::apiResource('products',VendingMachineController::class);
    Route::get('/transactions', [VendingMachineController::class, 'viewTransactions'])->name('transactions.index');
    Route::post('/products/{id}/transaction', [VendingMachineController::class, 'makeTransaction'])->name('products.transaction');
});

Route::group([
    'middleware' => ['api','role:user,admin'],
    'prefix' => 'auth'
], function ($router) {
    
    Route::get('/products', [VendingMachineController::class, 'index']);
    Route::get('/products/{id}', [VendingMachineController::class, 'show']);;
    Route::post('/products/{id}/transaction', [VendingMachineController::class, 'makeTransaction']);
});
