<?php

namespace App\Http\Controllers\Api;

use App\Actions\ExcluirCliente;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClienteController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $clientes = Cliente::query()
            ->withCount('tarefas')
            ->orderBy('nome_fantasia')
            ->get();

        return $this->ok($clientes);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::query()->create($request->validated());

        return $this->ok($cliente, 'Cliente criado', 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return $this->ok($cliente->loadCount('tarefas'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());

        return $this->ok($cliente->fresh(), 'Cliente atualizado');
    }

    public function destroy(Cliente $cliente, ExcluirCliente $excluirCliente): JsonResponse
    {
        $this->authorize('delete', $cliente);

        try {
            $excluirCliente->handle($cliente);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 409) {
                return $this->fail($e->getMessage(), [], 409);
            }

            throw $e;
        }

        return $this->ok(null, 'Cliente removido');
    }
}
