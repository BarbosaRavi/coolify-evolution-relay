<?php

namespace App\Http\Middlewares;

use App\Builder\ApiResponse;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if (!$user) {
                return ApiResponse::error("Não autorizado", null, 401);
            }
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return ApiResponse::error("Token inválido", null, 401);
            }
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return ApiResponse::error("Token expirado", null, 401);
            }

            return ApiResponse::error("Não autorizado", null, 401);
        }

        return $next($request);
    }
}