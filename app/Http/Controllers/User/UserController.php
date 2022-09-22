<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Verification;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\MailLogController;
use App\Http\Controllers\Student\AuthController;
use App\Http\Controllers\UserAccessController;
use App\Models\UserRoles;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller
{

    private $ADMIN_LIST_USER_VIEW_PER_PAGE;
    protected $USER_STORE_PROFILE_PATH;

    public function __construct()
    {
        $this->USER_STORE_PROFILE_PATH = RouteServiceProvider::USER_STORE_PROFILE_PATH;
        $this->ADMIN_LIST_USER_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_USER_VIEW_PER_PAGE;
    }

    public function check_token()
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'error' => 'You have to log in first']);
        }
    }

    public function find($role_name, $id = null, $detail = null, Request $request)
    {
        $rules = [
            'role_name' => 'required|in:admin,mentor,editor,alumni'
        ];

        $validator = Validator::make(['role_name' => $role_name], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        //find by Id
        if ($id) { 
            $users = User::with('educations', 'social_media')->find($id);
            return response()->json(['success' => true, 'data' => $users]);

        }

        //find by keyword
        $keyword = $request->get('keyword');

        try {
            $users = User::where(function($query) use ($keyword) {
                $query->where(DB::raw("CONCAT(`first_name`, ' ', `last_name`)"), 'like', '%'.$keyword.'%')->orWhere('email', 'like', '%'.$keyword.'%');
            })->whereHas('roles', function($query) use ($role_name) {
                $query->where('role_name', $role_name);
            })->paginate($this->ADMIN_LIST_USER_VIEW_PER_PAGE);
        } catch (Exception $e) {
            Log::error('Find User by Keyword Issue : ['.$keyword.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find user by Keyword. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function update (Request $request)
    {
        $rules = [
            'id'              => 'required|exists:users,id',
            'user_type'       => 'required|in:student,user',
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'phone_number'    => 'required|numeric',
            'profile_picture' => 'nullable|max:3000',
            'address'         => 'nullable'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if ($request->hasFile('profile_picture')) {
            $pp_name = date('Ymd_His').'_'.str_replace(' ', '-', $request->first_name.'-'.$request->last_name);
            $pp_format = $request->file('profile_picture')->getClientOriginalExtension();
            $pp_file_path = $request->file('profile_picture')->storeAs($this->USER_STORE_PROFILE_PATH.'/'.$request->id, $pp_name.'.'.$pp_format, ['disk' => 'student_files']);
        }

        DB::beginTransaction();
        try {
            if ($request->user_type == "user") {
                $user = User::find($request->id);
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->phone_number = $request->phone_number;
                $user->profile_picture = $pp_file_path;
                $user->address = $request->address;
                $user->save();
            }
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update User Data Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update user data. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Profile has been updated', 'data' => $user]);
    }

    //! harus ditambahin status dan pagination
    public function index($role_name = 'all', $status = 'all', $use_paginate = "yes")
    {   
        $role_name = strtolower($role_name);
        $user = User::whereHas('roles', function($query) use ($role_name) {
            $query->when($role_name != 'all', function($q) use ($role_name) {
                
                // if ($role_name == 'mentor') {
                //     $q->where('role_name', $role_name)->with('educations');
                // } else {
                    $q->where('role_name', $role_name);
                // }
            });
        })->when($status != 'all', function ($q) use ($status) {
            $q->where('status', $status);
        })->orderBy('created_at', 'desc')->customPaginate($use_paginate, $this->ADMIN_LIST_USER_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function resendVerificationCode()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized User'
            ], 400);
        }

        $user = Auth::user();
        $id      = $user->id;
        $name    = $user->first_name." ".$user->last_name;
        $email   = $user->email;
        $is_verified = $user->is_verified;

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
                'updated_at' => Carbon::now()
            );
    
            $save_log = new MailLogController;
            $save_log->saveLogMail($log);
        } catch (Exception $e) {
            Log::error('Resend Verification Code Issue : ['.$id.' '.$name.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to resend the verification code. Please try again.']);
        }
    
        return response()->json(['success' => true, 'message' => 'Verification link sent']);
    }

    public function authenticate(Request $request)
    {
        //if user (mentor/editor/alumni) hasn't setup the password
        //or never login before
        //do this
        $check = User::where('email', $request->email)->where('password', NULL)->first();
        if ($check) {
            $token = Crypt::encrypt(array(
                'user_id' => $check->id,
                'email' => $check->email,
            ));
            return response()->json(['success' => true, 'code' => true, 'message' => 'You need to set a password', 'token' => $token]);
        }

        // authenticate function below
        $credentials = $request->only('email', 'password');

        //Error messages
        $messages = [
            "email.exists" => "Email doesn't exists"
        ];

        $rules = [
            'email' => 'required|email|exists:users,email',
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
            echo $e->getMessage();
            Log::error('Login Issue : ['.$request->email.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }

        $currentUser = Auth::user();
        if ($currentUser->is_verified == false) {
            return response()->json(['success' => false, 'error' => 'Please verify your account first'], 400);
        }

        if (!$currentUser->roles) {
            return response()->json(['success' => false, 'error' => 'You don\'t have permission to login. Please contact your Administrator.']);
        }

        //** create role access token */
        foreach ($currentUser->roles as $data) {
            $permission = $data->permissions;
            $scope[] = $permission->per_scope_access;

            //if 1 role has many permission
            // foreach ($permission as $value) {
            //     $scope[] = $value->per_scope_access;
            // }
        }
        
        $scope = str_replace(array('[',']'), "", str_replace('"', '', array_unique($scope)));
        $user_scope_access = $scope;
        //** create role access token end */
        
        // $get_user_access = new UserAccessController;
        // $email = $currentUser->email;
        // $role_id = $currentUser->role_id;
        // $user_scope_access = $get_user_access->getUserAccess($email, $role_id);

        if (!$token = $currentUser->createToken('User Token', $user_scope_access)->accessToken) {
            return response()->json(['success' => false, 'error' => 'Failed to generate token']);
        }

        return response()->json([
            'success' => true,
            'code' => false,
            'message' => 'Login Successfully',
            'data' => array(
                'user' => $currentUser, //! info ke hafidz kalau disini berubah
                'access_token' => $token
            )
        ]);
    }

    public function verifying($verification_code)
    {
        $check = Verification::where('token', $verification_code)->first();

        if (is_null($check)) {
            return response()->json(['success'=> false, 'error'=> "Verification code is invalid."]);
        }

        DB::beginTransaction();

        if(!is_null($check)){
            $user = User::find($check->registrant);

            if($user->is_verified == true){
                return response()->json([
                    'success'=> true,
                    'message'=> 'Account already verified.'
                ]);
            }

            try {
                $user->email_verified_at = Carbon::now();
                $user->is_verified = true;
                $user->save();

                Verification::where(['registrant' => $user->id, 'token' => $verification_code])->delete();

            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Verifiy Issue : ['.$user->id.' with token '.$verification_code.'] '.$e->getMessage());
            }
            DB::commit();

            return response()->json([
                'success'=> true,
                'message'=> 'You have successfully verified your email address.'
            ]);
        }
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'phone_number' => 'required|numeric',
            'role_id'      => 'required|exists:roles,id',
            'email'        => 'required|string|email|max:255|unique:students',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 422);
        }
        
        $name = $request->first_name.' '.$request->last_name;
        $email = $request->email;
        $password = $request->password;
        
        //! changed the first number *0* to *62*
        $phone_number = $request->get('phone_number');
        if ( substr($phone_number, 0, 1) == 0) {
            $phone_number = "62".substr($phone_number, 1);
        }

        DB::beginTransaction();
        try {

            $user = User::create([
                'first_name'   => $request->get('first_name'),
                'last_name'    => $request->get('last_name'),
                'phone_number' => $phone_number,
                'role_id'      => $request->get('role_id'),
                'email'        => $request->get('email'),
                'password'     => Hash::make($password),
            ]);
    
            //! Generate verification Code
            $verification_code = rand(1000, 9999);
    
            Verification::create([
                'registrant' => $user->id,
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
                'status'    => count(Mail::failures()) == 0 ? "delivered" : "not delivered",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            );
    
            $save_log = new MailLogController;
            $save_log->saveLogMail($log);
                
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Register Issue : ['.$request->get('first_name').' '.$request->get('last_name').'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to register. Please try again.'], 400);

        }
        
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Successfuly Registered'
        ], 200);
        
    }

    public function store_new_password($user_type, Request $request)
    {
        if ($user_type == "student") {
            $student_auth = new AuthController;
            return $student_auth->store_new_password($request);
        }

        $data = Crypt::decrypt($request->token);
        $user_id = $data['user_id'];
        $email = $data['email'];

        if (!$user = User::where('id', $user_id)->where('email', $email)->where('password', NULL)->first()) {
            return response()->json(['success' => false, 'error' => 'Token is not valid']);
        }

        $rules = [
            'token' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 401);
        }

        DB::beginTransaction();
        try {
            $user->password = Hash::make($request->password);
            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Store new password : ['.$email.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to store new password'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Password has been updated']);
        
    }
}
