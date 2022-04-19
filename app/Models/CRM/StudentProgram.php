<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProgram extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_stprog';

    protected $primaryKey = 'stprog_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stprog_id',
        'st_num',
        'prog_id',
        'lead_id',
        'eduf_id',
        'infl_id',
    ];

    public function clients()
    {
        return $this->belongsTo(Client::class, 'st_num', 'st_num');
    }

    public function programs()
    {
        return $this->belongsTo(Program::class, 'prog_id', 'prog_id');
    }
    
    public function student_mentors()
    {
        return $this->hasMany(StudentMentor::class, 'stprog_id', 'stprog_id');
    }
}
