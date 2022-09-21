<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Students extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'birthday',
        'phone_number',
        'grade',
        'email',
        'address',
        'city',
        'total_exp',
        'image',
        'provider',
        'provider_id',
        'password',
        'imported_from',
        'imported_id',
        'status',
        'is_verified',
        'school_name',
        'tag',
        'competitiveness_level',
        'personal_brand_dev_progress',
        'progress_status',
        'application_year',
        'mentee_relationship',
        'parent_relationship',
        'last_update',
        'additional_notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getGradeAttribute($value)
    {
        $created_year = date('Y', strtotime($this->created_at));
        $today_year = date('Y');
        $diff = $today_year > $created_year ? $today_year - $created_year : $created_year - $today_year;
        $grade = $value + $diff;
        return $grade <= 12 ? $grade : 'Not High School';
    }

    // public function getAddressAttribute($value)
    // {
    //     return strip_tags($value);
    // }

    public function getTagAttribute($value)
    {
        $explode = explode(', ', $value);
        return $value == NULL ? NULL : $explode;
    }

    public function medias()
    {
        return $this->hasMany(Medias::class, 'student_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'student_mentors', 'student_id', 'user_id')->withPivot('id');
    }

    public function student_mentors()
    {
        return $this->hasMany(StudentMentors::class, 'student_id', 'id');
    }

    public function todos()
    {
        return $this->hasManyThrough(PlanToDoList::class, StudentMentors::class, 'student_id', 'student_mentors_id', 'id', 'id');
    }

    public function meeting_minutes()
    {
        return $this->hasOneThrough(MeetingMinutes::class, StudentActivities::class, 'student_id', 'st_act_id', 'id', 'id');
    }

    public function student_activities()
    {
        return $this->hasMany(StudentActivities::class, 'student_id', 'id');
    }

    public function social_media()
    {
        return $this->hasMany(SocialMedia::class, 'student_id', 'id');
    }

    public function interests()
    {
        return $this->hasMany(Interests::class, 'student_id', 'id');
    }

    public function competitions()
    {
        return $this->hasMany(Competitions::class, 'student_id', 'id');
    }
 
    public function academic_records()
    {
        return $this->hasMany(AcademicRecords::class, 'student_id', 'id');
    }

    public function uni_shortlisted()
    {
        return $this->hasMany(UniShortlisted::class, 'student_id', 'id');
    }

    public function group_project()
    {
        return $this->hasMany(GroupProject::class, 'student_id', 'id');
    }

    public function group_project_participant()
    {
        return $this->belongsToMany(GroupProject::class, 'participants', 'student_id', 'group_id');
    }

    public function scopePaginateChecker($query, $is_detail, $paginate)
    {
        if (!$is_detail)
            return $query->paginate($paginate);
        else
            return $query->first();
    }

    public function scopeCustomPaginate($query, $use_paginate, $paginate, $options = [])
    {
        if ($use_paginate == "yes") {
            $response = $query->paginate($paginate)->appends($options);
            return $response;
        } else {
            return $query->get();
        }
    }

    public function attendances()
    {
        return $this->belongsToMany(GroupMeeting::class, 'student_attendances', 'student_id', 'group_meet_id');
    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                     ->with([$relation => $constraint]);
    }
}
