<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\RejectController;
use App\Http\Controllers\DowntimeController;
use App\Http\Controllers\TrackingOperatorController;
use App\Http\Controllers\TrackingMachineController;
use App\Http\Controllers\TrackingDowntimeController;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Redirect Default
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect()->route('dashboard'));

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Production
|--------------------------------------------------------------------------
*/
Route::prefix('produksi')->name('produksi.')->group(function () {
    Route::get('/input', [ProductionController::class, 'create'])
        ->name('input');

    Route::post('/store', [ProductionController::class, 'store'])
        ->name('store');
});

/*
|--------------------------------------------------------------------------
| Reject
|--------------------------------------------------------------------------
*/
Route::prefix('reject')->name('reject.')->group(function () {
    Route::get('/', [RejectController::class, 'index'])
        ->name('index');

    Route::get('/input', [RejectController::class, 'create'])
        ->name('input');

    Route::post('/store', [RejectController::class, 'store'])
        ->name('store');
});

/*
|--------------------------------------------------------------------------
| Downtime (Input)
|--------------------------------------------------------------------------
*/
Route::prefix('downtime')->name('downtime.')->group(function () {
    Route::get('/input', [DowntimeController::class, 'create'])
        ->name('input');

    Route::post('/store', [DowntimeController::class, 'store'])
        ->name('store');

    Route::get('/', [TrackingDowntimeController::class, 'index'])
        ->name('tracking');
});

/*
|--------------------------------------------------------------------------
| Tracking
|--------------------------------------------------------------------------
*/
Route::prefix('tracking')->name('tracking.')->group(function () {

    Route::prefix('operator')->name('operator.')->group(function () {
        Route::get('/', [TrackingOperatorController::class, 'index'])
            ->name('index');

        Route::get('/{operator}/{date}', [TrackingOperatorController::class, 'show'])
            ->name('show');
    });

    Route::prefix('mesin')->name('mesin.')->group(function () {
        Route::get('/', [TrackingMachineController::class, 'index'])
            ->name('index');

        Route::get('/{machine}/{date}', [TrackingMachineController::class, 'show'])
            ->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| Export
|--------------------------------------------------------------------------
*/
Route::prefix('export')->name('export.')->group(function () {
    Route::get('/operator/{date}', [ExportController::class, 'operatorKpi'])
        ->name('operator');

    Route::get('/machine/{date}', [ExportController::class, 'machineKpi'])
        ->name('machine');

    Route::get('/downtime/{date}', [ExportController::class, 'downtime'])
        ->name('downtime');
});
