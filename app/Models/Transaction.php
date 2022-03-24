<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'transactions';

    protected $fillable = [
        'st_act_id',
        'promo_id',
        'amount',
        'total_amount',
        'status',
        'payment_proof',
        'payment_method',
        'payment_date'
    ];

    public function student_activities()
    {
        return $this->belongsTo(StudentActivities::class, 'st_act_id', 'id');
    }

    public function scopeRecent($query, $recent, $paginate)
    {
        if (!$recent)
            return $query->paginate($paginate);
        else
            return $query->limit(3)->get();
    }
}
