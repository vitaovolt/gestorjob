<?php

namespace App\Http\Controllers\Api;

use App\Actions\CriarEmpresaComConvite;
use App\Actions\ReenviarConviteAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpresaRequest;
use App\Http\Requests\UpdateEmpresaRequest;
use App\Models\Empresa;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmpresaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Empresa::class);

        $empresas = Empresa::query()
            ->withCount('usuarios')
            ->with(['usuarios' => fn ($q) => $q->where('papel', 'admin')->orderBy('id')])
            ->orderBy('nome')
            ->get()
            ->map(fn (Empresa $empresa) => $this->visivel($empresa));

        return $this->ok($empresas);
    }

    public function store(StoreEmpresaRequest $request, CriarEmpresaComConvite $criarEmpresa): JsonResponse
    {
        $criado = $criarEmpresa->handle($request->validated());

        return $this->ok(
            $this->visivel($criado['empresa']->load(['usuarios' => fn ($q) => $q->where('papel', 'admin')->orderBy('id')]))
                + ['convite_url' => $criado['convite_url']],
            'Empresa criada. Convite enviado.',
            201,
        );
    }

    public function showPlataforma(Request $request, Empresa $empresa): JsonResponse
    {
        $this->authorize('view', $empresa);

        $empresa->loadCount('usuarios');
        $empresa->load(['usuarios' => fn ($q) => $q->where('papel', 'admin')->orderBy('id')]);

        return $this->ok($this->visivel($empresa));
    }

    public function update(UpdateEmpresaRequest $request, Empresa $empresa): JsonResponse
    {
        $empresa->update($request->validated());
        $empresa->loadCount('usuarios');
        $empresa->load(['usuarios' => fn ($q) => $q->where('papel', 'admin')->orderBy('id')]);

        return $this->ok($this->visivel($empresa), 'Empresa atualizada');
    }

    public function reenviarConvite(Request $request, Empresa $empresa, ReenviarConviteAdmin $reenviar): JsonResponse
    {
        abort_unless($request->user()?->ehSuperAdmin(), 403);

        try {
            $url = $reenviar->handle($empresa);
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), [], $e->getStatusCode());
        }

        $empresa->loadCount('usuarios');
        $empresa->load(['usuarios' => fn ($q) => $q->where('papel', 'admin')->orderBy('id')]);

        return $this->ok($this->visivel($empresa) + ['convite_url' => $url], 'Convite reenviado');
    }

    public function show(Request $request): JsonResponse
    {
        $empresa = $request->user()->empresa;
        abort_if($empresa === null, 404);

        return $this->ok($empresa->loadCount('usuarios'));
    }

    /** @return array<string, mixed> */
    private function visivel(Empresa $empresa): array
    {
        $admin = $empresa->usuarios->first();

        return [
            'id' => $empresa->id,
            'nome' => $empresa->nome,
            'plano' => $empresa->plano,
            'limite_usuarios' => $empresa->limite_usuarios,
            'status' => $empresa->status,
            'usuarios_count' => $empresa->usuarios_count ?? $empresa->usuarios()->count(),
            'admin' => $admin instanceof User ? [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'convite_pendente' => $admin->convitePendente(),
            ] : null,
        ];
    }
}
