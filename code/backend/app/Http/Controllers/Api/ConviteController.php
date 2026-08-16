<?php

namespace App\Http\Controllers\Api;

use App\Actions\AceitarConvite;
use App\Actions\GerarConviteAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AceitarConviteRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConviteController extends Controller
{
    use ApiResponse;

    public function show(string $token, GerarConviteAdmin $gerarConvite): JsonResponse
    {
        $user = $gerarConvite->localizar($token);
        if (! $user) {
            return $this->fail('Convite inválido ou já usado.', [], 404);
        }

        if ($user->convite_expira_em === null || $user->convite_expira_em->isPast()) {
            return $this->fail('Este convite expirou. Peça um novo link ao Super Admin.', [], 422);
        }

        return $this->ok([
            'name' => $user->name,
            'email' => $user->email,
            'empresa' => $user->empresa?->nome,
            'expira_em' => $user->convite_expira_em?->toIso8601String(),
        ]);
    }

    public function aceitar(string $token, AceitarConviteRequest $request, AceitarConvite $aceitarConvite): JsonResponse
    {
        try {
            $user = $aceitarConvite->handle($token, $request->validated());
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 404) {
                return $this->fail($e->getMessage(), [], 404);
            }

            throw $e;
        }

        $tokenAcesso = $user->createToken('spa')->plainTextToken;

        return $this->ok([
            'token' => $tokenAcesso,
            'token_type' => 'Bearer',
            'user' => $user->toAuthArray(),
        ], 'Conta ativada');
    }
}
