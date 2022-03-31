<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Editor extends Model
{
    use HasFactory;

    protected $connection = 'mysql_edt';
    protected $table = 'tbl_editors';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'graduated_from',
        'major',
        'address',
        'about_me',
        'position',
        'image',
        'hours',
        'average_rating',
        'status',
        'password'
    ];
}
