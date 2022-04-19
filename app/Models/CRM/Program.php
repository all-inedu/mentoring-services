<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_prog';

    protected $primaryKey = 'prog_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'prog_main',
        'main_number',
        'prog_sub',
        'prog_program',
        'prog_type',
        'prog_mentor',
        'prog_payment'
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'tbl_stprog', 'prog_id', 'st_num');
    }

    public function student_program()
    {
        return $this->hasMany(StudentProgram::class, 'prog_id', 'prog_id');
    }
}
