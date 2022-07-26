<?php

namespace App\Http\Controllers;

use App\Models\Tags;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class TagsController extends Controller
{
    
    public function index()
    {
        $tags = Tags::where('status', 1)->orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $tags]);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:tags,name'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $tag = new Tags;
            $tag->name = $request->name;
            $tag->save();


            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Create Tag Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to create tag. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'New tag has stored']);
    }

    public function update($tag_id, Request $request)
    {
        $rules = [
            'tag_id' => 'required|exists:tags,id',
            'name' => 'required|unique:tags,name'
        ];

        $validator = Validator::make($request->all() + ['tag_id' => $tag_id], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $tag = Tags::find($tag_id);
            $tag->name = $request->name;
            $tag->save();


            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Update Tag Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update tag. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Tag has updated']);
    }

    public function delete($tag_id)
    {
        if (!$tag = Tags::find($tag_id)) {
            return response()->json(['success' => false, 'message' => 'Id does not exists']);
        }

        DB::beginTransaction();
        try {
            $tag->delete();
            DB::commit();
        } catch (Exception $e)  {
            DB::rollBack();
            Log::error('Delete Tag Issue : '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete tag. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Tag has deleted']);
    }
}
