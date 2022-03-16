<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeDetails extends Model
{
    use HasFactory;

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
}
