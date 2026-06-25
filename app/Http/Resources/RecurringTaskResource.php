<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Models\RecurringTask;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RecurringTask
 */
class RecurringTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->uuid,
            'title'            => $this->title,
            'description'      => $this->description,
            'category'         => $this->whenLoaded(
                'category',
                fn() => [
                    'id'   => $this->category->uuid,
                    'name' => $this->category->name,
                ]
            ),
            'frequency'        => $this->frequency->value,
            'frequency_config' => $this->frequency_config,
            'start_date'       => new DateTimeResource($this->start_date, includeTime: false)->resolve($request),
            'end_date'         => new DateTimeResource($this->end_date, includeTime: false)->resolve($request),
            'created_at'       => new DateTimeResource($this->created_at)->resolve($request),
            'updated_at'       => new DateTimeResource($this->updated_at)->resolve($request),
        ];
    }
}
