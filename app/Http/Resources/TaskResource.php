<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Task
 */
class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->uuid,
            'title'        => $this->title,
            'description'  => $this->description,
            'category'     => $this->whenLoaded(
                'category',
                fn() => [
                    'id'   => $this->category->uuid,
                    'name' => $this->category->name,
                ]
            ),
            'task_date'    => new DateTimeResource($this->task_date, includeTime: false)->resolve($request),
            'completed_at' => new DateTimeResource($this->completed_at)->resolve($request),
            'created_at'   => new DateTimeResource($this->created_at)->resolve($request),
            'updated_at'   => new DateTimeResource($this->updated_at)->resolve($request),
        ];
    }
}
