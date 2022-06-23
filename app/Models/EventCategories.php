<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCategories extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name'
    ];

    public function programme_details ()
    {
        return $this->hasMany(ProgrammeDetails::class, 'dtl_category', 'id');
    }
}
