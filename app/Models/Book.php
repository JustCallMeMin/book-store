<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'gutendex_id',
        'google_books_id',
        'title',
        'subjects',
        'bookshelves',
        'languages',
        'summaries',
        'translators',
        'copyright',
        'media_type',
        'formats',
        'download_count',
        'isbn',
        'publisher',
        'published_date',
        'description',
        'page_count',
        'cover_image',
        'quantity_in_stock',
        'price',
        'price_note',
        'discount_percent',
        'is_featured',
        'is_active'
    ];

    protected $casts = [
        'subjects' => 'array',
        'bookshelves' => 'array',
        'languages' => 'array',
        'summaries' => 'array',
        'translators' => 'array',
        'formats' => 'array',
        'copyright' => 'boolean',
        'download_count' => 'integer',
        'published_date' => 'date',
        'page_count' => 'integer',
        'quantity_in_stock' => 'integer',
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Quan hệ nhiều-nhiều với Author
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_authors');
    }

    /**
     * Quan hệ nhiều-nhiều với Category
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'book_categories');
    }

    /**
     * Lấy tất cả các đánh giá của sách
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Lấy tất cả các chi tiết đơn hàng có sách này
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Lấy tất cả các mục trong giỏ hàng có sách này
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Lấy tất cả các chi tiết nhập có sách này
     */
    public function importItems(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }

    /**
     * Getter cho subjects
     */
    public function getSubjectsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho subjects
     */
    public function setSubjectsAttribute($value)
    {
        $this->attributes['subjects'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Getter cho bookshelves
     */
    public function getBookshelvesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho bookshelves
     */
    public function setBookshelvesAttribute($value)
    {
        $this->attributes['bookshelves'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Getter cho languages
     */
    public function getLanguagesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho languages
     */
    public function setLanguagesAttribute($value)
    {
        $this->attributes['languages'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Getter cho summaries
     */
    public function getSummariesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho summaries
     */
    public function setSummariesAttribute($value)
    {
        $this->attributes['summaries'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Getter cho translators
     */
    public function getTranslatorsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho translators
     */
    public function setTranslatorsAttribute($value)
    {
        $this->attributes['translators'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Getter cho formats
     */
    public function getFormatsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Setter cho formats
     */
    public function setFormatsAttribute($value)
    {
        $this->attributes['formats'] = is_array($value) ? json_encode($value) : $value;
    }
} 