<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConsultarCep;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CepController extends Controller
{
    use ApiResponse;

    public function show(string $cep, ConsultarCep $consultar): JsonResponse
    {
        abort_unless(auth()->user()?->empresa_id !== null, 403);

        try {
            return $this->ok($consultar->handle($cep), 'Endereço encontrado');
        } catch (HttpException $e) {
            return $this->fail($e->getMessage(), [], $e->getStatusCode());
        }
    }
}
