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
        'handled_by',
        'location_link',
        'prog_dtl_id',
        'call_with',
        'module'
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
        return $this->belongsTo(User::class, ['user_id', 'handled_by'], 'id');
    }

    public function transactions()
    {
        return $this->hasOne(Transaction::class, 'st_act_id', 'id');
    }
}
