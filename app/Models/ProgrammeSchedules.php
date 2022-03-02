<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgrammeSchedules extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_id',
        'prog_sch_start_date',
        'prog_sch_start_time',
        'prog_sch_end_date',
        'prog_sch_end_time',
        'status'
    ];

    public function programmes()
    {
        return $this->belongsTo(Programmes::class, 'prog_id', 'id');
    }
}
