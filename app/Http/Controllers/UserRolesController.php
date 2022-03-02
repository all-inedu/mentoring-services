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

class UserRolesController extends Controller
{
    
    public function store(Request $request)
    {
        $rules = [
            'user_id' => 'required|integer|exists:users,id',
            'roles.*' => 'integer|exists:roles,id'
        ];

        $validator = Validator::make($request->all(), $rules);
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
