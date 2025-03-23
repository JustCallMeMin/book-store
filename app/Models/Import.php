<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Import extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'import_code',
        'supplier_id',
        'user_id',
        'total_amount',
        'tax_amount',
        'shipping_fee',
        'discount_amount',
        'final_amount',
        'status',
        'import_date',
        'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'import_date' => 'datetime'
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
     * Lấy tất cả chi tiết phiếu nhập
     */
    public function importItems(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }

    /**
     * Lấy thông tin nhà cung cấp
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Lấy thông tin người tạo phiếu nhập
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
