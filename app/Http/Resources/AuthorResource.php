<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gutendex_id' => $this->gutendex_id,
            'name' => $this->name,
            'birth_year' => $this->birth_year,
            'death_year' => $this->death_year,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 