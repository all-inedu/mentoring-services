<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ResetPasswordController extends Controller
{
    public function sendResetPasswordLink(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email|exists:users']);

        if ($validator->fails()) {

            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $error_message = "Your email address was not found.";
            return response()->json(['success' => false, 'error' => ['email'=> $error_message]], 401);
        }

        try {
            $token = Str::random(64);
  
            DB::table('password_resets')->insert([
                'email' => $request->email, 
                'token' => $token, 
                'created_at' => Carbon::now()
                ]);

            Mail::send('email.forgetPassword', ['token' => $token], function($message) use($request){
                $message->to($request->email);
                $message->subject('Your Reset Password Link');
            });

        } catch (\Exception $e) {
            //Return with error
            $error_message = $e->getMessage();
            return response()->json(['success' => false, 'error' => $error_message], 401);
        }

        return response()->json([
            'success' => true, 'data'=> ['message'=> 'We have e-mailed your password reset link!']
        ]);
    }

    public function handleResetPassword(Request $request)
    {
        $token = $request->token;
        $get = DB::table('password_resets')->where('token', $token)->first();
        if(!$get) {
            return response()->json(['success' => false, 'error', 'Invalid token!']);
        }

        return response()->json(['success' => true, 'data' => ['email' => $get->email]]);
    }

    public function submitResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        if ($validator->fails()) {

            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $updatePassword = DB::table('password_resets')
                            ->where([
                              'email' => $request->email, 
                              'token' => $request->token
                            ])
                            ->first();

        if(!$updatePassword){
            return response()->json(['success' => false, 'error' => 'Invalid token!']);
        }

        User::where('email', $request->email)
                    ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email'=> $request->email])->delete();

        return response()->json(['success' => true, 'data' => ['message' => 'Your password has been changed!']]);
    }
}
