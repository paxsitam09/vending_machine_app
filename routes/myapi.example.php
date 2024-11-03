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
    
    Route::apiResource("products",VendingMachineController::class);
    
    Route::get('/transactions', [VendingMachineController::class, 'viewTransactions'])->name('transactions.index');
    Route::post('/products/{id}/transaction', [VendingMachineController::class, 'makeTransaction'])->name('products.transaction');
});
