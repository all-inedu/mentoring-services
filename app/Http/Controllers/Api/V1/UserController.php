<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\User;
class UserController extends Controller
{
    public function user($id)
    {
        if ($id == "all") {

            $data = User::all();

        } else {
            
            $data = User::where('id', $id)->first();

        }
        return response()->json($data, 200);
    }

    public function userAuth()
    {
        $data = "Welcome " . Auth::user()->first_name;
        return response()->json($data, 200);
    }
}
