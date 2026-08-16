<?php

namespace App\Http\Controllers\Api;

use App\Actions\AlterarPropriaSenha;
use App\Actions\RedefinirSenhaComToken;
use App\Actions\SolicitarRecuperacaoSenha;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlterarSenhaRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RedefinirSenhaRequest;
use App\Http\Requests\SolicitarRecuperacaoSenhaRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(LoginRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $user = User::query()->with('empresa')->where('email', $email)->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if ($user->convitePendente()) {
            throw ValidationException::withMessages([
                'email' => ['Convite pendente. Use o link do e-mail para definir a senha.'],
            ]);
        }

        $device = $request->validated('device_name') ?: 'spa';
        $token = $user->createToken($device)->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toAuthArray(),
        ], 'Login realizado');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('empresa');

        return $this->ok($user->toAuthArray(), 'Usuário autenticado');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return $this->ok(null, 'Logout realizado');
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('empresa');
        $atual = $user->currentAccessToken();
        if ($atual && method_exists($atual, 'delete')) {
            $atual->delete();
        }

        $token = $user->createToken('refresh')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->toAuthArray(),
        ], 'Token renovado');
    }

    public function alterarSenha(AlterarSenhaRequest $request, AlterarPropriaSenha $alterarPropriaSenha): JsonResponse
    {
        $alterarPropriaSenha->handle(
            $request->user(),
            $request->validated('senha_atual'),
            $request->validated('password'),
        );

        return $this->ok(null, 'Senha atualizada');
    }

    public function solicitarRecuperacao(
        SolicitarRecuperacaoSenhaRequest $request,
        SolicitarRecuperacaoSenha $solicitar,
    ): JsonResponse {
        $url = $solicitar->handle($request->validated('email'));

        $data = [];
        if (app()->environment(['local', 'testing'])) {
            $data['reset_url'] = $url;
        }

        return $this->ok(
            $data === [] ? null : $data,
            'Enviamos um link para redefinir a senha.',
        );
    }

    public function redefinirSenha(
        RedefinirSenhaRequest $request,
        RedefinirSenhaComToken $redefinir,
    ): JsonResponse {
        $redefinir->handle(
            $request->validated('token'),
            $request->validated('password'),
        );

        return $this->ok(null, 'Senha redefinida. Entre com a nova senha.');
    }
}
