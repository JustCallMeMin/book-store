<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'password',
        'provider',
        'provider_id',
        'oauth_verified',
        'oauth_verified_at',
        'oauth_token',
        'oauth_refresh_token'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'oauth_token',
        'oauth_refresh_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'oauth_verified_at' => 'datetime',
        'oauth_verified' => 'boolean'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid();
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
        return [];
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

    public function oauthVerifications()
    {
        return $this->hasMany(OAuthVerification::class);
    }
}
