<?php

namespace Tests\Unit;

use App\Rules\CnpjAlfanumerico;
use PHPUnit\Framework\TestCase;

class CnpjAlfanumericoTest extends TestCase
{
    public function test_exemplo_oficial_e_cnpj_numerico_validos(): void
    {
        $this->assertTrue(CnpjAlfanumerico::ehValido(CnpjAlfanumerico::normalizar('12.ABC.345/01DE-35')));
        $this->assertTrue(CnpjAlfanumerico::ehValido(CnpjAlfanumerico::normalizar('11.222.333/0001-81')));
        $this->assertSame('12ABC34501DE35', CnpjAlfanumerico::normalizar('12.ABC.345/01DE-35'));
    }

    public function test_nao_apaga_letras_e_rejeita_dv_errado(): void
    {
        $this->assertSame('12ABC34501DE35', CnpjAlfanumerico::normalizar('12.ABC.345/01DE-35'));
        $this->assertFalse(CnpjAlfanumerico::ehValido('12ABC34501DE00'));
        $this->assertFalse(CnpjAlfanumerico::ehValido(CnpjAlfanumerico::normalizar('11.222.333/0001-00')));
    }
}
