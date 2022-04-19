<?php

namespace App\Models\CRM;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StudentMentor extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_stmentor';

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

    public function student_programs()
    {
        return $this->belongsTo(StudentProgram::class, 'stprog_id', 'stprog_id');
    }


}
