<?php

namespace App\Services\Project;

use App\Exceptions\ApiException;
use App\Http\Resources\Project\ProjectCollection;
use App\Http\Resources\Project\ProjectResource;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function index(array $data): ProjectCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $trashed = $data['trashed'] ?? null;

        $query = Project::query()
            ->when($trashed, fn ($query) => $query->withTrashed())
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('github_repository', 'like', "%{$search}%")
                        ->orWhere('coolify_project', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ProjectCollection($query);
    }

    public function show(array $data): ProjectResource
    {
        return new ProjectResource(Project::findOrFail($data['id']));
    }

    public function store(array $data): ProjectResource
    {
        return DB::transaction(function () use ($data): ProjectResource {
            $this->guardAgainstUselessProject($data);

            $project = Project::create([
                'name' => $data['name'],
                'github_repository' => $data['github_repository'] ?? null,
                'coolify_project' => $data['coolify_project'] ?? null,
                'whatsapp_group_jid' => $data['whatsapp_group_jid'] ?? null,
                'notify_push' => $data['notify_push'] ?? true,
                'notify_deploy' => $data['notify_deploy'] ?? true,
                'branches' => $data['branches'] ?? null,
            ]);

            return new ProjectResource($project);
        });
    }

    public function update(array $data): ProjectResource
    {
        $project = Project::findOrFail($data['id']);

        return DB::transaction(function () use ($data, $project): ProjectResource {
            $this->guardAgainstUselessProject($data);

            $project->fill([
                'name' => $data['name'],
                'github_repository' => $data['github_repository'] ?? null,
                'coolify_project' => $data['coolify_project'] ?? null,
                'whatsapp_group_jid' => $data['whatsapp_group_jid'] ?? null,
                'notify_push' => $data['notify_push'] ?? $project->notify_push,
                'notify_deploy' => $data['notify_deploy'] ?? $project->notify_deploy,
                'branches' => $data['branches'] ?? null,
            ])->save();

            return new ProjectResource($project->refresh());
        });
    }

    public function delete(array $data): void
    {
        DB::transaction(function () use ($data): void {
            Project::findOrFail($data['id'])->delete();
        });
    }

    public function restore(array $data): ProjectResource
    {
        return DB::transaction(function () use ($data): ProjectResource {
            $project = Project::withTrashed()->findOrFail($data['id']);
            $project->restore();

            return new ProjectResource($project->refresh());
        });
    }

    public function destroy(array $data): void
    {
        DB::transaction(function () use ($data): void {
            Project::withTrashed()->findOrFail($data['id'])->forceDelete();
        });
    }

    /**
     * Usado pelos comandos de console, que trabalham com os modelos direto.
     */
    public function all(): Collection
    {
        return Project::query()->orderBy('name')->get();
    }

    public function findByIdentifier(string $identifier): ?Project
    {
        return Project::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('name', $identifier)
                    ->orWhere('github_repository', $identifier)
                    ->orWhere('coolify_project', $identifier);
            })
            ->first();
    }

    public function findForGithubRepository(string $repository): ?Project
    {
        return Project::query()
            ->where('github_repository', $repository)
            ->first();
    }

    public function findForCoolifyProject(string $name): ?Project
    {
        return Project::query()
            ->where('coolify_project', $name)
            ->first();
    }

    private function guardAgainstUselessProject(array $data): void
    {
        if (blank($data['github_repository'] ?? null) && blank($data['coolify_project'] ?? null)) {
            throw new ApiException(
                'Informe ao menos o repositório do GitHub ou o projeto no Coolify, senão nenhum evento será associado a este projeto.',
                422,
            );
        }
    }
}
