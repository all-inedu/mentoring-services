<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMentors extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'user_id',
        'user_id',
        'start_mentoring',
        'end_mentoring',
        'status',
        'created_at',
        'updated_at'
    ];

    public function plan_to_do_list()
    {
        return $this->hasMany(PlanToDoList::class, 'student_mentors_id', 'id');
    }
}
