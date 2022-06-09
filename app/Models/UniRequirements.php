<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniRequirements extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'description',
        'toefl_score',
        'ielts_score',
        'essay_title',
        'publication_links',
        'status'
    ];

    public function uni_shortlisted()
    {
         return $this->belongsTo(UniShortlisted::class, 'uni_id', 'id');
    }

    public function media_categories()
    {
        return $this->belongsTo(MediaCategory::class, 'med_cat_id', 'id');
    }

    public function media()
    {
        return $this->belongsToMany(Medias::class, 'uni_requirement_media', 'uni_req_id', 'med_id');
    }
}
