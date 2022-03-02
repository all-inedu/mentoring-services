<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $dates = ['deleted_at'];
    protected $hidden = ['deleted_at'];

    protected $fillable = [
        'promo_title',
        'promo_desc',
        'promo_code',
        'promo_type',
        'discount',
        'promo_start_date',
        'promo_end_date',
        'limited',
        'total_used',
        'status'
    ];
}
