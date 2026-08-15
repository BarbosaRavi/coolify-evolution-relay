<?php

namespace App\Http\Controllers\Auth;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Autenticação', description: 'Login por JWT. O token retornado deve ser enviado no header `Authorization: Bearer <token>` nas rotas protegidas.', weight: 0)]
class AuthController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected AuthService $service) {}

    /**
     * @unauthenticated
     */
    #[Endpoint(title: 'Login', description: 'Autentica o usuário e devolve o token JWT junto com os dados e permissões dele.')]
    #[Response(status: 200, description: 'Autenticado com sucesso')]
    #[Response(status: 401, description: 'Email e/ou senha inválido')]
    #[Response(status: 403, description: 'Email ainda não confirmado')]
    public function login(LoginRequest $request): JsonResponse
    {
        $admin = $this->service->login($request->validated());

        return ApiResponse::success($admin, 'Usuário com sucesso!', 200);
    }

    #[Endpoint(title: 'Usuário autenticado', description: 'Retorna o usuário do token, com seus cargos e permissões.')]
    #[Response(status: 200, description: 'Dados do usuário')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    public function me(): JsonResponse
    {
        $admin = $this->service->me();

        return ApiResponse::success($admin, 'Dados do usuário', 200);
    }

    /**
     * @unauthenticated
     */
    #[Endpoint(title: 'Renovar token', description: 'Troca um token ainda dentro da janela de refresh por um novo.')]
    #[Response(status: 200, description: 'Token atualizado com sucesso')]
    #[Response(status: 401, description: 'Token inválido ou fora da janela de refresh')]
    public function refreshToken(): JsonResponse
    {
        $admin = $this->service->refreshToken();

        return ApiResponse::success($admin, 'Token atualizado com sucesso!', 200);
    }
}
