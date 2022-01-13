<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    private $system_name;

    public function __construct()
    {
        $this->system_name = RouteServiceProvider::SYSTEM_NAME;
    }


    public function redirect($provider)
    {
        if ($provider == "apple") {
            $response = Http::get('https://appleid.apple.com/auth/keys');
            return response()->json($response);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(Request $request)
    {
        // Socialite::driver('linkedin')->stateless()->user();
        // Socialite::driver('google')->stateless()->user();

        $provider = $request->provider;
        $userSocial =   Socialite::driver($provider)->user();

        $users = User::where(['email' => $userSocial->getEmail()])->first();
        if ($users) {
            Auth::login($users);
        } else {
            $socialName = explode(" ", $userSocial->getName());

            $users = User::create([
                        'first_name'        => $socialName[0],
                        'last_name'         => $socialName[1],
                        'role_id'           => 1,
                        'email'             => $userSocial->getEmail(),
                        'email_verified_at' => Carbon::now(),
                        'image'             => $userSocial->getAvatar(),
                        'provider'          => $provider,
                        'provider_id'       => $userSocial->getId()
                    ]);
        }

        $state = $request->get('state');
        $request->session()->put('state',$state);

        if(Auth::check()==false){
          session()->regenerate();
        }
        
        return response()->json([
            'success' => true,
            'data' => array(
                'users' => $users,
                'token' => $users->createToken($this->system_name)->accessToken
            )
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //Error messages
        $messages = [
            "email.exists" => "Email doesn't exists"
        ];

        $rules = [
            'email' => 'required|email|exists:users',
            'password' => 'required|min:6',
        ];

        $validator = Validator::make($credentials, $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        try {
            // attempt to verify the credentials and create a token for the user
            if (!Auth::attempt($credentials)) {
                return response()->json(['success' => false, 'error' => 'Wrong password'], 400);
            }
        } catch (Exception $e) {
            // something went wrong while attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }

        $currentUser = Auth::user();
        $role_id = $currentUser->role_id;
        $is_verified = $currentUser->is_verified;

        if (!$token = $currentUser->createToken($this->system_name)->accessToken) {
            return response()->json(['success' => false, 'error' => 'Failed to generate token']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfuly Login',
            'data' => array(
                'token'       => $token,
                'role_id'     => $role_id,
                'is_verified' => $is_verified
            )
        ]);
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
            return response()->json(['success' => false, 'error' => $validator->errors()], 422);
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

        return response()->json([
            'success' => true,
            'message' => 'Successfuly Registered'
        ], 200);
        
    }

    public function profile()
    {
        $user = Auth::user();
        $user = $user->makeHidden(['email_verified_at','password','remember_token']);

        $response['status'] = true;
        $response['message'] = 'User login profil';
        $response['data'] = $user;

        return response()->json($response, 200);
    }

    public function logout(Request $request)
    {
        $validator = Validator::make($request->all(), ['token' => 'required']);
        if ($validator->fails()) {

            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        
        try {
            $request->user()->token()->revoke();
            return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
        } catch (Exception $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }
}
