<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Students;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\MailLogController;

class ForgotPasswordController extends Controller
{

    private $reset_password_link;

    public function __construct()
    {
        $this->reset_password_link = RouteServiceProvider::RESET_PASSWORD_LINK;
    }

    public function ResetPassword($token, Request $request)
    {
        $validator = Validator::make($request->all() + ['token' => $token], [
            'token' => 'required|exists:password_resets,token',
            'email' => 'required|exists:password_resets,email',
            'password' => 'required|string|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        // $token = $request->get('token');
        $email = $request->email;
        $new_password = $request->password;

        DB::beginTransaction();
        try {

            $student = Students::where('email', $email)->firstOrFail();
            $student->password = Hash::make($new_password);
            $student->save();

            DB::table('password_resets')->where('email', $email)->where('token', $token)->delete();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Reset Password Issue : ['.$request->email.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to reset your password. Please try again.']);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Your password has been reset.']);
    }

    public function sendResetPasswordLink(Request $request)
    {
        //Error messages
        $messages = [
            "email.exists" => "Email doesn't exists"
        ];

        $rules = [
            'email' => 'required|string|email|max:255|exists:students,email'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }
        DB::beginTransaction();
        // Create Password Reset Token
        $token = Str::random(60);
        DB::table('password_resets')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now()
        ]);

        $email = $request->email;
        $student = Students::where('email', $email)->first();
        $name = $student->first_name.' '.$student->last_name;

        $generated_link = $this->reset_password_link.$token; //? needs to be change

        try {
            $subject = "Your Reset Password Link";
            Mail::send('templates.mail.reset-password', ['generated_link' => $generated_link],
                function($mail) use ($email, $name, $subject) {
                    $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $mail->to($email, $name);
                    $mail->subject($subject);
                });

            $log = array(
                'sender'    => 'system',
                'recipient' => $email,
                'subject'   => $subject,
                'message'   => NULL,
                'date_sent' => Carbon::now(),
                'status'    => Mail::failures() ? "delivered" : "not delivered"
            );
    
            $save_log = new MailLogController;
            $save_log->saveLogMail($log);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Send Reset Password Link Issue : ['.$email.' '.$name.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to send reset password link. Please try again.'], 400);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'A reset link has been sent to your email address']);
    }
}
