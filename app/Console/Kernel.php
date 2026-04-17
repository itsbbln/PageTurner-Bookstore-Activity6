<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Backups
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: backup:run'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: backup:run'));

        $schedule->command('backup:clean')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: backup:clean'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: backup:clean'));

        // Maintenance tasks
        $schedule->command('order:cleanup-pending --hours=24')
            ->hourly()
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: order:cleanup-pending'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: order:cleanup-pending'));

        $schedule->command('session:cleanup')
            ->daily()
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: session:cleanup'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: session:cleanup'));

        $schedule->command('log:rotate --days=7')
            ->weeklyOn(0, '04:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: log:rotate'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: log:rotate'));

        $schedule->command('report:generate-daily')
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: report:generate-daily'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: report:generate-daily'));

        $schedule->command('notification:prune --days=90')
            ->weeklyOn(0, '05:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: notification:prune'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: notification:prune'));

        $schedule->command('audit:archive --months=12')
            ->monthlyOn(1, '01:00')
            ->withoutOverlapping()
            ->onSuccess(fn () => logger()->info('Scheduled task success: audit:archive'))
            ->onFailure(fn () => logger()->error('Scheduled task failed: audit:archive'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
