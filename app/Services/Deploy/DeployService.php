<?php

namespace App\Services\Deploy;

use App\Exceptions\ApiException;
use App\Jobs\SendWhatsappMessageJob;
use App\Models\Project;
use App\Services\Project\ProjectService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DeployService
{
    public function __construct(protected ProjectService $projects) {}

    public function notify(array $data): void
    {
        $attachment = $data['attachments'][0];
        $fields = collect($attachment['fields'] ?? []);

        $project = $this->resolveProject($fields);

        if ($project && ! $project->notify_deploy) {
            return;
        }

        $groupJid = $project?->whatsapp_group_jid ?: (string) config('evolution.group_jid');

        if ($groupJid === '') {
            throw new ApiException('Grupo do WhatsApp não configurado', 500);
        }

        $text = $fields
            ->map(fn (array $field): string => "*{$field['title']}:* {$field['value']}")
            ->prepend($attachment['title'] ?? 'Coolify')
            ->when(
                filled($attachment['text'] ?? null),
                fn ($lines) => $lines->push($attachment['text'])
            )
            ->implode("\n");

        SendWhatsappMessageJob::dispatch($text, $groupJid);
    }

    private function resolveProject(Collection $fields): ?Project
    {
        $field = $fields->first(fn (array $field): bool => Str::contains(
            Str::lower($field['title'] ?? ''),
            ['project', 'projeto', 'application', 'aplicação'],
        ));

        $name = $field['value'] ?? null;

        if (! filled($name)) {
            return null;
        }

        return $this->projects->findForCoolifyProject($name);
    }
}
