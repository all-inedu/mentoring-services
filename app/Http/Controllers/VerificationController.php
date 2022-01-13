<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Exception;
use Illuminate\Database\QueryException;
use JWTAuth;
use Illuminate\Support\Facades\Mail;

class VerificationController extends Controller
{
    public function verifyUser($verification_code)
    {
        $check = DB::table('user_verifications')->where('token',$verification_code)->first();

        if(!is_null($check)){
            $user = User::find($check->user_id);

            if($user->is_verified == 1){
                return response()->json([
                    'success'=> true,
                    'message'=> 'Account already verified.'
                ]);
            }

            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->is_verified = 1;
            $user->save();

            DB::table('user_verifications')->where('token',$verification_code)->delete();

            return response()->json([
                'success'=> true,
                'message'=> 'You have successfully verified your email address.'
            ]);
        }

        return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
    }

    public function verificationNotification()
    {
        if (!$user = JWTAuth::parseToken()->authenticate()) {
            return response()->json(['user_not_found'], 404);
        }

        $user_id = $user->id;
        $name = $user->first_name." ".$user->last_name;
        $email = $user->email;
        
        //! Generate verification Code
        $verification_code = rand(1000, 9999);

        DB::table('user_verifications')->select("*")->where('user_id', $user_id)->delete(); // 1. delete the verification token that has been sent 

        DB::table('user_verifications')->insert([
            'user_id' => $user->id,
            'token' => $verification_code,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]); // 2. save new verification token 

        try {
            $subject = "Please verify your email address.";
            Mail::send('email.verify', ['name' => $name, 'verification_code' => $verification_code],
                function($mail) use ($email, $name, $subject) {
                    $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                    $mail->to($email, $name);
                    $mail->subject($subject);
                });
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    
        return response()->json(['success' => true, 'message' => 'Verification link sent!']);
    }
}
