<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Nếu resource không phải object mà là string
        if (!is_object($this->resource)) {
            return [
                'id' => null,
                'name' => is_string($this->resource) ? $this->resource : 'Unknown',
                'created_at' => null,
                'updated_at' => null
            ];
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 