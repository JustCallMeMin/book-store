<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    /**
     * Các trường có thể gán giá trị.
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password'
    ];

    /**
     * Các trường ẩn khỏi JSON response.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Tắt tự động tăng (auto increment) và chuyển primary key về kiểu string.
     */
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Boot method để tự động gán UUID khi tạo mới record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Nếu chưa có ID, gán UUID mới.
            if (!$model->{$model->getKeyName()}) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Lấy định danh cho JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Lấy các claims tùy chỉnh cho JWT.
     */
    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'role'    => $this->role,
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}
