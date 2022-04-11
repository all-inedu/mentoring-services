<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EssayController extends Controller
{
    
    public function count_essay()
    {
        $auth = Auth::user();
        $email = $auth->email;
        $position = $auth->position;

        switch ($position) {
            default:
                $incoming = $waiting = [];
                $ongoing = [2, 3, 6, 8];
                $complete = [7];
                break;
            case "3":
                $incoming = [0, 4, 5];
                $waiting = [1];
                $ongoing = [2, 3, 6, 8];
                $complete = [7];
                break;
        }

        $incoming = DB::connection('mysql_edt')->table('tbl_essay_editors')->where('editors_mail', $email)->whereIn('status_essay_editors', $incoming)->count();
        $waiting = DB::connection('mysql_edt')->table('tbl_essay_editors')->where('editors_mail', $email)->whereIn('status_essay_editors', $waiting)->count();
        $ongoing = DB::connection('mysql_edt')->table('tbl_essay_editors')->where('editors_mail', $email)->whereIn('status_essay_editors', $ongoing)->count();
        $complete = DB::connection('mysql_edt')->table('tbl_essay_editors')->where('editors_mail', $email)->whereIn('status_essay_editors', $complete)->count();

        $data = $position == "3" ? compact('incoming', 'waiting', 'ongoing', 'complete') : compact('ongoing', 'complete');

        return response()->json(['success' => true, 'data' => $data]);
    }
}
