<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'book_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'discount_amount',
        'final_cost'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_cost' => 'decimal:2'
    ];

    /**
     * Lấy thông tin phiếu nhập
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    /**
     * Lấy thông tin sách
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
