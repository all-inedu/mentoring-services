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

    public function medias()
    {
        return $this->hasMany(Medias::class, 'student_id', 'id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'student_mentors', 'student_id', 'user_id');
    }

    public function student_activities()
    {
        return $this->hasMany(StudentActivities::class, 'student_id', 'id');
    }

    public function social_media()
    {
        return $this->hasMany(SocialMedia::class, 'student_id', 'id');
    }

    public function scopePaginateChecker($query, $is_detail, $paginate)
    {
        if (!$is_detail)
            return $query->paginate($paginate);
        else
            return $query->first();
    }
}
