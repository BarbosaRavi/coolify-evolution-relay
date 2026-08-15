<?php

namespace App\Http\Requests\Deploy;

use Illuminate\Foundation\Http\FormRequest;

class DeployNotifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $secret = (string) config('evolution.webhook_secret');

        return $secret !== '' && hash_equals($secret, (string) $this->route('secret'));
    }

    public function rules(): array
    {
        return [
            'attachments'                  => ['required', 'array', 'min:1'],
            'attachments.0.title'          => ['nullable', 'string'],
            'attachments.0.color'          => ['nullable', 'string'],
            'attachments.0.text'           => ['nullable', 'string'],
            'attachments.0.fields'         => ['nullable', 'array'],
            'attachments.0.fields.*.title' => ['required_with:attachments.0.fields', 'string'],
            'attachments.0.fields.*.value' => ['required_with:attachments.0.fields', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'attachments'          => 'Anexos',
            'attachments.0.title'  => 'Título',
            'attachments.0.color'  => 'Cor',
            'attachments.0.text'   => 'Descrição',
            'attachments.0.fields' => 'Campos',
        ];
    }
}