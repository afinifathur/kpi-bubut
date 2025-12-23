<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Daftar command Artisan custom.
     */
    protected $commands = [
        \App\Console\Commands\PullMasterMachines::class,
    ];

    /**
     * Definisi penjadwalan command.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 16:30 - Generate KPI harian (H-1)
        $schedule->command('kpi:generate-daily')
            ->dailyAt('16:30')
            ->withoutOverlapping();

        // 16:40 - Export CSV KPI
        $schedule->command('kpi:export-csv')
            ->dailyAt('16:40')
            ->withoutOverlapping();

        // Auto export KPI setiap 30 menit
        $schedule->command('kpi:auto-export')
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        // Pull master machines setiap 5 menit
        $schedule->command('pull:master-machines')
            ->everyFiveMinutes()
            ->withoutOverlapping();
    }

    /**
     * Register command console.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
