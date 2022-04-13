<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programmes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_mod_id',
        'prog_name',
        'prog_desc',
        'prog_has',
        'prog_href',
        'prog_price',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function programme_modules()
    {
        return $this->belongsTo(ProgrammeModules::class, 'prog_mod_id', 'id');
    }

    public function programme_details()
    {
        return $this->hasMany(ProgrammeDetails::class, 'prog_id', 'id');
    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                     ->with([$relation => $constraint]);
    }

    public function student_activities()
    {
        return $this->hasMany(StudentActivities::class, 'prog_id', 'id');
    }

    public function scopeRecent($query, $recent, $paginate)
    {
        if ($recent)
            return $query->limit(3)->get();
        else 
            return $query->paginate($paginate);
    }
}
