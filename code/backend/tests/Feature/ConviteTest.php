<?php

namespace Tests\Feature;

use App\Mail\ConviteAdminMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_aceitar_convite_define_senha_e_consome_token(): void
    {
        Mail::fake();
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $criado = $this->postJson('/api/v1/empresas', [
            'nome' => 'Agência Convite',
            'plano' => 'starter',
            'limite_usuarios' => 4,
            'admin_nome' => 'Lia Costa',
            'admin_email' => 'lia@convite.test',
        ])->assertCreated();

        $url = $criado->json('data.convite_url');
        $token = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($token, $query);
        $plain = $query['token'] ?? '';
        $this->assertNotEmpty($plain);

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/convites/'.$plain)
            ->assertOk()
            ->assertJsonPath('data.email', 'lia@convite.test')
            ->assertJsonPath('data.empresa', 'Agência Convite');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'lia@convite.test',
            'password' => 'password',
        ])->assertStatus(422);

        $aceite = $this->postJson('/api/v1/convites/'.$plain, [
            'name' => 'Lia Costa',
            'password' => 'senha-lia-1',
            'password_confirmation' => 'senha-lia-1',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'lia@convite.test')
            ->assertJsonPath('data.user.papel', 'admin');

        $this->assertNotEmpty($aceite->json('data.token'));
        $this->assertNull(User::query()->where('email', 'lia@convite.test')->value('convite_token'));

        $this->postJson('/api/v1/convites/'.$plain, [
            'name' => 'Lia Costa',
            'password' => 'outra-senha-1',
            'password_confirmation' => 'outra-senha-1',
        ])->assertNotFound();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'lia@convite.test',
            'password' => 'senha-lia-1',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'lia@convite.test');

        Mail::assertQueued(ConviteAdminMail::class);
    }

    public function test_convite_expirado_nao_ativa(): void
    {
        Mail::fake();
        $super = User::factory()->superAdmin()->create();
        Sanctum::actingAs($super);

        $url = $this->postJson('/api/v1/empresas', [
            'nome' => 'Expirada',
            'plano' => 'starter',
            'limite_usuarios' => 2,
            'admin_nome' => 'Eva',
            'admin_email' => 'eva@expira.test',
        ])->json('data.convite_url');

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $plain = $query['token'] ?? '';

        User::query()->where('email', 'eva@expira.test')->update([
            'convite_expira_em' => now()->subDay(),
        ]);

        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/convites/'.$plain)->assertStatus(422);
        $this->postJson('/api/v1/convites/'.$plain, [
            'name' => 'Eva',
            'password' => 'senha-eva-1',
            'password_confirmation' => 'senha-eva-1',
        ])->assertStatus(422);

        $this->assertNotNull(User::query()->where('email', 'eva@expira.test')->value('convite_token'));
        $this->postJson('/api/v1/auth/login', [
            'email' => 'eva@expira.test',
            'password' => 'senha-eva-1',
        ])->assertStatus(422);
    }

    public function test_convite_invalido_retorna_404(): void
    {
        $this->getJson('/api/v1/convites/token-inventado')->assertNotFound();
        $this->postJson('/api/v1/convites/token-inventado', [
            'name' => 'X',
            'password' => 'senha1234',
            'password_confirmation' => 'senha1234',
        ])->assertNotFound();
    }
}
