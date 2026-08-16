<?php

namespace Tests\Feature;

use App\Actions\GerarAvisosPrazoHoje;
use App\Mail\PrazoHojeMail;
use App\Models\Empresa;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailPrazoTest extends TestCase
{
    use RefreshDatabase;

    public function test_comando_enfileira_email_de_prazo_hoje(): void
    {
        Mail::fake();

        $empresa = Empresa::factory()->create();
        $colab = User::factory()->colaborador()->create([
            'empresa_id' => $empresa->id,
            'email' => 'prazo@agencia.local',
        ]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Post com prazo',
            'status' => 'execucao',
            'prazo_em' => now()->setTime(17, 0),
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        $this->artisan('gestor:avisos-prazo')
            ->expectsOutputToContain('E-mails: 1')
            ->assertSuccessful();

        Mail::assertQueued(PrazoHojeMail::class, function (PrazoHojeMail $mail) use ($colab, $tarefa) {
            return $mail->hasTo($colab->email)
                && $mail->tarefa->is($tarefa)
                && $mail->user->is($colab);
        });

        $this->assertDatabaseHas('emails_prazo_enviados', [
            'user_id' => $colab->id,
            'tarefa_id' => $tarefa->id,
            'dia' => now()->toDateString(),
        ]);
    }

    public function test_respeita_flag_notif_email(): void
    {
        Mail::fake();

        $empresa = Empresa::factory()->create([
            'configuracao' => ['notif_email' => false, 'notif_in_app' => true],
        ]);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'execucao',
            'prazo_em' => now()->setTime(12, 0),
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        $r = app(GerarAvisosPrazoHoje::class)->handle();
        $this->assertSame(1, $r['in_app']);
        $this->assertSame(0, $r['emails']);
        Mail::assertNothingQueued();
        $this->assertDatabaseCount('emails_prazo_enviados', 0);
    }

    public function test_email_sem_in_app_quando_flag_in_app_off(): void
    {
        Mail::fake();

        $empresa = Empresa::factory()->create([
            'configuracao' => ['notif_email' => true, 'notif_in_app' => false],
        ]);
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'revisao',
            'prazo_em' => now()->setTime(9, 0),
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        $r = app(GerarAvisosPrazoHoje::class)->handle();
        $this->assertSame(0, $r['in_app']);
        $this->assertSame(1, $r['emails']);
        Mail::assertQueued(PrazoHojeMail::class);
        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_nao_reenvia_email_no_mesmo_dia(): void
    {
        Mail::fake();

        $empresa = Empresa::factory()->create();
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'status' => 'execucao',
            'prazo_em' => now()->endOfDay(),
        ]);
        $tarefa->responsaveis()->sync([$colab->id]);

        app(GerarAvisosPrazoHoje::class)->handle();
        app(GerarAvisosPrazoHoje::class)->handle();

        Mail::assertQueued(PrazoHojeMail::class, 1);
    }
}
