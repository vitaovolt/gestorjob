<?php

namespace App\Http\Controllers\Api;

use App\Actions\CalcularMargemCliente;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MargemController extends Controller
{
    use ApiResponse;

    public function index(Request $request, CalcularMargemCliente $calcular): JsonResponse
    {
        abort_unless($request->user()?->podeVerFinanceiro(), 403);

        $competencia = Carbon::parse($request->input('competencia', now()->format('Y-m').'-01'))
            ->startOfMonth();

        $linhas = Cliente::query()
            ->ativos()
            ->orderBy('nome_fantasia')
            ->get()
            ->map(fn (Cliente $cliente) => $calcular->handle($cliente, $competencia))
            ->values();

        return $this->ok([
            'competencia' => $competencia->format('Y-m'),
            'clientes' => $linhas,
        ]);
    }
}
