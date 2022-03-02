<?php

namespace App\Http\Controllers;

use App\Models\Permissions;
use Illuminate\Http\Request;
use App\Models\User;
use PhpParser\Node\Stmt\Switch_;

class UserAccessController extends Controller
{
    public function getUserAccess($email, $role_id)
    {
        $get_scope = Permissions::where('role_id', $role_id)->first();
        $scope = $get_scope->per_scope_access;
        
        //remove square bracket
        $scope = str_replace(array('[',']'), '', $scope);

        //remove "
        $scope = str_replace('"', '', $scope);

        //string to array
        $exp = explode(',', $scope);
        return $exp;
    }
}
