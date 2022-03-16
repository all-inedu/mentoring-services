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
        'role_id',
        'per_scope_access',
        'per_desc'
    ];

    public function roles()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
