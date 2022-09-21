<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
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
        'role_id',
        'phone_number',
        'email',
        'image',
        'provider',
        'provider_id',
        'password',
        'is_verified',
        'imported_id',
        'position'
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    public function user_schedules()
    {
        return $this->hasMany(UserSchedule::class, 'user_id', 'id');
    }

    public function students()
    {
        return $this->belongsToMany(Students::class, 'student_mentors', 'user_id', 'student_id');
    }

    public function student_mentors()
    {
        return $this->hasMany(StudentMentors::class, 'user_id', 'id');
    }

    public function educations()
    {
        return $this->hasMany(Education::class, 'user_id', 'id');
    }

    public function student_activities()
    {
        return $this->hasMany(StudentActivities::class, 'user_id', 'id');
    }

    public function social_media()
    {
        return $this->hasMany(SocialMedia::class, 'user_id', 'id');
    }

    public function uni_shortlisted()
    {
        return $this->hasMany(UniShortlisted::class, 'user_id', 'id');
    }

    public function group_project()
    {
        return $this->hasMany(GroupProject::class, 'user_id', 'id');
    }

    public function initial_consult()
    {
        return $this->hasMany(InitialConsult::class, 'user_id', 'id');
    }

    public function group_mentor()
    {
        return $this->belongsToMany(GroupProject::class, 'group_mentors', 'user_id', 'group_id');
    }

    public function attendances()
    {
        return $this->belongsToMany(GroupMeeting::class, 'user_attendances', 'user_id', 'group_meet_id');
    }
}
