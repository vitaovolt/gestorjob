<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_login_retorna_token_e_grava_personal_access_token(): void
    {
        $user = User::factory()->create([
            'email' => 'mariana@agenciaeduc.local',
            'password' => 'password',
            'papel' => 'admin',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'Mariana@AgenciaEduc.local',
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'mariana@agenciaeduc.local')
            ->assertJsonPath('data.user.papel', 'admin')
            ->assertJsonPath('data.user.empresa.nome', $user->empresa->nome);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'test',
        ]);
    }

    public function test_login_invalido_nao_cria_token(): void
    {
        User::factory()->create([
            'email' => 'mariana@agenciaeduc.local',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mariana@agenciaeduc.local',
            'password' => 'errada',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_me_e_rotas_privadas_exigem_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh')->assertUnauthorized();
        $this->postJson('/api/v1/auth/senha')->assertUnauthorized();
        $this->getJson('/api/v1/clientes')->assertUnauthorized();
    }

    public function test_me_logout_invalida_o_token(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@agenciaeduc.local',
            'password' => 'password',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_refresh_rotaciona_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $antigo = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $novo = $this->withToken($antigo)
            ->postJson('/api/v1/auth/refresh')
            ->assertOk()
            ->json('data.token');

        $this->assertNotSame($antigo, $novo);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->app['auth']->forgetGuards();

        $this->withToken($antigo)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($novo)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_token_invalido_retorna_401(): void
    {
        $this->withToken('token-inventado')
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_seed_mariana_consegue_logar(): void
    {
        $this->seed();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mariana@agenciaeduc.local',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('data.user.name', 'Mariana Costa')
            ->assertJsonPath('data.user.empresa.nome', 'Agência Educ');
    }

    public function test_usuario_altera_a_propria_senha(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/auth/senha', [
            'senha_atual' => 'errada',
            'password' => 'nova-senha-1',
            'password_confirmation' => 'nova-senha-1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['senha_atual']);

        $this->postJson('/api/v1/auth/senha', [
            'senha_atual' => 'password',
            'password' => 'nova-senha-1',
            'password_confirmation' => 'nova-senha-1',
        ])->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('nova-senha-1', $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
    }
}
