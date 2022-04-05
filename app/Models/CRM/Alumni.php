<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_alu';

    protected $primaryKey = 'alu_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'st_id',
        'alu_graduatedate'
    ];

    public function student()
    {
        return $this->belongsTo(Client::class, 'st_id', 'st_id');
    }

    public function alumni_detail()
    {
        return $this->hasMany(AlumniDetail::class, 'alu_id', 'alu_id')->where('aludetail_status', 3);
    }
}
