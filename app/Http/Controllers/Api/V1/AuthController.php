<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function emailVerificationHandler(EmailVerificationRequest $request)
    {
        $request->fulfill();
        $response = array(
            "success" => true
        );
        return response()->json($response, 200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $rules = [
            'email' => 'required|email',
            'password' => 'required',
        ];

        $validator = Validator::make($credentials, $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        $credentials['is_verified'] = 1;

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'We can\'t find an account with this credentials. Please make sure you entered the right information and you have verified your email address'], 400);
            }
        } catch (JWTException $e) {
            // something went wrong while attempting to encode the token
            return response()->json(['error' => 'Failed to login, please try again.'], 500);
        }

        // $role_id = $credentials['role_id'];
        // $is_verified = $credentials['is_verified'];
        return response()->json(['success' => true, 'data' => ['token' => $token]], 200);
    }

    public function logout(Request $request)
    {
        $this->validate($request, ['token' => 'required']);
        
        try {
            JWTAuth::invalidate($request->input('token'));
            return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'birthday'     => 'required',
            'phone_number' => 'required|numeric',
            'role_id'      => 'required|integer',
            'email'        => 'required|string|email|max:255|unique:users',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {

            return response()->json(['error' => $validator->errors()], 400);
        }

        $name = $request->first_name.' '.$request->last_name;
        $email = $request->email;
        $password = $request->password;

        $user = User::create([
            'first_name'   => $request->get('first_name'),
            'last_name'    => $request->get('last_name'),
            'birthday'     => $request->get('birthday'),
            'phone_number' => $request->get('phone_number'),
            'role_id'      => $request->get('role_id'),
            'email'        => $request->get('email'),
            'password'     => Hash::make($request->get('password')),
        ]);

        $token = JWTAuth::fromUser($user);

        //! Generate verification Code
        $verification_code = rand(1000, 9999);

        DB::table('user_verifications')->insert([
            'user_id' => $user->id,
            'token' => $verification_code,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $subject = "Please verify your email address.";
        Mail::send('email.verify', ['name' => $name, 'verification_code' => $verification_code],
            function($mail) use ($email, $name, $subject) {
                $mail->from(getenv('FROM_EMAIL_ADDRESS'), "no-reply@all-inedu.com");
                $mail->to($email, $name);
                $mail->subject($subject);
            });

        return response()->json(compact('user', 'token'), 201);
    }

    public function getAuthenticatedUser()
    {
        try {

            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }
}
