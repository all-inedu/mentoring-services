<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('automated:synchronize_student')->everyThirtyMinutes();
        $schedule->command('automated:synchronize_mentor')->everyThirtyMinutes();
        $schedule->command('automated:synchronize_editor')->everyThirtyMinutes();
        $schedule->command('automated:synchronize_alumni')->everyThirtyMinutes();
        $schedule->command('automated:send_error_report')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
