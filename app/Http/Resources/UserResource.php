<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Chỉnh sửa format của response khi trả về
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'full_name'  => $this->first_name . ' ' . $this->last_name,
            'email'      => $this->email,
            'roles'      => $this->roles->pluck('name'),
        ];
    }
}
