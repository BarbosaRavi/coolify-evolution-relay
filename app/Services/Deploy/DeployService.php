<?php

namespace App\Services\Deploy;

use App\Exceptions\ApiException;
use App\Jobs\SendWhatsappMessageJob;

class DeployService
{
    public function notify(array $data): void
    {
        $groupJid = (string) config('evolution.group_jid');

        if ($groupJid === '') {
            throw new ApiException('Grupo do WhatsApp não configurado', 500);
        }

        $attachment = $data['attachments'][0];

        $text = collect($attachment['fields'] ?? [])
            ->map(fn (array $field): string => "*{$field['title']}:* {$field['value']}")
            ->prepend($attachment['title'] ?? 'Coolify')
            ->when(
                filled($attachment['text'] ?? null),
                fn ($lines) => $lines->push($attachment['text'])
            )
            ->implode("\n");

        SendWhatsappMessageJob::dispatch($text, $groupJid);
    }
}
