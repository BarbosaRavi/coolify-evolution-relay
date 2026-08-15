<?php

namespace App\Http\Resources\Project;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'github_repository' => $this->github_repository,
            'coolify_project' => $this->coolify_project,
            'whatsapp_group_jid' => $this->whatsapp_group_jid,
            'notify_push' => $this->notify_push,
            'notify_deploy' => $this->notify_deploy,
            'branches' => $this->branches,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
