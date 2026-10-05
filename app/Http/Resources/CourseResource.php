<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sks' => $this->sks,
            'status' => $this->status,
            'lecturer' => new UserResource($this->whenLoaded('lecturer')),
            'students_count' => $this->whenCounted('students'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
