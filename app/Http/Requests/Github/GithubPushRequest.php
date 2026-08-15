<?php

namespace App\Http\Requests\Github;

use Illuminate\Foundation\Http\FormRequest;

class GithubPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        $secret    = (string) config('github.webhook_secret');
        $signature = (string) $this->header('X-Hub-Signature-256');

        if ($secret === '' || $signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $this->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    public function rules(): array
    {
        if ($this->header('X-GitHub-Event') !== 'push') {
            return [];
        }

        return [
            'ref'                     => ['required', 'string'],
            'repository.full_name'    => ['required', 'string'],
            'pusher.name'             => ['nullable', 'string'],
            'head_commit'             => ['nullable', 'array'],
            'commits'                 => ['nullable', 'array'],
            'commits.*.id'            => ['required_with:commits', 'string'],
            'commits.*.message'       => ['required_with:commits', 'string'],
            'compare'                 => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ref'                  => 'Referência',
            'repository.full_name' => 'Repositório',
            'pusher.name'          => 'Autor',
        ];
    }
}
