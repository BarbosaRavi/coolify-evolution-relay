<?php

namespace App\Http\Requests\Project;

use App\Rules\Project\ExistingProject;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class ProjectDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', new ExistingProject()],
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'ID do Projeto',
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $this->merge(['id' => $this->route('id')]);
    }
}
