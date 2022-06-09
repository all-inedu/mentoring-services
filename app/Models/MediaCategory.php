<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'media_categories';

    protected $fillable = [
        'name',
        'terms',
        'type',
        'status'
    ];

    public function medias()
    {
        return $this->hasMany(Medias::class, 'med_cat_id', 'id');
    }

    public function uni_requirement()
    {
        return $this->hasMany(UniRequirements::class, 'med_cat_id', 'id');
    }
}
