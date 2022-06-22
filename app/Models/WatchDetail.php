<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_duration',
        'current_time'
    ];

    public function joined_activities()
    {
        return $this->belongsTo(StudentActivities::class, 'std_act_id', 'id');
    }
}
