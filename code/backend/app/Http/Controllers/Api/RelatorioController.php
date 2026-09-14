<?php

namespace App\Http\Controllers\Api;

use App\Actions\CalcularCargaEquipe;
use App\Actions\CalcularMargemCliente;
use App\Actions\ListarAtrasos;
use App\Actions\ListarHorasApontadas;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelatorioController extends Controller
{
    use ApiResponse;

    public function margem(Request $request, CalcularMargemCliente $calcular): JsonResponse
    {
        abort_unless($request->user()?->podeVerFinanceiro(), 403);

        $competencia = $this->competencia($request);

        $linhas = Cliente::query()
            ->ativos()
            ->somenteClientes()
            ->orderBy('nome_fantasia')
            ->get()
            ->map(fn (Cliente $cliente) => $calcular->handle($cliente, $competencia))
            ->values();

        return $this->ok([
            'competencia' => $competencia->format('Y-m'),
            'clientes' => $linhas,
        ]);
    }

    public function atrasos(Request $request, ListarAtrasos $listar): JsonResponse
    {
        abort_unless($request->user()?->podeVerFinanceiro(), 403);

        return $this->ok($listar->handle());
    }

    public function horas(Request $request, ListarHorasApontadas $listar): JsonResponse
    {
        abort_unless($request->user()?->podeVerFinanceiro(), 403);

        return $this->ok($listar->handle($this->competencia($request)));
    }

    public function carga(Request $request, CalcularCargaEquipe $calcular): JsonResponse
    {
        abort_unless($request->user()?->podeVerFinanceiro(), 403);

        return $this->ok($calcular->handle());
    }

    private function competencia(Request $request): Carbon
    {
        $raw = (string) $request->input('competencia', now()->format('Y-m'));
        $valor = preg_match('/^\d{4}-\d{2}$/', $raw) ? $raw.'-01' : $raw;

        try {
            return Carbon::parse($valor)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
