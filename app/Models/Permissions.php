<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permissions extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'per_scope_access',
        'per_desc'
    ];

    public function roles()
    {
        return $this->belongsTo(Roles::class, 'role_id', 'id');
    }
}
