<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'gutendex_id',
        'title',
        'subjects',
        'bookshelves',
        'languages',
        'summaries',
        'translators',
        'copyright',
        'media_type',
        'formats',
        'download_count'
    ];

    protected $casts = [
        'subjects' => 'array',
        'bookshelves' => 'array',
        'languages' => 'array',
        'summaries' => 'array',
        'translators' => 'array',
        'formats' => 'array',
        'copyright' => 'boolean',
        'download_count' => 'integer'
    ];

    /**
     * Quan hệ nhiều-nhiều với Author
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_authors', 'book_id', 'author_id');
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