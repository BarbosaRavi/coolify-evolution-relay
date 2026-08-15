<?php

namespace App\Rules\Project;

use App\Models\Project;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExistingProject implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Project::query()
            ->withTrashed()
            ->whereKey($value)
            ->exists();

        if (! $exists) {
            $fail('O projeto informado não existe.');
        }
    }
}
