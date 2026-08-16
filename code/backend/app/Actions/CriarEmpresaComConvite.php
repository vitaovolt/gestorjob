<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CriarEmpresaComConvite
{
    public function __construct(private GerarConviteAdmin $gerarConvite) {}

    /**
     * @param  array{nome: string, plano: string, limite_usuarios: int, admin_nome: string, admin_email: string}  $dados
     * @return array{empresa: Empresa, admin: User, convite_url: string}
     */
    public function handle(array $dados): array
    {
        [$empresa, $admin] = DB::transaction(function () use ($dados) {
            $empresa = Empresa::query()->create([
                'nome' => $dados['nome'],
                'plano' => $dados['plano'],
                'limite_usuarios' => $dados['limite_usuarios'],
                'status' => 'ativo',
            ]);

            $admin = User::query()->create([
                'empresa_id' => $empresa->id,
                'name' => $dados['admin_nome'],
                'email' => $dados['admin_email'],
                'password' => Str::password(32),
                'papel' => 'admin',
            ]);

            return [$empresa, $admin];
        });

        $url = $this->gerarConvite->handle($admin->load('empresa'));

        return [
            'empresa' => $empresa->loadCount('usuarios'),
            'admin' => $admin->fresh(),
            'convite_url' => $url,
        ];
    }
}
