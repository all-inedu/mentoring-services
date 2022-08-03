<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProgrammeDetails extends Model
{
    use HasFactory;

    protected $table = 'programme_details';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_id',
        'dtl_category',
        'dtl_name',
        'dtl_desc',
        'dtl_price',
        'dtl_video_link',
        'dtl_date',
        'status'
    ];

    public function programmes()
    {
        return $this->belongsTo(Programmes::class, 'prog_id', 'id');
    }

    public function programme_schedules()
    {
        return $this->hasMany(ProgrammeSchedules::class, 'prog_dtl_id', 'id');
    }

    public function speakers()
    {
        return $this->hasMany(Speakers::class, 'prog_dtl_id', 'id');
    }

    public function partners()
    {
        return $this->hasMany(Partners::class, 'prog_dtl_id', 'id');
    }

    public function student_activities()
    {
        return $this->hasMany(StudentActivities::class, 'prog_dtl_id', 'id');
    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                     ->with([$relation => $constraint]);
    }

}
