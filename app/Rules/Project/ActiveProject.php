<?php

namespace App\Rules\Project;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ActiveProject implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Project::query()
            ->whereKey($value)
            ->exists();

        if (! $exists) {
            $fail('O projeto informado não existe ou está excluído.');
        }
    }
}
