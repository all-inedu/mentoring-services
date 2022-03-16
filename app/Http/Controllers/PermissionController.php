<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\Role;
use App\Models\UserRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{

    public function select($role_id)
    {
        try {
            $permission = Permissions::with('roles')->where('role_id', $role_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Role Use Permission Issue : ['.$role_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select role by role Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $permission]);
    }

    public function index()
    {
        $roles = Role::orderBy('created_at', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $roles]);
    }

    public function update($per_id, Request $request)
    {
        $rules = [
            'per_id'           => 'required|exists:permissions,id',
            'role_id'          => 'required|exists:roles,id|unique:permissions,role_id,'.$per_id,
            'role_name'        => 'required|string|max:255',
            'status'           => 'required|in:active,inactive',
            'per_scope_access' => 'required|unique:permissions,per_scope_access,'.$per_id,
            'per_desc'         => 'required'
        ];

        $validator = Validator::make(['per_id' => $per_id] + $request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // check if the role has already been assigned to user
        if ($request->status == "inactive") {
            if (UserRoles::where('role_id', $request->role_id)->get()) {
                return response()->json(['success' => false, 'error' => 'You cannot deactivate the role because already been assigned to user']);
            }
        }

        DB::beginTransaction();
        try {

            $role = Role::find($request->role_id);
            $role->role_name = $request->role_name;
            $role->status = $request->status;
            $role->save();

            $permission = Permissions::find($per_id);
            $permission->role_id = $request->role_id;
            $permission->per_scope_access = $request->per_scope_access;
            $permission->per_desc = $request->per_desc;
            $permission->save();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Update Scope Access Issue : ['.$request->role_name.', '.$request->per_scope_access.', '.$request->per_desc.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update permission. Please try again.']);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Permission has been updated']);
    }

    public function store(Request $request)
    {
        $rules = [
            'role_name'        => 'required|string|max:255|unique:roles,role_name',
            'per_scope_access' => 'required|unique:permissions,per_scope_access',
            'per_desc'         => 'required'
        ];

        if (isset($request->role_id)) {
            $rules['role_id'] = 'exists:roles,id|unique:permissions,role_id';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        if (Role::where('role_name', $request->role_name)->count() > 0) {
            return response()->json(['success' => false, 'error' => 'Role name you submitted already exist'], 400);
        }

        DB::beginTransaction();

        try {

            $role = new Role;
            $role->role_name = $request->role_name;
            $role->save();

            $permission = new Permissions;
            $permission->role_id = isset($request->role_id) ? $request->role_id : $role->id;
            $permission->per_scope_access = $request->per_scope_access;
            $permission->per_desc = $request->per_desc;
            $permission->save();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Scope Access Issue : ['.$request->role_name.', '.$request->per_scope_access.', '.$request->per_desc.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new permission. Please try again.']);

        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'New permission has been made']);
    }
}
