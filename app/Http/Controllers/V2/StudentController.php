<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Students;

class StudentController extends Controller
{
    public function index($student_id = NULL, Request $request)
    {
        $keyword = $request->get('keyword');
        $is_detail = (($student_id != NULL) || ($request->get('mail') != NULL)) ? 1 : 0;
        $email = $request->get('mail') != NULL ? $request->get('mail') : null;
        $students = Students::with('social_media')->orderBy('created_at', 'desc')->when($student_id != NULL, function($query) use ($student_id) {
            $query->where('id', $student_id);
        })->when($email != NULL, function($query) use ($email) {
                $query->where('email', $email);
        })->paginateChecker($is_detail, $this->ADMIN_LIST_STUDENT_VIEW_PER_PAGE);
        return response()->json(['success' => true, 'data' => $students]);
    }
}
