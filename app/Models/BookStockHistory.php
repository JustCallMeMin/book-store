<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookStockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'user_id',
        'previous_quantity',
        'new_quantity',
        'adjustment',
        'action',
        'reason',
        'order_id',
        'import_id',
    ];

    protected $casts = [
        'previous_quantity' => 'integer',
        'new_quantity' => 'integer',
        'adjustment' => 'integer',
    ];

    /**
     * Get the book associated with the stock history
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the user who made the adjustment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a stock adjustment
     */
    public static function recordAdjustment(
        Book $book,
        int $previousQuantity,
        int $newQuantity,
        string $action = 'other',
        ?string $reason = null,
        ?string $orderId = null,
        ?string $importId = null
    ): self {
        return self::create([
            'book_id' => $book->id,
            'user_id' => auth()->id(),
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'adjustment' => $newQuantity - $previousQuantity,
            'action' => $action,
            'reason' => $reason,
            'order_id' => $orderId,
            'import_id' => $importId,
        ]);
    }
}
