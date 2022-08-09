<?php

namespace App\Console;

use App\Console\Commands\SendErrorReport;
use App\Console\Commands\SynchronizeAlumniFromBigData;
use App\Console\Commands\SynchronizeEditorFromBigData;
use App\Console\Commands\SynchronizeMentorFromBigData;
use App\Console\Commands\SynchronizeStudentFromBigData;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendErrorReport::class,
        SynchronizeAlumniFromBigData::class,
        SynchronizeEditorFromBigData::class,
        SynchronizeMentorFromBigData::class,
        SynchronizeStudentFromBigData::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('automated:import_big_data')->everyThirtyMinutes()->timezone('Asia/Jakarta');
        // $schedule->command('automated:synchronize_student')->everyThirtyMinutes()->timezone('Asia/Jakarta')->appendOutputTo(storage_path('logs/sync_student.log'));
        // $schedule->command('automated:synchronize_mentor')->everyThirtyMinutes()->timezone('Asia/Jakarta')->appendOutputTo(storage_path('logs/sync_mentor.log'));
        // $schedule->command('automated:synchronize_editor')->everyThirtyMinutes()->timezone('Asia/Jakarta')->appendOutputTo(storage_path('logs/sync_editor.log'));
        // $schedule->command('automated:synchronize_alumni')->everyThirtyMinutes()->timezone('Asia/Jakarta')->appendOutputTo(storage_path('logs/sync_alumni.log'));
        $schedule->command('automated:send_error_report')->daily()->timezone('Asia/Jakarta');
        $schedule->command('automated:payment_checker')->cron('* * * * *');

        $schedule->command('automated:cancel_personal_meeting')->daily()->timezone('Asia/Jakarta');
        $schedule->command('automated:finish_personal_meeting')->daily()->timezone('Asia/Jakarta');
        $schedule->command('automated:finish_group_meeting')->daily()->timezone('Asia/Jakarta');
        $schedule->command('automated:send_invitation_group_project')->cron('* * * * *');
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
