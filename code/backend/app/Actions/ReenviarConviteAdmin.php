<?php

namespace App\Actions;

use App\Models\Empresa;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReenviarConviteAdmin
{
    public function __construct(private GerarConviteAdmin $gerarConvite) {}

    public function handle(Empresa $empresa): string
    {
        $admin = $empresa->usuarios()->where('papel', 'admin')->orderBy('id')->first();
        if (! $admin instanceof User) {
            throw new HttpException(404, 'Esta empresa não tem admin.');
        }

        if (! $admin->convitePendente()) {
            throw new HttpException(409, 'Esta conta já foi ativada.');
        }

        return $this->gerarConvite->handle($admin);
    }
}
