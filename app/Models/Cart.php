<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'session_id',
        'total_items',
        'total_amount',
        'discount_amount',
        'final_amount',
        'expires_at',
        'is_guest',
        'last_activity'
    ];

    protected $casts = [
        'total_items' => 'integer',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'last_activity' => 'datetime',
        'is_guest' => 'boolean'
    ];

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
     * Lấy tất cả các item trong giỏ hàng
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Lấy thông tin người dùng (nếu có)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
