<?php

namespace App\Services\Github;

use App\Jobs\SendWhatsappMessageJob;
use App\Services\Project\ProjectService;
use Illuminate\Support\Str;

class GithubService
{
    private const PUSH_EMOJI = '🙏';

    public function __construct(protected ProjectService $projects) {}

    public function push(array $data, string $event): void
    {
        if ($event !== 'push') {
            return;
        }

        $repository = $data['repository']['full_name'];
        $project = $this->projects->findForGithubRepository($repository);

        if ($project && ! $project->notify_push) {
            return;
        }

        $branch = Str::afterLast($data['ref'], '/');
        $branches = $project?->branches ?: config('github.branches');

        if (! in_array($branch, $branches, true)) {
            return;
        }

        $commits = $data['commits'] ?? [];

        if ($commits === []) {
            return;
        }

        $lines = collect($commits)
            ->take(10)
            ->map(fn (array $commit): string => sprintf(
                '• `%s` %s',
                substr($commit['id'], 0, 7),
                Str::before($commit['message'], "\n"),
            ));

        $text = collect([
            self::PUSH_EMOJI." *Novo push em {$repository}*",
            "*Branch:* {$branch}",
            '*Autor:* '.($data['pusher']['name'] ?? 'desconhecido'),
            '',
            $lines->implode("\n"),
        ])->implode("\n");

        $groupJid = $project?->whatsapp_group_jid ?: (string) config('evolution.group_jid');

        SendWhatsappMessageJob::dispatch($text, $groupJid);
    }
}
