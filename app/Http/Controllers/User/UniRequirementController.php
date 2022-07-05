<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\UniversityController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UniRequirementController extends Controller
{
    
    public function index($category, $student_id, $univ_id = null)
    {
        $university = new UniversityController;
        $rules = [
            'category' => 'in:academic,document'
        ];

        if (($univ_id != null) && ($univ_id != "all")) {
            $rules['show_item'] = 'exists:uni_shortlisteds,imported_id';
        }
        
        $validator = Validator::make(['category' => $category], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        switch ($category) {
            case "academic":
                return $university->index_academic_requirement($student_id);
                break;

            case "document":
                return $university->index_document_requirement($student_id, $univ_id);
                break;
        }
    }
}
