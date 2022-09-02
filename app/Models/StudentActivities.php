<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentActivities extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_id',
        'student_id',
        'user_id',
        'std_act_status',
        'mt_confirm_status',
        'handled_by',
        'location_link',
        'location_pw',
        'prog_dtl_id',
        'call_with',
        'module',
        'start_call_date',
        'end_call_date',
        'call_status',
        'std_reason',
        'mt_reason',
    ];

    public function programmes ()
    {
        return $this->belongsTo(Programmes::class, 'prog_id', 'id');
    }

    public function students ()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }

    public function programme_details ()
    {
        return $this->belongsTo(ProgrammeDetails::class, 'prog_dtl_id', 'id');
    }

    public function users ()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function handled_by ()
    {
        return $this->hasOne(User::class, 'id', 'handled_by');
    }

    public function transactions()
    {
        return $this->hasOne(Transaction::class, 'st_act_id', 'id');
    }

    public function meeting_minutes()
    {
        return $this->hasOne(MeetingMinutes::class, 'st_act_id', 'id');
    }

    public function watch_detail()
    {
        return $this->hasOne(WatchDetail::class, 'std_act_id', 'id');
    }

    public function scopeRecent($query, $recent, $paginate)
    {
        if (!$recent)
            return $query->paginate($paginate);
        else
            return $query->limit(3)->get();
    }
}
