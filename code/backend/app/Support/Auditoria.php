<?php

namespace App\Support;

use App\Models\LogAcao;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class Auditoria
{
    public static function registrar(string $acao, string $descricao, ?Model $recurso = null, array $payload = []): void
    {
        try {
            $user = auth()->user();
            $request = request();

            LogAcao::query()->create([
                'user_id' => $user?->id,
                'user_nome' => $user?->name,
                'user_login' => $user?->email,
                'empresa_id' => $user?->empresa_id,
                'acao' => $acao,
                'recurso_tipo' => $recurso ? class_basename($recurso) : null,
                'recurso_id' => $recurso?->getKey(),
                'descricao' => $descricao,
                'payload' => $payload === [] ? null : $payload,
                'ip' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 512) ?: null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
