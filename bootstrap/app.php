<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function ($schedule) {
        // 16:30 - Generate KPI harian (H-1)
        $schedule->command('kpi:generate-daily')->dailyAt('16:30')->withoutOverlapping();

        // 16:40 - Export CSV KPI
        $schedule->command('kpi:export-csv')->dailyAt('16:40')->withoutOverlapping();

        // Auto export KPI setiap 30 menit
        $schedule->command('kpi:auto-export')->everyThirtyMinutes()->withoutOverlapping();

        // MASTER DATA SYNC
        $schedule->command('pull:master-items')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('pull:master-operators')->everyTenMinutes()->withoutOverlapping();
        $schedule->command('pull:master-machines')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('pull:master-heat-numbers')->everyTenMinutes()->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
