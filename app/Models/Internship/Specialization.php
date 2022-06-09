<?php

namespace App\Models\Internship;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    use HasFactory;

    protected $connection = 'mysql_internship';
    protected $table = 'tbl_specialization';

    protected $primaryKey = 'spec_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'spec_name',
        'spec_parent',
        'spec_status'
    ];
}
