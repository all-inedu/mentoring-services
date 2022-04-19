<?php

namespace App\Models\CRM;

use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_students';

    protected $primaryKey = 'st_num';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'st_num',
        'st_id',
        'pr_id',
        'st_firstname',
        'st_lastname',
        'st_mail',
        'st_phone',
        'st_dob',
        'st_insta',
        'st_state',
        'st_city',
        'st_address',
        'sch_id',
        'st_grade',
        'st_grade_updated',
        'lead_id',
        'eduf_id',
        'infl_id',
        'st_levelinterest',
        'prog_id',
        'st_abryear',
        'st_abrcountry',
        'st_abruniv',
        'st_abrmajor',
        'st_statusact',
        'st_note',
        'st_statuscli',
        'st_password',
        'st_datecreate',
        'st_datelastedit'
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'sch_id', 'sch_id');
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'tbl_stprog', 'st_num', 'prog_id');
    }

    public function student_programs()
    {
        return $this->hasMany(StudentProgram::class, 'st_num', 'st_num');
    }

    public function alumni()
    {
        return $this->hasMany(Students::class, 'st_id', 'st_id');
    }

    public function scopeWithAndWhereHas($query, $relation, $constraint){
        return $query->whereHas($relation, $constraint)
                     ->with([$relation => $constraint]);
    }
}
