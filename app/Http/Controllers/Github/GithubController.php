<?php

namespace App\Http\Controllers\Github;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Github\GithubPushRequest;
use App\Services\Github\GithubService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Webhooks', weight: 10)]
class GithubController extends Controller
{
    public function __construct(protected GithubService $service) {}

    /**
     * @unauthenticated
     */
    #[Endpoint(
        title: 'Push do GitHub',
        description: 'Recebe o evento `push` do GitHub e envia o resumo dos commits para o grupo. O evento `ping`, disparado ao salvar o webhook, é aceito e ignorado.',
    )]
    #[HeaderParameter(name: 'X-Hub-Signature-256', description: 'HMAC SHA-256 do corpo cru, assinado com GITHUB_WEBHOOK_SECRET.', required: true)]
    #[HeaderParameter(name: 'X-GitHub-Event', description: 'Tipo do evento; apenas "push" gera mensagem.', required: true)]
    #[Response(status: 200, description: 'Evento processado')]
    #[Response(status: 403, description: 'Assinatura inválida')]
    #[Response(status: 422, description: 'Payload fora do formato esperado')]
    public function push(GithubPushRequest $request): JsonResponse
    {
        $this->service->push(
            $request->validated(),
            (string) $request->header('X-GitHub-Event'),
        );

        return ApiResponse::success(null, 'Evento processado com sucesso!', 200);
    }
}
