<?php

namespace App\Http\Controllers\Api;

use App\Actions\ExcluirCliente;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Support\ApiResponse;
use App\Support\Auditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ClienteController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cliente::class);

        $clientes = Cliente::query()
            ->withCount('tarefas')
            ->when($request->string('papel')->toString() === 'cliente', fn ($q) => $q->somenteClientes())
            ->when($request->string('papel')->toString() === 'fornecedor', fn ($q) => $q->somenteFornecedores())
            ->orderBy('nome_fantasia')
            ->get();

        return $this->ok($clientes);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $cliente = Cliente::query()->create($request->validated());
        Auditoria::registrar(
            'cliente.criar',
            $cliente->rotuloPapel().' criado: '.$cliente->nome_fantasia,
            $cliente,
            ['nome_fantasia' => $cliente->nome_fantasia, 'papeis' => $this->papeis($cliente)],
        );

        return $this->ok($cliente, $cliente->rotuloPapel().' criado', 201);
    }

    public function show(Cliente $cliente): JsonResponse
    {
        $this->authorize('view', $cliente);

        return $this->ok($cliente->loadCount('tarefas'));
    }

    public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
    {
        $cliente->update($request->validated());
        $cliente = $cliente->fresh();
        Auditoria::registrar(
            'cliente.atualizar',
            $cliente->rotuloPapel().' atualizado: '.$cliente->nome_fantasia,
            $cliente,
            ['nome_fantasia' => $cliente->nome_fantasia, 'papeis' => $this->papeis($cliente)],
        );

        return $this->ok($cliente, $cliente->rotuloPapel().' atualizado');
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

        Auditoria::registrar(
            'cliente.excluir',
            'Cadastro removido: '.$cliente->nome_fantasia,
            $cliente,
            ['nome_fantasia' => $cliente->nome_fantasia],
        );

        return $this->ok(null, $cliente->rotuloPapel().' removido');
    }

    /**
     * @return list<string>
     */
    private function papeis(Cliente $cliente): array
    {
        $papeis = [];
        if ($cliente->eh_cliente) {
            $papeis[] = 'cliente';
        }
        if ($cliente->eh_fornecedor) {
            $papeis[] = 'fornecedor';
        }

        return $papeis;
    }
}
