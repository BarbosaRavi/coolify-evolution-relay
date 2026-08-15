<?php

namespace App\Http\Requests\Project;

use App\Rules\Project\ActiveProject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class ProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', new ActiveProject()],
            'name' => ['required', 'string', 'max:255'],
            'github_repository' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[\w.-]+\/[\w.-]+$/',
                Rule::unique('projects', 'github_repository')->ignore($this->route('id')),
            ],
            'coolify_project' => ['nullable', 'string', 'max:255'],
            'whatsapp_group_jid' => ['nullable', 'string', 'ends_with:@g.us'],
            'notify_push' => ['sometimes', 'boolean'],
            'notify_deploy' => ['sometimes', 'boolean'],
            'branches' => ['nullable', 'array'],
            'branches.*' => ['required_with:branches', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID do Projeto',
            'name' => 'Nome',
            'github_repository' => 'Repositório do GitHub',
            'coolify_project' => 'Projeto no Coolify',
            'whatsapp_group_jid' => 'Grupo do WhatsApp',
            'notify_push' => 'Notificar pushes',
            'notify_deploy' => 'Notificar deploys',
            'branches' => 'Branches',
        ];
    }

    public function messages(): array
    {
        return [
            'github_repository.regex' => 'O repositório deve estar no formato usuario/repositorio.',
            'whatsapp_group_jid.ends_with' => 'O grupo do WhatsApp deve terminar em @g.us.',
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);

        foreach (['notify_push', 'notify_deploy'] as $flag) {
            if ($this->has($flag)) {
                $this->merge([
                    $flag => filter_var($this->input($flag), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }
}
