<?php

namespace App\Http\Controllers\Deploy;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deploy\DeployNotifyRequest;
use App\Services\Deploy\DeployService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Webhooks', description: 'Endpoints chamados por máquinas, não por usuários. Não usam JWT: o Coolify se autentica pelo segredo na URL e o GitHub pela assinatura HMAC do corpo.', weight: 10)]
class DeployController extends Controller
{
    public function __construct(protected DeployService $service) {}

    /**
     * @unauthenticated
     */
    #[Endpoint(
        title: 'Notificação de deploy do Coolify',
        description: 'Recebe o payload no formato Slack que o Coolify envia e enfileira a mensagem para o grupo do WhatsApp. Configure esta URL em Notifications → Slack no Coolify.',
    )]
    #[PathParameter(name: 'secret', description: 'O valor de DEPLOY_WEBHOOK_SECRET. É a única credencial do endpoint.')]
    #[Response(status: 200, description: 'Notificação enfileirada')]
    #[Response(status: 403, description: 'Segredo inválido')]
    #[Response(status: 422, description: 'Payload fora do formato esperado')]
    public function notify(DeployNotifyRequest $request): JsonResponse
    {
        $this->service->notify($request->validated());

        return ApiResponse::success(null, 'Notificação de deploy enfileirada com sucesso!', 200);
    }
}
