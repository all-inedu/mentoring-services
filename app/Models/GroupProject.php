<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupProject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_name',
        'project_type',
        'project_desc',
        'progress_status',
        'status',
        'owner_type',
        'picture',
    ];

    // public function getProjectDescAttribute($value)
    // {
    //     return strip_tags($value);
    // }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id' , 'id');
    }

    public function students()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }

    public function group_meeting()
    {
        return $this->hasMany(GroupMeeting::class, 'group_id', 'id');
    }

    public function group_participant()
    {
        return $this->belongsToMany(Students::class, 'participants', 'group_id', 'student_id')->withPivot('id', 'contribution_role', 'contribution_description', 'status', 'mail_sent_status');
    }

    public function assigned_mentor()
    {
        return $this->belongsToMany(User::class, 'group_mentors', 'group_id', 'user_id')->withPivot('id', 'status');
    }
}
