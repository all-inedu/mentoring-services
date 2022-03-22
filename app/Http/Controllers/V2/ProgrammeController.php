<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Programmes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Providers\RouteServiceProvider;

class ProgrammeController extends Controller
{
    
    protected $store_media_path;
    protected $ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;

    public function __construct()
    {
        $this->store_media_path = RouteServiceProvider::USER_STORE_MEDIA_PATH;
        $this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE = RouteServiceProvider::ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE;
    }

    public function index()
    {
        $programmes = Programmes::orderBy('created_at', 'desc')->paginate($this->ADMIN_LIST_PROGRAMME_VIEW_PER_PAGE);
        return response()->json(['succes' => true, 'data' => $programmes]);
    }

    public function store(Request $request)
    {
        //initialize variable
        $data = $prog_has = array();
        $programme_inserted_id = $med_file_name = $med_file_format = $med_file_path = NULL;

        //VALIDATION START
        $rules = [
            'prog_name'   => 'required|string|max:255|unique:programmes,prog_name',
            'prog_desc'   => 'required',
            'prog_price'  => 'nullable|integer',
            'status'      => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        //VALIDATION END

        //begin process
        DB::beginTransaction();
        //insert to programme table
        try {

            $programme = new Programmes;
            $programme->prog_name = $request->prog_name;
            $programme->prog_desc = $request->prog_desc;
            $programme->prog_price = $request->prog_price;
            $programme->status = $request->status;
            $programme->save();

            $data['programme'] = $programme;

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme. Please try again.']);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'New programme has been made', 'data' => $data]);
    }

    public function update($prog_id, Request $request)
    {
        try {
            $programme = Programmes::findOrFail($prog_id);
        } catch (Exception $e) {
            Log::error('Find Program by Id Issue : ['.$prog_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find programme by Id. Please try again.']);
        }

        //initialize variable
        $data = $prog_has = array();
        $programme_inserted_id = $med_file_name = $med_file_format = $med_file_path = NULL;

        //VALIDATION START
        $rules = [
            'prog_name'   => 'required|string|max:255|unique:programmes,prog_name,'.$prog_id,
            'prog_desc'   => 'required',
            'prog_price'  => 'nullable|integer',
            'status'      => 'required|in:active,inactive'
        ];


        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }
        //VALIDATION END

        //begin process
        DB::beginTransaction();
        //update to programme table
        try {

            $programme->prog_name = $request->prog_name;
            $programme->prog_desc = $request->prog_desc;
            $programme->prog_price = $request->prog_price;
            $programme->status = $request->status;
            $programme->save();

            $data['programme'] = $programme;
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Programme Issue : ['.$request->prog_mod_id.', '.$request->prog_name.', '.$request->prog_desc.', '.$request->status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update programme. Please try again.']);
        }

        DB::commit();
        return response()->json(['success' => true, 'message' => 'Programme has been updated', 'data' => $programme]);
    }
}
