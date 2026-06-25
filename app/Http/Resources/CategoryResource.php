<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->uuid,
            'name'       => $this->name,
            'created_at' => new DateTimeResource($this->created_at)->resolve($request),
            'updated_at' => new DateTimeResource($this->updated_at)->resolve($request),
        ];
    }
}
