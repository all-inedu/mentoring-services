<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Students;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\MailLogController;

class VerificationController extends Controller
{
    public function verifying($verification_code)
    {
        $check = Verification::where('token', $verification_code)->first();

        if (is_null($check)) {
            return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
        }

        DB::beginTransaction();

        if(!is_null($check)){
            $student = Students::find($check->registrant);

            if($student->is_verified == true){
                return response()->json([
                    'success'=> true,
                    'message'=> 'Account already verified.'
                ]);
            }

            try {
                $student->email_verified_at = Carbon::now();
                $student->is_verified = true;
                $student->save();

                Verification::where(['registrant' => $student->id, 'token' => $verification_code])->delete();

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Verifiy Issue : ['.$student->id.' with token '.$verification_code.'] '.$e->getMessage());
            }
            DB::commit();

            return response()->json([
                'success'=> true,
                'message'=> 'You have successfully verified your email address.'
            ]);
        }
    }

    public function resendVerificationCode()
    {
        if (!Auth::guard('student')->check()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized User'
            ], 400);
        }

        $student = Auth::guard('student')->user();
        $id      = $student->id;
        $name    = $student->first_name." ".$student->last_name;
        $email   = $student->email;
        $is_verified = $student->is_verified;

        if ($is_verified == true) {
            return response()->json(['success' => false, 'error' => 'Your account has been verified']);
        }
        
        try {
            // //! Generate verification Code
            $verification_code = rand(1000, 9999);
            // 1. delete the verification token that has been sent 
            Verification::where('registrant', $id)->delete(); 
            // 2. save new verification token 
            Verification::create([ 
                'registrant' => $id,
                'token' => $verification_code,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            $subject = "Please verify your email address.";
            Mail::send('templates.mail.verify', ['name' => $name, 'verification_code' => $verification_code],
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
                'status'    => count(Mail::failures() == 0) ? "delivered" : "not delivered",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            );
    
            $save_log = new MailLogController;
            $save_log->saveLogMail($log);
            
        } catch (Exception $e) {
            Log::error('Resend Verification Code Issue : ['.$id.' '.$name.'] '.$e->getMessage());
            return response()->json(['error' => 'Failed to resend the verification code. Please try again.']);
        }
    
        return response()->json(['success' => true, 'message' => 'Verification link sent']);
    }
}
