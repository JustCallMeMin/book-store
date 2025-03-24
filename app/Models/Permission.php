<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    public static function findBySlug(string $slug)
    {
        return static::where('slug', $slug)->first();
    }

    public static function createPermission(string $name, string $slug, ?string $description = null, ?string $group = null)
    {
        return static::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'group' => $group,
        ]);
    }
}
