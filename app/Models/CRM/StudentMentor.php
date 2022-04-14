<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMentor extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_stprog';

    protected $primaryKey = 'stmentor_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stmentor_id',
        'stprog_id',
        'mt_id1',
        'mt_id2'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class, 'stprog_id', 'stprog_id');
    }
}
