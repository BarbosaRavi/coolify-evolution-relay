<?php

namespace App\Http\Controllers\Project;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectDeleteRequest;
use App\Http\Requests\Project\ProjectDestroyRequest;
use App\Http\Requests\Project\ProjectIndexRequest;
use App\Http\Requests\Project\ProjectRestoreRequest;
use App\Http\Requests\Project\ProjectShowRequest;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Services\Project\ProjectService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

#[Group(name: 'Projetos', description: 'Cadastro dos projetos que recebem notificações no WhatsApp. Um projeto é identificado pelo repositório do GitHub (para pushes) e/ou pelo nome do projeto no Coolify (para deploys). Eventos de projetos não cadastrados continuam sendo enviados para o grupo padrão definido em WHATSAPP_GROUP_JID.')]
class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected ProjectService $service) {}

    #[Endpoint(
        title: 'Listar projetos',
        description: 'Retorna os projetos paginados. Use `search` para filtrar por nome, repositório ou projeto do Coolify, e `trashed` para incluir os excluídos.',
    )]
    #[Response(status: 200, description: 'Projetos listados com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.view')]
    public function index(ProjectIndexRequest $request): JsonResponse
    {
        $projects = $this->service->index($request->validated());

        return ApiResponse::success($projects, 'Projetos listados com sucesso!', 200);
    }

    #[Endpoint(title: 'Visualizar projeto', description: 'Retorna um projeto pelo seu UUID.')]
    #[Response(status: 200, description: 'Projeto visualizado com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.view')]
    #[Response(status: 422, description: 'O projeto não existe ou está excluído')]
    public function show(ProjectShowRequest $request): JsonResponse
    {
        $project = $this->service->show($request->validated());

        return ApiResponse::success($project, 'Projeto visualizado com sucesso!', 200);
    }

    #[Endpoint(
        title: 'Criar projeto',
        description: 'Cadastra um projeto. É obrigatório informar `github_repository` e/ou `coolify_project` — sem pelo menos um deles nenhum evento consegue ser associado ao projeto. Omitir `whatsapp_group_jid` faz o projeto usar o grupo padrão.',
    )]
    #[Response(status: 200, description: 'Projeto criado com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.create')]
    #[Response(status: 422, description: 'Dados inválidos ou repositório já cadastrado')]
    public function store(ProjectStoreRequest $request): JsonResponse
    {
        $project = $this->service->store($request->validated());

        return ApiResponse::success($project, 'Projeto criado com sucesso!', 200);
    }

    #[Endpoint(title: 'Atualizar projeto', description: 'Atualiza um projeto existente. Campos omitidos que aceitam nulo são limpos.')]
    #[Response(status: 200, description: 'Projeto atualizado com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.update')]
    #[Response(status: 422, description: 'Dados inválidos ou repositório já usado por outro projeto')]
    public function update(ProjectUpdateRequest $request): JsonResponse
    {
        $project = $this->service->update($request->validated());

        return ApiResponse::success($project, 'Projeto atualizado com sucesso!', 200);
    }

    #[Endpoint(
        title: 'Deletar projeto',
        description: 'Exclusão lógica (soft delete). O projeto para de rotear eventos e volta a cair no grupo padrão, mas o repositório continua reservado até ser destruído.',
    )]
    #[Response(status: 200, description: 'Projeto deletado com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.delete')]
    #[Response(status: 422, description: 'O projeto não existe ou já está excluído')]
    public function delete(ProjectDeleteRequest $request): JsonResponse
    {
        $this->service->delete($request->validated());

        return ApiResponse::success(null, 'Projeto deletado com sucesso!', 200);
    }

    #[Endpoint(title: 'Restaurar projeto', description: 'Desfaz a exclusão lógica de um projeto.')]
    #[Response(status: 200, description: 'Projeto restaurado com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.restore')]
    #[Response(status: 422, description: 'O projeto não existe ou não está excluído')]
    public function restore(ProjectRestoreRequest $request): JsonResponse
    {
        $project = $this->service->restore($request->validated());

        return ApiResponse::success($project, 'Projeto restaurado com sucesso!', 200);
    }

    #[Endpoint(
        title: 'Destruir projeto',
        description: 'Exclusão definitiva. Use quando quiser liberar o repositório do GitHub para ser cadastrado em outro projeto.',
    )]
    #[Response(status: 200, description: 'Projeto destruído com sucesso')]
    #[Response(status: 401, description: 'Token ausente, inválido ou expirado')]
    #[Response(status: 403, description: 'Usuário sem a permissão project.destroy')]
    #[Response(status: 422, description: 'O projeto não existe')]
    public function destroy(ProjectDestroyRequest $request): JsonResponse
    {
        $this->service->destroy($request->validated());

        return ApiResponse::success(null, 'Projeto destruído com sucesso!', 200);
    }
}
