<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Students;
use Illuminate\Support\Facades\Hash;
use App\Models\Verification;
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Carbon;
use App\Http\Controllers\MailLogController;

class AuthController extends Controller
{
    private $system_name;

    public function __construct()
    {
        $this->system_name = RouteServiceProvider::SYSTEM_NAME;
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //Error messages
        $messages = [
            "email.exists" => "Email doesn't exists"
        ];

        $rules = [
            'email' => 'required|email|exists:students',
            'password' => 'required|min:6',
        ];

        $validator = Validator::make($credentials, $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        try {
            // attempt to verify the credentials and create a token for the user
            if (!Auth::guard('student')->attempt($credentials)) {
                return response()->json(['success' => false, 'error' => 'Wrong password'], 400);
            }
        } catch (Exception $e) {
            // something went wrong while attempting to encode the token
            Log::error('Login Issue : ['.$request->email.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }

        $currentStudent = Auth::guard('student')->user();
        if ($currentStudent->is_verified == false) {
            return response()->json(['success' => false, 'error' => 'Please verify your account first'], 400);
        }

        if (!$token = $currentStudent->createToken('Student Token', ['student'])->accessToken) {
            return response()->json(['success' => false, 'error' => 'Failed to generate token']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login Successfully',
            'data' => array(
                'student' => $currentStudent,
                'access_token' => $token
            )
        ]);
    }

    public function register(Request $request)
    {
        $is_error = false;
        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthday'     => 'required',
            'phone_number' => 'required|string',
            'grade'        => 'required|integer|min:7',
            'email'        => 'required|string|email|max:255|unique:students',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 422);
        }
        
        $name = $request->first_name.' '.$request->last_name;
        $email = $request->email;
        $password = $request->password;

        DB::beginTransaction();
        try {

            $student = Students::create([
                'first_name'   => $request->get('first_name'),
                'last_name'    => $request->get('last_name'),
                'birthday'     => $request->get('birthday'),
                'phone_number' => $request->get('phone_number'),
                'grade'        => $request->get('grade'),
                'email'        => $request->get('email'),
                'password'     => Hash::make($password),
            ]);
    
            //! Generate verification Code
            $verification_code = rand(1000, 9999);
    
            Verification::create([
                'registrant' => $student->id,
                'token' => $verification_code,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            // $subject = "Please verify your email address.";
            // Mail::send('templates.mail.verify', ['name' => $name, 'verification_code' => $verification_code],
            //     function($mail) use ($email, $name, $subject) {
            //         $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            //         $mail->to($email, $name);
            //         $mail->subject($subject);
            //     });

            // $log = array(
            //     'sender'    => 'system',
            //     'recipient' => $email,
            //     'subject'   => $subject,
            //     'message'   => NULL,
            //     'date_sent' => Carbon::now(),
            //     'status'    => Mail::failures() ? "delivered" : "not delivered"
            // );
    
            // $save_log = new MailLogController;
            // $mailLog = $save_log->saveLogMail($log);
            
        } catch (Exception $e) {

            $is_error = true;
            DB::rollBack();
            Log::error('Register Issue : ['.$request->get('first_name').' '.$request->get('last_name').'] '.$e->getMessage());

        }
        
        DB::commit();

        $subject = "Please verify your email address.";
        $log = array(
            'sender'    => 'system',
            'recipient' => $email,
            'subject'   => $subject,
            'message'   => NULL,
            'date_sent' => Carbon::now(),
            'status'    => 'not delivered' /*Mail::failures() ? "delivered" : "not delivered"*/
        );

        $mail_log = new MailLogController;
        $mail_log->saveLogMail($log);

        try {
            Mail::send('templates.mail.verify', ['name' => $name, 'verification_code' => $verification_code],
                function($mail) use ($email, $name, $subject) {
                    $mail->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                    $mail->to($email, $name);
                    $mail->subject($subject);
                });

            $mail_log = MailLog::where('recipient', $email)->first();
            $mail_log->status = 'delivered';
            $mail_log->save();

        } catch (Exception $e) {
            $mail_log->record_error_message($email, $e->getMessage());
            $mail_log->mail_to_tech($subject, $email, $e->getMessage());
        }

        if ($is_error === true) {
            return response()->json(['success' => false, 'error' => 'Failed to register. Please try again.'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfuly Registered'
        ], 200);
        
    }
}
