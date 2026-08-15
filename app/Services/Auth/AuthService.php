<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService 
{
    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if ($user && Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {

            $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
            $token = JWTAuth::fromUser($user);

            $user->update(['last_login' => now()]);

            return [
                'user' => new UserResource($user->load(['roles.permissions'])), 
                'token' => $token, 
                'refresh_expires_in' => $refreshTtlInSeconds
            ];
        }

        throw new ApiException('Email e/ou senha inválido', 401);
    }

    public function me(): UserResource
    {
        $user = Auth::user();
        return new UserResource($user->load('roles.permissions'));
    }

    public function refreshToken(): array
    {
        $refreshTtlInSeconds = Config::get('jwt.refresh_ttl') * 60;
        $token = auth('api')->refresh();

        return [
            'token' => $token,
            'refresh_expires_in' => $refreshTtlInSeconds,
        ];
    }

    public function logout()
    {
        Auth::logout();
    }
}