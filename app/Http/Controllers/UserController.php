<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class UserController extends Controller
{
    public function user() {
        $data = "Data All User";
        return response()->json($data, 200);
    }

    public function userAuth() {
        $data = "Welcome ". Auth::user()->first_name;
        return response()->json($data, 200);
    }
}
