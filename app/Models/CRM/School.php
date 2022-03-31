<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_sch';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sch_id',
        'sch_name',
        'sch_type',
        'sch_level',
        'sch_curriculum',
        'sch_mail',
        'sch_phone',
        'sch_insta',
        'sch_city',
        'sch_location',
        'sch_lastupdate'
    ];

    public function client()
    {
        return $this->hasMany(Client::class, 'sch_id', 'sch_id');
    }
}
