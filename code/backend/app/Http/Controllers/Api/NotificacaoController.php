<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacao;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ! $user->ehSuperAdmin(), 403);

        $itens = Notificacao::query()
            ->where('user_id', $user->id)
            ->orderByRaw('lida_em is null desc')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get()
            ->map(fn (Notificacao $n) => $n->paraApi())
            ->values();

        return $this->ok($itens);
    }

    public function naoLidas(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ! $user->ehSuperAdmin(), 403);

        $total = Notificacao::query()
            ->where('user_id', $user->id)
            ->naoLidas()
            ->count();

        return $this->ok(['total' => $total]);
    }

    public function marcarLida(Request $request, Notificacao $notificacao): JsonResponse
    {
        $this->garantirDona($request, $notificacao);
        $notificacao->marcarComoLida();

        return $this->ok($notificacao->fresh()->paraApi(), 'Notificação lida');
    }

    public function marcarTodas(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ! $user->ehSuperAdmin(), 403);

        Notificacao::query()
            ->where('user_id', $user->id)
            ->naoLidas()
            ->update(['lida_em' => now()]);

        return $this->ok(null, 'Todas marcadas como lidas');
    }

    private function garantirDona(Request $request, Notificacao $notificacao): void
    {
        $user = $request->user();
        abort_unless($user && ! $user->ehSuperAdmin(), 403);
        abort_unless((int) $notificacao->user_id === (int) $user->id, 404);
    }
}
