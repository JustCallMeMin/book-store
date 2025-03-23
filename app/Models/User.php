<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'password'
    ];

    protected $hidden = [
        'password',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
    public function getJWTCustomClaims(): array
    {
        return [
            'user_id' => $this->id,
            'roles'   => $this->roles()->pluck('name')
        ];
    }

    /**
     * Quan hệ nhiều-nhiều giữa User và Role.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Lấy tất cả đơn hàng của người dùng
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Lấy giỏ hàng của người dùng
     */
    public function cart(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Lấy tất cả đánh giá của người dùng
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Lấy tất cả phiếu nhập do người dùng tạo
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Kiểm tra xem người dùng có vai trò được chỉ định hay không
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
}
