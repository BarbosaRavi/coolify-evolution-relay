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

    private const SUCCESS_EMOJI = '✅';

    private const FAILURE_EMOJI = '❌';

    /**
     * Palavras que aparecem no título enviado pelo Coolify e indicam falha.
     * Verificadas antes das de sucesso, já que "deployment failed" contém "deploy".
     */
    private const FAILURE_WORDS = ['fail', 'error', 'falha', 'erro', 'unhealthy', 'stopped', 'parado'];

    private const SUCCESS_WORDS = ['success', 'sucesso', 'deployed', 'healthy', 'implantad', 'restored'];

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
        $emoji = $this->statusEmoji($attachment, $status);

        if ($emoji !== '') {
            $status = "{$emoji} {$status}";
        }

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

    /**
     * Escolhe o emoji do status a partir do título do Coolify e, se ele não for
     * conclusivo, da cor do anexo. Retorna string vazia quando não dá para saber
     * se o deploy deu certo, para não marcar uma falha como sucesso.
     */
    private function statusEmoji(array $attachment, string $status): string
    {
        if (Str::contains($status, [self::SUCCESS_EMOJI, self::FAILURE_EMOJI])) {
            return '';
        }

        $title = Str::lower($status);

        if (Str::contains($title, self::FAILURE_WORDS)) {
            return self::FAILURE_EMOJI;
        }

        if (Str::contains($title, self::SUCCESS_WORDS)) {
            return self::SUCCESS_EMOJI;
        }

        $color = Str::lower(trim((string) ($attachment['color'] ?? '')));

        return match (true) {
            Str::contains($color, ['danger', 'red', 'ff0000']) => self::FAILURE_EMOJI,
            Str::contains($color, ['good', 'green', '00ff00', '008000']) => self::SUCCESS_EMOJI,
            default => '',
        };
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
