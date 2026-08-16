<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CnpjAlfanumerico implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cnpj = self::normalizar((string) $value);
        if ($cnpj === '') {
            return;
        }

        if (! self::ehValido($cnpj)) {
            $fail('Informe um CNPJ válido (números ou letras, padrão Receita).');
        }
    }

    public static function normalizar(string $valor): string
    {
        return strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $valor));
    }

    public static function ehValido(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (! preg_match('/^[0-9A-Z]{12}[0-9]{2}$/', $cnpj)) {
            return false;
        }

        return $cnpj[12] === (string) self::digito($cnpj, 12)
            && $cnpj[13] === (string) self::digito($cnpj, 13);
    }

    private static function digito(string $cnpj, int $tamanho): int
    {
        $pesos = $tamanho === 12
            ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]
            : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;
        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (ord($cnpj[$i]) - 48) * $pesos[$i];
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
