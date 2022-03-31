<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_univ';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'univ_id',
        'univ_name',
        'univ_address',
        'univ_country'
    ];

    public function mentor()
    {
        return $this->hasMany(Mentor::class, 'univ_id', 'univ_id');
    }

    public function alumni_detail()
    {
        return $this->hasMany(AlumniDetail::class, 'univ_id', 'univ_id');
    }
}
