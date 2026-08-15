<?php

namespace App\Http\Requests\Project;

use App\Rules\Project\ActiveProject;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class ProjectDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', new ActiveProject()],
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
