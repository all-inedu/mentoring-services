<?php

namespace App\Console\Commands;

use App\Models\StudentActivities;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class CancelPersonalMeeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:cancel_personal_meeting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will check if there are meeting that has not confirmed until the call date, then change status to canceled';

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
                $query->where('std_act_status', 'waiting')->orWhere('mt_confirm_status', 'waiting');
            })->where('call_status', 'waiting')->
            where('end_call_date', '<', $today)->get();

        DB::beginTransaction();
        try {
            foreach ($meeting as $meeting_info) {
                $meeting_info->update(['call_status' => 'canceled']);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('cancel_meeting')->error('Cancel Personal Meeting Issue : '.$e->getMessage());
        }

        Log::channel('cancel_meeting')->info('There are '.count($meeting).' personal meeting canceled');
        return count($meeting) > 0 ? 1 : 0;
    }
}
