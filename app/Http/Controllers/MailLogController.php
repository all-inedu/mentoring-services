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
use Illuminate\Support\Facades\Validator;

class MailLogController extends Controller
{

    protected $tech_mail;
    protected $day_range_error_report;

    public function __construct()
    {
        $this->tech_mail = RouteServiceProvider::TECH_MAIL_1;
        $this->day_range_error_report = RouteServiceProvider::DAY_RANGE_ERROR_REPORT;
    }

    public function index($param, Request $request) //error & success
    {
        $use_keyword = $request->get('keyword') != null ? 1 : 0;
        $keyword = $request->get('keyword') != null ? $request->get('keyword') : null;

        $rules = [
            'param' => 'in:success,error'
        ];

        $validator = Validator::make(['param' => $param], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $mailLog = MailLog::when($param == 'success', function($query) use ($use_keyword, $keyword){
            $query->where(function($query1) {
                $query1->where('status', 'delivered')->orWhere(function($query2) {
                    $query2->where('status', 'not delivered')->where('error_status', 'solved');
                });
            })->when($use_keyword, function($query1) use ($keyword) {
                $query1->where(function ($query2) use ($keyword) {
                    $query2->where('recipient', 'like', '%'.$keyword.'%')->
                            orWhere('sender', 'like', '%'.$keyword.'%')->
                            orWhere('subject', 'like', '%'.$keyword.'%')->
                            orWhere('message', 'like', '%'.$keyword.'%')->
                            orWhere('date_sent', 'like', '%'.$keyword.'%');
                });
            });
        }, function($query) use ($use_keyword, $keyword) {
            $query->where(function($query1) {
                $query1->where('status', 'not delivered')->whereNull('error_status');
            })->when($use_keyword, function($query1) use ($keyword) {
                $query1->where(function ($query2) use ($keyword) {
                    $query2->where('recipient', 'like', '%'.$keyword.'%')->
                            orWhere('sender', 'like', '%'.$keyword.'%')->
                            orWhere('subject', 'like', '%'.$keyword.'%')->
                            orWhere('message', 'like', '%'.$keyword.'%')->
                            orWhere('date_sent', 'like', '%'.$keyword.'%');
                });
            });
        })->orderBy('date_sent', 'desc')->paginate(10);

        $response = $use_keyword ? $mailLog->appends(array('keyword' => $keyword)) : $mailLog;
        return response()->json(['succes' => true, 'data' => $response]);
    }

    public function saveLogMail($log)
    {
        $new_log = new MailLog;
        $new_log->recipient = $log['recipient'];
        $new_log->sender = $log['sender'];
        $new_log->subject = $log['subject'];
        $new_log->message = $log['message'];
        $new_log->date_sent = $log['date_sent'];
        $new_log->status = $log['status'];
        $new_log->error_message = array_key_exists('error_message', $log) ? $log['error_message'] : NULL;
        return $new_log->save();
    }

    public function record_error_message($email, $error_message)
    {
        $mailLog = MailLog::where('recipient', $email)->first();
        $mailLog->error_message = $error_message;
        return $mailLog->save();
    }

    //** function to send email to IT email if there are errors when sending mail */
    public function mail_to_tech()
    {
        $today = date('Y-m-d');
        $next_day = date('Y-m-d', strtotime("+ ".$this->day_range_error_report." days"));

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
