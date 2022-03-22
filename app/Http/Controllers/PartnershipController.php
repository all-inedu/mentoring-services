<?php

namespace App\Http\Controllers;

use App\Models\Partners;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ProgrammeDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class PartnershipController extends Controller
{
    
    public function select($prog_dtl_id)
    {
        try {
            $partners = Partners::where('prog_dtl_id', $prog_dtl_id)->orderBy('created_at', 'desc')->get();
        } catch (Exception $e) {
            Log::error('Select Partners Use Programme Detail Id  Issue : ['.$prog_dtl_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to select partners by programme detail Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $partners]);
    }

    public function find($pt_id)
    {
        try {
            $partner = Partners::findOrFail($pt_id);
        } catch (Exception $e) {
            Log::error('Find Partner by Id Issue : ['.$pt_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find partner by Id. Please try again.']);
        }
        return response()->json(['success' => true, 'data' => $partner]);
    }

    public function store(Request $request)
    {
        $rules = [
            'prog_dtl_id'   => [
                            'required',
                            Rule::exists(ProgrammeDetails::class, 'id')->where(function($query) {
                                $query->where('dtl_category', '!=', NULL);
                            }),
                        ],
            'pt_name'    => 'required|max:255',
            'pt_image'   => 'required',
            'pt_website' => 'required',
            'status'     => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        //insert to schedule table
        try {
            $partner = new Partners;
            $partner->prog_dtl_id = $request->prog_dtl_id;
            $partner->pt_name = $request->pt_name;
            $partner->pt_image = $request->pt_image;
            $partner->pt_website = $request->pt_website;
            $partner->status = $request->status;
            $partner->save();

            $data['partner'] = $partner;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Save Partner Issue : ['.$request->prog_dtl_id.', '.json_encode($partner).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to save partner. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Partner has been added', 'data' => $data]);
    }

    public function update($pt_id, Request $request)
    {
        try {
            $partner = Partners::findOrFail($pt_id);
        } catch (Exception $e) {
            Log::error('Find Partner by Id Issue : ['.$pt_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to find partner by Id. Please try again.']);
        }

        $rules = [
            'prog_dtl_id'   => [
                            'required',
                            Rule::exists(ProgrammeDetails::class, 'id')->where(function($query) {
                                $query->where('dtl_category', '!=', NULL);
                            }),
                        ],
            'pt_name'    => 'required|max:255',
            'pt_image'   => 'required',
            'pt_website' => 'required',
            'status'     => 'required|in:active,inactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        //insert to schedule table
        try {
            $partner->prog_dtl_id = $request->prog_dtl_id;
            $partner->pt_name = $request->pt_name;
            $partner->pt_image = $request->pt_image;
            $partner->pt_website = $request->pt_website;
            $partner->status = $request->status;
            $partner->save();

            $data['partner'] = $partner;
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Partner Issue : ['.$request->prog_dtl_id.', '.json_encode($partner).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update partner. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Partner has been updated', 'data' => $data]);
    }

    public function delete($pt_id)
    {
        //Validation
        if (!$partner = Partners::find($pt_id)) {
            return response()->json(['success' => false, 'error' => 'Failed to find existing speaker'], 400);
        } 

        DB::beginTransaction();
        try {
            $partner->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Partner Issue : ['.$pt_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete partner. Please try again.'], 400);
        }
        return response()->json(['success' => true, 'message' => 'Partner has been deleted']);
    }
}
