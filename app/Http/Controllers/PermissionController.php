<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function store(Request $request)
    {
        $rules = [
            'role_name'        => 'string|max:255',
            'per_scope_access' => 'required',
            'per_desc'         => 'required'
        ];

        if (isset($request->role_id)) {
            $rules['role_id'] = 'exists:roles,id';
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
