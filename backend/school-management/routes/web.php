<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});


Route::get('/verificar/{token}', [\App\Http\Controllers\ReceiptController::class, 'verify'])
    ->name('receipts.verify')->middleware('throttle:60,1');

require __DIR__.'/auth.php';
