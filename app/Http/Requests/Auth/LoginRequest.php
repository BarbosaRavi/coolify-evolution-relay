<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['email', 'required'],
            'password' => ['string', 'required', 'min:6']
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'Email',
            'password' => 'Senha'
        ];
    }
}