<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'github_repository',
    'coolify_project',
    'whatsapp_group_jid',
    'notify_push',
    'notify_deploy',
    'branches',
])]
class Project extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'notify_push' => 'boolean',
            'notify_deploy' => 'boolean',
            'branches' => 'array',
        ];
    }
}
