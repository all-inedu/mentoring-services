<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MailLogController extends Controller
{

    protected $tech_mail;

    public function __construct()
    {
        $this->tech_mail = RouteServiceProvider::TECH_MAIL_1;
    }

    public function index()
    {
        $mailLog = MailLog::orderBy('date_sent', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $mailLog]);
    }

    public function saveLogMail($log)
    {  
        return MailLog::create($log);
    }

    public function record_error_message($email, $error_message)
    {
        $mailLog = MailLog::where('recipient', $email)->first();
        $mailLog->error_message = $error_message;
        return $mailLog->save();
    }

    //** function to send email to IT email if there are errors when sending mail */
    public function mail_to_tech(/*$subject, $email, $error*/)
    {

        $mailLog = MailLog::where('error_status', NULL)->orderBy('date_sent', 'desc')->get();

        Mail::send('templates.mail.error-report', ['mailLog' => $mailLog], function($mail) {
            $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->to($this->tech_mail);
            $mail->subject('Daily Error Report');
        });

        return Mail::failures() ? true : false;
    }

    public function update($mail_id)
    {
        try {
            $mailLog = MailLog::find($mail_id);
        } catch (Exception $e) {
            Log::error('Find Mail by Id Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find mail by Id. Please try again.']);
        }

        DB::beginTransaction();

        try {
            $mailLog->error_status = "solved";
            $mailLog->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update error status : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update error status. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'The error has been solved']);
    }
}
