<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status->value,
            'deadline'    => $this->deadline?->toDateString(),
            'project'     => [
                'id'   => $this->project->id,
                'name' => $this->project->name,
            ],
            'assignee'    => [
                'id'   => $this->assignee->id,
                'name' => $this->assignee->name,
            ],
            'created_at'  => $this->created_at->toDateString(),
        ];
    }
}
