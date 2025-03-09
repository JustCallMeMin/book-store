<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name'];
    public $incrementing = false; // Để Laravel hiểu rằng ID không phải auto-increment
    protected $keyType = 'string'; // UUID được lưu dưới dạng string

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }
}
