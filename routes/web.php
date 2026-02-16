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

use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Redirect Default
    |--------------------------------------------------------------------------
    */
    Route::get('/', fn() => redirect()->route('dashboard'));

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/dashboard/operator', [DashboardController::class, 'operatorDashboard'])
        ->name('dashboard.operator');

    /*
    |--------------------------------------------------------------------------
    | Production
    |--------------------------------------------------------------------------
    */
    Route::prefix('production')->name('production.')->group(function () {

        Route::get('/create', [ProductionController::class, 'create'])
            ->name('create');

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

        Route::get('/create', [RejectController::class, 'create'])
            ->name('create');

        Route::post('/store', [RejectController::class, 'store'])
            ->name('store');
    });

    /*
    |--------------------------------------------------------------------------
    | Downtime
    |--------------------------------------------------------------------------
    */
    Route::prefix('downtime')->name('downtime.')->group(function () {

        Route::get('/create', [DowntimeController::class, 'create'])
            ->name('create');

        Route::post('/store', [DowntimeController::class, 'store'])
            ->name('store');

        Route::get('/', [TrackingDowntimeController::class, 'index'])
            ->name('tracking');

        Route::get('/tracking/pdf', [TrackingDowntimeController::class, 'exportPdf'])
            ->name('tracking.pdf');
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

            Route::get('/pdf', [TrackingOperatorController::class, 'exportPdf'])
                ->name('pdf');

            Route::get('/{operator}/{date}', [TrackingOperatorController::class, 'show'])
                ->name('show');
        });

        Route::prefix('mesin')->name('mesin.')->group(function () {

            Route::get('/', [TrackingMachineController::class, 'index'])
                ->name('index');

            Route::get('/pdf', [TrackingMachineController::class, 'exportPdf'])
                ->name('pdf');

            Route::get('/{machine}/{date}', [TrackingMachineController::class, 'show'])
                ->name('show');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Daily Report (Daftar Harian)
    |--------------------------------------------------------------------------
    */
    Route::prefix('daily-report')->name('daily_report.')->group(function () {

        Route::prefix('operator')->name('operator.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DailyReportController::class, 'operatorIndex'])->name('index');
            Route::get('/show/{date}', [\App\Http\Controllers\DailyReportController::class, 'operatorShow'])->name('show');
            Route::get('/edit/{id}', [\App\Http\Controllers\DailyReportController::class, 'operatorEdit'])->name('edit');
            Route::put('/update/{id}', [\App\Http\Controllers\DailyReportController::class, 'operatorUpdate'])->name('update');
            Route::delete('/destroy/{id}', [\App\Http\Controllers\DailyReportController::class, 'operatorDestroy'])->name('destroy');
            Route::get('/pdf/{date}', [\App\Http\Controllers\DailyReportController::class, 'operatorExportPdf'])->name('pdf');
            // Locking
            Route::post('/toggle-lock', [\App\Http\Controllers\DailyReportController::class, 'toggleLock'])->name('toggle_lock');
        });

        Route::prefix('downtime')->name('downtime.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DailyReportController::class, 'downtimeIndex'])->name('index');
            Route::get('/show/{date}', [\App\Http\Controllers\DailyReportController::class, 'downtimeShow'])->name('show');
            Route::delete('/destroy/{id}', [\App\Http\Controllers\DailyReportController::class, 'downtimeDestroy'])->name('destroy');
            Route::get('/pdf/{date}', [\App\Http\Controllers\DailyReportController::class, 'downtimeExportPdf'])->name('pdf');
            // Re-use lock toggle as it is date-based
            Route::post('/toggle-lock', [\App\Http\Controllers\DailyReportController::class, 'toggleLock'])->name('toggle_lock');
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

    /*
    |--------------------------------------------------------------------------
    | Audit Logs (MR & Direktur Only)
    |--------------------------------------------------------------------------
    */
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->name('audit_logs.index');

    /*
    |--------------------------------------------------------------------------
    | Internal API / Autocomplete
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/search/items', [\App\Http\Controllers\AutocompleteController::class, 'searchItems'])->name('search.items');
        Route::get('/search/operators', [\App\Http\Controllers\AutocompleteController::class, 'searchOperators'])->name('search.operators');
        Route::get('/search/machines', [\App\Http\Controllers\AutocompleteController::class, 'searchMachines'])->name('search.machines');
        Route::get('/search/heat-numbers', [\App\Http\Controllers\AutocompleteController::class, 'searchHeatNumbers'])->name('search.heat_numbers');
        Route::get('/item-stats/{code}', [\App\Http\Controllers\AutocompleteController::class, 'getItemStats'])->name('item.stats');

        Route::post('/sync', [\App\Http\Controllers\ManualSyncController::class, 'sync'])->name('manual.sync');

        // Context Switcher
        Route::get('/context/departments', [\App\Http\Controllers\ContextSwitcherController::class, 'getDepartments'])->name('context.departments');
        Route::post('/context/set', [\App\Http\Controllers\ContextSwitcherController::class, 'setDepartment'])->name('context.set');
    });
});
