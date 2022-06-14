<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medias extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $primaryKey = 'id';

    protected $fillable = [
        'student_id',
        'med_cat_id',
        'med_title',
        'med_desc',
        'med_file_path',
        'med_file_name',
        'med_file_format',
        'status'
    ];

    public function students()
    {
        return $this->belongsTo(Students::class, 'student_id', 'id');
    }

    public function media_categories()
    {
        return $this->belongsTo(MediaCategory::class, 'med_cat_id', 'id');
    }

    // public function uni_requirement()
    // {
    //     return $this->belongsToMany(UniRequirements::class, 'uni_requirement_media', 'med_id', 'uni_req_id');
    // }

    public function uni_shortlisted()
    {
        return $this->belongsToMany(UniShortlisted::class, 'uni_requirement_media', 'med_id', 'uni_shortlisted_id');
    }
}
