<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\UserRoles;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserRolesController extends Controller
{

    public function delete($user_role_id)
    {
        //Validation
        if (!$user_role = UserRoles::find($user_role_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find user role. Please try again.'], 400);
        } 

        $user = User::find($user_role->user_id);
        $user_name = $user->first_name.' '.$user->last_name;

        $roles = Role::find($user_role->role_id);
        $role_name = $roles->role_name;

        DB::beginTransaction();
        try {
            $user_role->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete User Role Issue : ['.$user_role_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete user role. Please try again.']);
        }
        return response()->json(['success' => true, 'message' => 'You\'re successfully removed role '.$role_name.' from '.$user_name]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'roles'   => 'required|array|min:1',
            'roles.*' => [
                    Rule::exists('roles', 'id')->where(function($query) {
                        return $query->where('status', 'active');
                    }),
                    'unique:user_roles,role_id'
                ]
        ];

        if ((!$request->roles) || (in_array(null, $request->roles)) ) {
            return response()->json(['success' => false, 'error' => ['roles' => 'The role field is required']]);
        }

        $custom_message = [];
        foreach ($request->roles as $key => $value) {
            $roles = Role::find($value);
            $role_name[] = $roles->role_name;

            $custom_message += [
                'roles.'.$key.'.unique' => 'The '.$roles->role_name.' has already been taken.',
                'roles.'.$key.'.exists' => 'The selected roles : '.$roles->role_name.' is invalid.'
            ];
        }
        

        $validator = Validator::make($request->all(), $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {

            $user = User::find($request->user_id);
            $user_name = $user->first_name.' '.$user->last_name;

            foreach ($request->roles as $key => $value) {
                $roles = Role::find($value);
                $role_name[] = $roles->role_name;

                $user_role = new UserRoles;
                $user_role->user_id = $request->user_id;
                $user_role->role_id = $value;
                $user_role->save();
            }
            DB::commit();
            
        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Assigning Role Issue : ['.implode(", ", $role_name).' to '.$user_name.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to assign a role '.implode(", ", $role_name).' to '.$user_name.'. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'You\'re successfully assigned role '.implode(", ", $role_name).' to '.$user_name]);
    }
}
