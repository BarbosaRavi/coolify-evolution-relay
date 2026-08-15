<?php

namespace App\Rules\Project;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TrashedProject implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Project::query()
            ->onlyTrashed()
            ->whereKey($value)
            ->exists();

        if (! $exists) {
            $fail('O projeto informado não existe ou não está excluído.');
        }
    }
}
