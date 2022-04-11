<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;

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
    public function mail_to_tech($subject, $email, $error)
    {
        Mail::send([], [], function($mail) use ($subject, $email, $error) {
            $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->to($this->tech_mail);
            $mail->subject('Error Sending Messages');
            $mail->setBody('<p>Dear IT Team</p>
                            <br>
                            <p></p>
                        ', 'text/html');
        });

        return Mail::failures() ? true : false;
    }
}
