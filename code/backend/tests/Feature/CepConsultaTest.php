<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CepConsultaTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_cep_normaliza_endereco(): void
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
        ]);
        Sanctum::actingAs($admin);

        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '14870-290',
                'logradouro' => 'Rua São Sebastião',
                'complemento' => '',
                'bairro' => 'Centro',
                'localidade' => 'Jaboticabal',
                'uf' => 'SP',
                'ibge' => '3524303',
            ], 200),
        ]);

        $this->getJson('/api/v1/cep/14870290')
            ->assertOk()
            ->assertJsonPath('data.cidade', 'Jaboticabal')
            ->assertJsonPath('data.uf', 'SP')
            ->assertJsonPath('data.logradouro', 'Rua São Sebastião')
            ->assertJsonPath('data.codigo_ibge', '3524303');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '14870290');
        });
    }

    public function test_cep_inexistente_retorna_404(): void
    {
        $empresa = Empresa::factory()->create();
        Sanctum::actingAs(User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
        ]));

        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true], 200),
        ]);

        $this->getJson('/api/v1/cep/00000000')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_cep_exige_auth(): void
    {
        $this->getJson('/api/v1/cep/14870290')->assertUnauthorized();
    }
}
