<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentActivities;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class FinishPersonalMeeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:finish_personal_meeting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will check if there are meeting that has already done, then change status to finished';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = date('Y-m-d');
        $meeting = StudentActivities::whereHas('programmes', function ($query) {
                $query->where('prog_name', '1-on-1-call');
            })->where(function($query) {
                $query->where('std_act_status', 'confirmed')->orWhere('mt_confirm_status', 'confirmed');
            })->where('call_status', 'waiting')->
            where('call_date', '>', $today)->get();

        DB::beginTransaction();
        try {
            foreach ($meeting as $meeting_info) {
                $meeting_info->update(['call_status' => 'finished']);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('finish_meeting')->error('Finish Personal Meeting Issue : '.$e->getMessage());
        }

        if (count($meeting) > 0)
            Log::channel('finish_meeting')->info('There are '.count($meeting).' personal meeting finished');
            
        return count($meeting) > 0 ? 1 : 0;
    }
}
