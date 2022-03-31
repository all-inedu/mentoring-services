<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniDetail extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_aludetail';

    protected $primaryKey = 'aludetail_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'alu_id',
        'univ_id',
        'aludetail_major',
        'aludetail_scholarship',
        'aludetail_status'
    ];

    public function university()
    {
        return $this->belongsTo(University::class, 'univ_id', 'univ_id');
    }
}
