<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfOuCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $doc = CnpjAlfanumerico::normalizar((string) $value);
        if ($doc === '') {
            return;
        }

        if (strlen($doc) === 11 && ctype_digit($doc)) {
            if (! self::cpfValido($doc)) {
                $fail('Informe um CPF válido.');
            }

            return;
        }

        if (! CnpjAlfanumerico::ehValido($doc)) {
            $fail('Informe um CPF ou CNPJ válido.');
        }
    }

    public static function cpfValido(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($tamanho = 9; $tamanho < 11; $tamanho++) {
            $soma = 0;
            for ($i = 0; $i < $tamanho; $i++) {
                $soma += (int) $cpf[$i] * (($tamanho + 1) - $i);
            }
            $digito = ((10 * $soma) % 11) % 10;
            if ((int) $cpf[$tamanho] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
