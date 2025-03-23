<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    use HasFactory;

    protected $fillable = [
        'gutendex_id',
        'name',
        'birth_year',
        'death_year'
    ];

    protected $casts = [
        'birth_year' => 'integer',
        'death_year' => 'integer'
    ];

    /**
     * Quan hệ nhiều-nhiều với Book
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_authors', 'author_id', 'book_id');
    }
} 