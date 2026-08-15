<?php

namespace App\Http\Controllers\Deploy;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deploy\DeployNotifyRequest;
use App\Services\Deploy\DeployService;
use Illuminate\Http\JsonResponse;

class DeployController extends Controller
{
    public function __construct(protected DeployService $service) {}

    public function notify(DeployNotifyRequest $request): JsonResponse
    {
        $this->service->notify($request->validated());

        return ApiResponse::success(null, 'Notificação de deploy enfileirada com sucesso!', 200);
    }
}
