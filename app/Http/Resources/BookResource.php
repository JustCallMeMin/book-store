<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gutendex_id' => $this->gutendex_id,
            'google_books_id' => $this->google_books_id,
            'title' => $this->title,
            'subjects' => $this->subjects,
            'bookshelves' => $this->bookshelves,
            'languages' => $this->languages,
            'copyright' => $this->copyright,
            'media_type' => $this->media_type,
            'download_count' => $this->download_count,
            'isbn' => $this->isbn,
            'publisher' => $this->publisher,
            'published_date' => $this->published_date,
            'description' => $this->description,
            'page_count' => $this->page_count,
            'cover_image' => $this->cover_image,
            'quantity_in_stock' => $this->quantity_in_stock,
            'price' => $this->price,
            'price_note' => $this->price_note,
            'discount_percent' => $this->discount_percent,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 