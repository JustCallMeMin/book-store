<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publisher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * Lấy tất cả sách từ nhà xuất bản này
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }
} 