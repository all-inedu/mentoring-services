<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingMinutes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'st_act_id',
        'academic_performance',
        'exploration',
        'writing_skills',
        'personal_brand',
        'mt_todos_note',
        'st_todos_note'
    ];

    public function student_activities()
    {
        return $this->belongsTo(StudentActivities::class, 'st_act_id', 'id');
    }
}
