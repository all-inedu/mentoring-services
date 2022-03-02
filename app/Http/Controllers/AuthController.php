<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Models\User;
use App\Models\Students;
use App\Models\Verification;
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

        $users = Students::where(['email' => $userSocial->getEmail()])->first();
        if ($users) {
            Auth::guard('student')->login($users);
        } else {
            $socialName = explode(" ", $userSocial->getName());

            $users = Students::create([
                        'first_name'        => $socialName[0],
                        'last_name'         => $socialName[1],
                        'email'             => $userSocial->getEmail(),
                        'email_verified_at' => Carbon::now(),
                        'image'             => $userSocial->getAvatar(),
                        'provider'          => $provider,
                        'provider_id'       => $userSocial->getId()
                    ]);
        }

        $state = $request->get('state');
        $request->session()->put('state',$state);

        if(Auth::guard('student')->check()==false){
          session()->regenerate();
        }
        
        return response()->json([
            'success' => true,
            'data' => array(
                'users' => $users,
                'token' => $users->createToken('Student Token', ['student'])->accessToken
            )
        ]);
    }

    /* NOT USED */

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
