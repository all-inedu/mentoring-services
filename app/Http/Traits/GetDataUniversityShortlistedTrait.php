<?php

namespace App\Http\Traits;

use App\Models\UniShortlisted;

trait GetDataUniversityShortlistedTrait
{
    public function get($id, $status)
    {
        $uni_shortlisted = UniShortlisted::when($status == 'waitlisted', function($query) {
                $query->where('status', 0);
            })->when($status == 'accepted', function($query) {
                $query->where('status', 1);
            })->when($status == 'applied', function($query) {
                $query->where('status', 2);
            })->when($status == 'rejected', function($query) {
                $query->where('status', 3);
            })->when($status == 'shortlisted', function($query) {
                $query->where('status', 99);
            })->when($status == 'all', function($query) {
                $query->where('status', '!=', 100);
            })->where('student_id', $id)->orderBy('uni_name', 'asc')->orderBy('uni_major', 'asc')->get();

        return $uni_shortlisted;
    }
}