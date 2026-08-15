<?php

namespace App\Http\Requests\Project;

use App\Traits\IndexRequestTrait;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class ProjectIndexRequest extends FormRequest
{
    use IndexRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'trashed' => ['sometimes', 'nullable', 'boolean'],
        ]);
    }

    public function attributes(): array
    {
        return [
            'page' => 'Página',
            'per_page' => 'Por página',
            'search' => 'Filtro',
            'trashed' => 'Excluídos',
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        if ($this->has('trashed')) {
            $this->merge([
                'trashed' => filter_var($this->input('trashed'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
