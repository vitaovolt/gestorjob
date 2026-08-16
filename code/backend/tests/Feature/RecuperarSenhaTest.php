<?php

namespace Tests\Feature;

use App\Mail\RecuperarSenhaMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecuperarSenhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitar_com_email_existente_grava_token_e_enfileira_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana@agenciaeduc.local', 'password' => 'password']);

        $resposta = $this->postJson('/api/v1/auth/recuperar-senha', [
            'email' => 'ana@agenciaeduc.local',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Enviamos um link para redefinir a senha.')
            ->assertJsonPath('data.reset_url', fn ($url) => is_string($url) && str_contains($url, '/redefinir-senha?token='));

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'ana@agenciaeduc.local',
        ]);
        $this->assertNotEquals(
            parse_url($resposta->json('data.reset_url'), PHP_URL_QUERY),
            DB::table('password_reset_tokens')->where('email', $user->email)->value('token'),
        );

        Mail::assertQueued(RecuperarSenhaMail::class, function (RecuperarSenhaMail $mail) use ($user) {
            return $mail->hasTo($user->email) && str_contains($mail->url, 'token=');
        });
    }

    public function test_solicitar_com_email_inexistente_retorna_422(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/auth/recuperar-senha', [
            'email' => 'ninguém@exemplo.local',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Este e-mail não está cadastrado.');

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Mail::assertNothingQueued();
    }

    public function test_redefinir_senha_com_token_valido(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => 'password']);
        $tokenAntigo = $user->createToken('spa')->plainTextToken;

        $resetUrl = $this->postJson('/api/v1/auth/recuperar-senha', [
            'email' => $user->email,
        ])->json('data.reset_url');

        parse_str(parse_url($resetUrl, PHP_URL_QUERY), $query);
        $token = $query['token'] ?? '';

        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => $token,
            'password' => 'nova-senha-9',
            'password_confirmation' => 'nova-senha-9',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Senha redefinida. Entre com a nova senha.');

        $this->assertTrue(Hash::check('nova-senha-9', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($tokenAntigo)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'nova-senha-9',
        ])->assertOk();
    }

    public function test_redefinir_com_token_invalido_falha(): void
    {
        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => 'token-falso',
            'password' => 'nova-senha-9',
            'password_confirmation' => 'nova-senha-9',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_redefinir_com_token_expirado_falha(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = 'token-expirado-xyz';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => hash('sha256', $token),
            'created_at' => now()->subHours(2),
        ]);

        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => $token,
            'password' => 'nova-senha-9',
            'password_confirmation' => 'nova-senha-9',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_token_e_de_uso_unico(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => 'password']);

        $resetUrl = $this->postJson('/api/v1/auth/recuperar-senha', [
            'email' => $user->email,
        ])->json('data.reset_url');
        parse_str(parse_url($resetUrl, PHP_URL_QUERY), $query);
        $token = $query['token'];

        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => $token,
            'password' => 'nova-senha-9',
            'password_confirmation' => 'nova-senha-9',
        ])->assertOk();

        $this->postJson('/api/v1/auth/redefinir-senha', [
            'token' => $token,
            'password' => 'outra-senha-1',
            'password_confirmation' => 'outra-senha-1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
