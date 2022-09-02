<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMeeting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'start_meeting_date',
        'end_meeting_date',
        'meeting_link',
        'meeting_subject',
        'mail_sent',
        'status'
    ];
    protected $with = ['student_attendances', 'user_attendances'];

    public function scopeRecent($query, $recent, $paginate)
    {
        if (!$recent)
            return $query->paginate($paginate);
        else
            return $query->get();
    }

    public function group_project()
    {
        return $this->belongsTo(GroupProject::class, 'group_id', 'id');
    }

    //* for student
    public function student_attendances()
    {
        return $this->belongsToMany(Students::class, 'student_attendances', 'group_meet_id', 'student_id')->withPivot('id', 'attend_status', 'mail_sent');
    }

    //* for mentor
    public function user_attendances()
    {
        return $this->belongsToMany(User::class, 'user_attendances', 'group_meet_id', 'user_id')->withPivot('id', 'attend_status', 'mail_sent');
    }
}
