<?php

namespace App\Jobs;

use App\Exceptions\ApiException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $text,
        public string $groupJid,
    ) {
        $this->queue = 'whatsapp';
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $endpoint = config('evolution.url').'/message/sendText/'.config('evolution.instance');

        $response = Http::withHeaders(['apikey' => config('evolution.api_key')])
            ->timeout(15)
            ->post($endpoint, [
                'number' => $this->groupJid,
                'text'   => $this->text,
            ]);

        if ($response->failed()) {
            throw new ApiException('Falha ao enviar mensagem: '.$response->body(), 502);
        }
    }
}