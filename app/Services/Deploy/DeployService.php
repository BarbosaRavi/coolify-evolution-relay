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
    /**
     * Campos do payload do Coolify que devem aparecer na mensagem.
     * Os demais (ex.: "Deployment Logs") são descartados.
     */
    private const ALLOWED_FIELDS = ['project', 'projeto', 'environment', 'ambiente'];

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

        SendWhatsappMessageJob::dispatch($this->buildMessage($attachment, $fields), $groupJid);
    }

    /**
     * Monta a mensagem apenas com o status do deploy, a URL da aplicação,
     * o projeto e o ambiente — sem o link de logs enviado pelo Coolify.
     */
    private function buildMessage(array $attachment, Collection $fields): string
    {
        $status = trim((string) ($attachment['title'] ?? 'Coolify'));

        $details = $fields
            ->filter(fn (array $field): bool => in_array(
                Str::lower(trim((string) ($field['title'] ?? ''))),
                self::ALLOWED_FIELDS,
                true,
            ))
            ->map(fn (array $field): string => "*{$field['title']}:* {$field['value']}")
            ->implode("\n");

        return collect([
            $status,
            $this->stripLinks((string) ($attachment['text'] ?? '')),
            $details,
        ])->filter(fn (string $block): bool => filled($block))->implode("\n\n");
    }

    /**
     * Descarta as linhas que contêm link no formato Slack (<url|rótulo>),
     * usado pelo Coolify para o link dos logs, e as linhas vazias.
     */
    private function stripLinks(string $text): string
    {
        return collect(preg_split('/\R/', $text))
            ->map(fn (string $line): string => rtrim($line))
            ->reject(fn (string $line): bool => (bool) preg_match('/<[^|>\s]+\|[^>]*>/', $line))
            ->filter(fn (string $line): bool => filled(trim($line)))
            ->implode("\n");
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
