<?php

namespace App\Http\Controllers;

use App\Models\MailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Exception;

class MailLogController extends Controller
{

    public function index()
    {
        $mailLog = MailLog::orderBy('date_sent', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $mailLog]);
    }

    public function saveLogMail($log)
    {  
        return MailLog::create($log);
    }
}
