<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeSchedules extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_dtl_id',
        'prog_sch_start_date',
        'prog_sch_start_time',
        'prog_sch_end_date',
        'prog_sch_end_time',
        'status'
    ];

    public function getProgSchStartTimeAttribute($value)
    {
        return date('H:i', strtotime($value));
    }

    public function getProgSchEndTimeAttribute($value)
    {
        return date('H:i', strtotime($value));
    }

    public function programme_details()
    {
        return $this->belongsTo(ProgrammeDetails::class, 'prog_dtl_id', 'id');
    }
}
