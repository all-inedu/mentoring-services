<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use HasFactory;

    protected $connection = 'mysql_crm';
    protected $table = 'tbl_mt';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mt_id',
        'mt_firstn',
        'mt_lastn',
        'mt_address',
        'mt_major',
        'univ_id',
        'mt_email',
        'mt_phone',
        'mt_password',
        'mt_cv',
        'mt_ktp',
        'mt_banknm',
        'mt_bankacc',
        'mt_npwp',
        'mt_status',
        'mt_istutor',
        'mt_tsubject',
        'mt_feehours',
        'mt_feesession',
        'mt_lastcontactdate',
        'mt_notes',
        'mt_lastupdate'
    ];

    public function university()
    {
        return $this->belongsTo(University::class, 'univ_id', 'univ_id');
    }
}
