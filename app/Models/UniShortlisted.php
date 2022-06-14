<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniShortlisted extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'imported_id',
        'uni_name',
        'uni_major',
        'status'
    ];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function students()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }

    public function uni_requirement()
    {
        return $this->hasMany(UniRequirements::class, 'uni_id', 'id');
    }

    public function medias()
    {
        return $this->belongsToMany(Medias::class, 'uni_requirement_media', 'uni_shortlisted_id', 'med_id');
    }
}
