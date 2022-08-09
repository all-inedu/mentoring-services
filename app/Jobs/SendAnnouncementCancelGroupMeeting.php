<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use App\Http\Controllers\MailLogController;

class SendAnnouncementCancelGroupMeeting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $mixed_data = $this->data['mixed_data'];
        $meeting_detail = $this->data['meeting_detail'];
        for ($i = 0 ; $i < count($mixed_data); $i++) {
            $email = $mixed_data[$i]['email'];
            $name = $mixed_data[$i]['name'];
            $subject = "The meeting for group project ".$meeting_detail->group_project->project_name." schedule has been canceled";

            Mail::send('templates.mail.cancel-group-meeting-announcement', ['name' => $name, 'group_info' => $meeting_detail->group_project, 'meeting_detail' => $meeting_detail],
                function($mail) use ($email, $name, $subject) {
                    $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                    $mail->to($email, $name);
                    $mail->subject($subject);
                }); 
            
            if (count(Mail::failures()) > 0) { 
                // save to log mail admin
                // save only if failure to sent
                $log = array(
                    'sender'    => 'system',
                    'recipient' => $email,
                    'subject'   => 'Sending reminder to join group meeting',
                    'message'   => json_encode($meeting_detail),
                    'date_sent' => Carbon::now(),
                    'status'    => "not delivered",
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                );
                $save_log = new MailLogController;
                $save_log->saveLogMail($log);

                foreach (Mail::failures() as $email_address) {
                    Log::channel('group_meeting_reminder_log')->error("Sending reminder mail failures to ". $email_address);
                }
            }
        }

        Log::channel('group_meeting_reminder_log')->info('Sending announcement that there is group meeting were canceled');
    }
}
