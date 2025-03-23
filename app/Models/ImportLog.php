<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportLog extends Model
{
    use HasFactory, HasUuids;

    /**
     * Các thuộc tính có thể gán hàng loạt
     */
    protected $fillable = [
        'type',
        'user_id',
        'data',
    ];

    /**
     * Các thuộc tính cần cast
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Định nghĩa quan hệ với User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
