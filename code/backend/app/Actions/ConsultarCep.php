<?php

namespace App\Actions;

use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConsultarCep
{
    /**
     * @return array{cep:string, logradouro:?string, complemento:?string, bairro:?string, cidade:?string, uf:?string, codigo_ibge:?string}
     */
    public function handle(string $cep): array
    {
        $cep = preg_replace('/\D+/', '', $cep) ?? '';
        if (strlen($cep) !== 8) {
            throw new HttpException(422, 'Informe um CEP com 8 dígitos.');
        }

        $resposta = Http::timeout(5)
            ->retry(2, 200)
            ->acceptJson()
            ->get("https://viacep.com.br/ws/{$cep}/json/")
            ->throw()
            ->json();

        if (! is_array($resposta) || ! empty($resposta['erro'])) {
            throw new HttpException(404, 'CEP não encontrado. Você pode preencher o endereço na mão.');
        }

        return [
            'cep' => preg_replace('/\D+/', '', (string) ($resposta['cep'] ?? $cep)) ?: $cep,
            'logradouro' => $this->texto($resposta['logradouro'] ?? null),
            'complemento' => $this->texto($resposta['complemento'] ?? null),
            'bairro' => $this->texto($resposta['bairro'] ?? null),
            'cidade' => $this->texto($resposta['localidade'] ?? null),
            'uf' => strtoupper(substr((string) ($resposta['uf'] ?? ''), 0, 2)) ?: null,
            'codigo_ibge' => $this->texto($resposta['ibge'] ?? null),
        ];
    }

    private function texto(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
