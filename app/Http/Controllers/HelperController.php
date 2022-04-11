<?php

namespace App\Http\Controllers;

use App\Models\SynchronizeLogs;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class HelperController extends Controller
{
    //** function to save last synchronization */
    public function last_sync ($user_type)
    {
        $rules = [
            'user_type' => 'in:student,mentor,editor,alumni'
        ];

        $validator = Validator::make(['user_type' => $user_type], $rules);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $data = SynchronizeLogs::where('user_type', $user_type)->where('status', 'success')->orderBy('created_at', 'desc')->first();
        return response()->json(['success' => true, 'data' => date('F d, Y H:i:s', strtotime($data->created_at))]);
    }

    //** external paginate function */
    public function paginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, ['path' => LengthAwarePaginator::resolveCurrentPath()]);
    }
}
