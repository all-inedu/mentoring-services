<?php

namespace App\Console\Commands;

use App\Models\GroupMeeting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class FinishGroupMeeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automated:finish_group_meeting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will check if there are group meeting that has already done, then change status to finished';

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
        $meetings = GroupMeeting::where('status', 0)->where('start_meeting_date', '<', $today)->whereHas('group_project', function($query) {
            $query->where('status', 'in progress');
        })->get();

        DB::beginTransaction();
        try {
            foreach ($meetings as $meeting) {
                $meeting->update(['status' => 1]);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::channel('finish_group_meeting')->error('Finish Group Meeting Issue : '.$e->getMessage());
        }

        Log::channel('finish_group_meeting')->info('There are '.count($meetings).' group meeting finished');
        return count($meetings) > 0 ? 1 : 0;
    }
}
