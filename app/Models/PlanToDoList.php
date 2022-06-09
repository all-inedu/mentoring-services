<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanToDoList extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_name',
        'description',
        'due_date',
        'content',
        'status'
    ];

    public function student_mentors()
    {
        return $this->belongsTo(StudentMentors::class, 'student_mentors_id', 'id');
    }
}
