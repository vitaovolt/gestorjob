<?php

namespace App\Http\Controllers\Api;

use App\Actions\CriarColaborador;
use App\Actions\ExcluirColaborador;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreColaboradorRequest;
use App\Http\Requests\UpdateColaboradorRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ColaboradorController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $colaboradores = User::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->select(['id', 'name', 'email', 'papel', 'custo_hora', 'carga_semanal_horas', 'departamento'])
            ->withCount('tarefas')
            ->orderBy('name')
            ->get();

        return $this->ok($colaboradores);
    }

    public function store(StoreColaboradorRequest $request, CriarColaborador $criarColaborador): JsonResponse
    {
        $user = $criarColaborador->handle($request->user()->empresa, $request->validated());

        return $this->ok($this->visivel($user), 'Colaborador criado', 201);
    }

    public function show(Request $request, int $colaborador): JsonResponse
    {
        $user = $this->doTenant($request, $colaborador);
        $this->authorize('view', $user);

        return $this->ok($this->visivel($user->loadCount('tarefas')));
    }

    public function update(UpdateColaboradorRequest $request, int $colaborador): JsonResponse
    {
        $user = $this->doTenant($request, $colaborador);
        $this->authorize('update', $user);
        $dados = $request->validated();

        if ($user->ehUnicoAdminDaEmpresa() && ($dados['papel'] ?? $user->papel) !== 'admin') {
            return $this->fail('Não dá para tirar o papel do único admin da agência.', [], 409);
        }

        if (empty($dados['password'])) {
            unset($dados['password']);
        }
        unset($dados['password_confirmation']);

        $user->update($dados);

        return $this->ok($this->visivel($user->fresh()), 'Colaborador atualizado');
    }

    public function destroy(Request $request, int $colaborador, ExcluirColaborador $excluirColaborador): JsonResponse
    {
        $user = $this->doTenant($request, $colaborador);
        $this->authorize('delete', $user);

        try {
            $excluirColaborador->handle($request->user(), $user);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 409) {
                return $this->fail($e->getMessage(), [], 409);
            }

            throw $e;
        }

        return $this->ok(null, 'Colaborador removido');
    }

    private function doTenant(Request $request, int $id): User
    {
        return User::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->findOrFail($id);
    }

    /** @return array<string, mixed> */
    private function visivel(User $user): array
    {
        return $user->only([
            'id', 'name', 'email', 'papel', 'custo_hora', 'carga_semanal_horas', 'departamento',
        ]) + ['tarefas_count' => $user->tarefas_count ?? $user->tarefas()->count()];
    }
}
