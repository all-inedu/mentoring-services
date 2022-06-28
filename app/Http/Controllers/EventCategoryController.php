<?php

namespace App\Http\Controllers;

use App\Models\ProgrammeDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Log;

class EventCategoryController extends Controller
{
    
    public function index ($programme)
    {
        $rules = [
            "programme" => 'required|in:event,webinar'
        ];

        $validator = Validator::make(['programme' => $programme], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            $category = ProgrammeDetails::whereHas('programmes', function ($query) use ($programme) {
                $query->where('prog_name', $programme);
            })->groupBy('dtl_category')->select('dtl_category')->get();

        } catch (Exception $e) {
            
            Log::error('Failed to fetch programme categories : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to fetch programme categories. Please try again.']);
        }

        return response()->json(['success' => true, 'data' => $category]);
    }
}
