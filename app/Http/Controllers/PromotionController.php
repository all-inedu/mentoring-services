<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{

    public function switch($status, Request $request)
    {
        $rules = [
            'promo_id' => [
                'required',
                Rule::unique('promotions', 'id')->where(function($query) {
                    return $query->where('deleted_at', '!=', NULL);
                }),
            ],
            'status'   => 'in:active,deactive'
        ];

        $custom_message = [
            'promo_id.unique' => 'Promotion id doesn\'t exist'
        ];

        $validator = Validator::make($request->all() + ['status' => $status], $rules, $custom_message);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        // DB::beginTransaction();
        try {

            if (!$promotion = Promotion::find($request->promo_id)) {
                return response()->json(['success' => false, 'error' => '']);
            }
            $promotion->status = $request->status;
            $promotion->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Switch status Issue : ['.$request->promo_id.', '.$status.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to switch promotion status. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'The promotion has been changed to '.$status]);
    }

    public function check_validation($promo_code)
    {
        $today = Carbon::now();
        try {
            $promotion = Promotion::where('promo_code', $promo_code)->first();
            if(!$promotion) {
                throw new Exception('The promotion code doesn\'t exist');
            }

            if (($today <= $promotion->promo_start_date) && ($today >= $promotion->promo_end_date)) {
                throw new Exception('The promotion code already expired');
            }
            if (($promotion->limited == 1) && ($promotion->total_used == 0)) {
                throw new Exception('The promotion code is not available');
            }

        } catch (Exception $e) {
            
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
        
        return response()->json(['success' => true, 'message' => 'Promo Code is valid']);
    }

    public function index()
    {
        $promotion = Promotion::where('deleted_at', NULL)->orderBy('created_at', 'desc')->get();
        return response()->json(['succes' => true, 'data' => $promotion]);
    }
    
    public function store(Request $request)
    {
        $rules = [
            'promo_title'      => 'nullable|unique:promotions,promo_title',
            'promo_desc'       => 'nullable',
            'promo_code'       => 'nullable|unique:promotions,promo_code',
            'promo_type'       => 'required|in:amount,percentage',
            'discount'         => 'required|integer|min:0',
            'promo_start_date' => 'nullable|required_with:promo_end_date|date|date_format:Y-m-d',
            'promo_end_date'   => 'nullable|required_with:promo_start_date|date|date_format:Y-m-d|after:promo_start_date',
            'limited'          => 'required|boolean',
            'total_used'       => 'nullable|required_if:limited,true|integer|min:0',
            'status'           => 'required|in:active,deactive'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            
            //generate promo code when empty
            $promo_code = isset($request->promo_code) ? $request->promo_code : Str::random(10);

            $promotion = new Promotion;
            $promotion->promo_title = $request->promo_title;
            $promotion->promo_desc = $request->promo_desc;
            $promotion->promo_code = $promo_code;
            $promotion->promo_type = $request->promo_type;
            $promotion->discount = $request->discount;
            $promotion->promo_start_date = $request->promo_start_date;
            $promotion->promo_end_date = $request->promo_end_date;
            $promotion->limited = $request->limited;
            $promotion->total_used = isset($request->total_used) ? $request->total_used : 0;
            $promotion->status = $request->status;
            $promotion->save();
            DB::commit();

        } catch (Exception $e) {

            DB::rollBack();
            Log::error('Create Promotion Issue : ['.json_encode($request->all()).'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create a new programme. Please try again.']);

        }

        return response()->json(['success' => true, 'message' => 'New promotion has been made', 'data' => $promotion]);
    }

    public function delete($promo_id)
    {
        DB::beginTransaction();
        try {
            Promotion::find($promo_id)->delete();
            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Delete Promotion Issue : ['.$promo_id.'] '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete promotion. Please try again.']);
        }
        
        return response()->json(['success' => true, 'message' => 'You\'ve successfully deleted the programme']);
    }
}
