<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaAnexo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnexoTarefaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake(TarefaAnexo::DISCO);
    }

    public function test_anexo_exige_auth(): void
    {
        $tarefa = $this->cenario()['tarefa'];

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png'),
        ], ['Accept' => 'application/json'])->assertUnauthorized();
    }

    public function test_admin_anexa_arquivo_no_disco_e_no_banco(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->cenario();
        Sanctum::actingAs($admin);

        $arquivo = UploadedFile::fake()->image('briefing.png', 12, 12);

        $resposta = $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => $arquivo,
        ], ['Accept' => 'application/json']);

        $resposta->assertCreated()
            ->assertJsonPath('data.anexos.0.nome', 'briefing.png')
            ->assertJsonPath('data.anexos.0.autor', $admin->name);

        $this->assertArrayNotHasKey('path', $resposta->json('data.anexos.0'));

        $anexo = TarefaAnexo::query()->first();
        $this->assertNotNull($anexo);
        $this->assertDatabaseHas('tarefa_anexos', [
            'id' => $anexo->id,
            'tarefa_id' => $tarefa->id,
            'empresa_id' => $admin->empresa_id,
            'user_id' => $admin->id,
            'nome_original' => 'briefing.png',
        ]);
        Storage::disk(TarefaAnexo::DISCO)->assertExists($anexo->path);
        $this->assertGreaterThan(0, $anexo->tamanho_bytes);
    }

    public function test_download_devolve_o_arquivo(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->cenario();
        Sanctum::actingAs($admin);

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $anexo = TarefaAnexo::query()->first();

        $this->get("/api/v1/tarefas/{$tarefa->id}/anexos/{$anexo->id}/download")
            ->assertOk()
            ->assertDownload('arte.png');
    }

    public function test_exclui_anexo_do_banco_e_do_disco(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->cenario();
        Sanctum::actingAs($admin);

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('rascunho.png'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $anexo = TarefaAnexo::query()->first();
        $path = $anexo->path;

        $this->deleteJson("/api/v1/tarefas/{$tarefa->id}/anexos/{$anexo->id}")
            ->assertOk()
            ->assertJsonPath('data.anexos', []);

        $this->assertDatabaseMissing('tarefa_anexos', ['id' => $anexo->id]);
        Storage::disk(TarefaAnexo::DISCO)->assertMissing($path);
    }

    public function test_rejeita_tipo_nao_permitido(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->cenario();
        Sanctum::actingAs($admin);

        foreach ([
            ['script.exe', 'application/x-msdownload'],
            ['notas.txt', 'text/plain'],
            ['foto.bmp', 'image/bmp'],
            ['dados.bin', 'application/octet-stream'],
        ] as [$nome, $mime]) {
            $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
                'arquivo' => UploadedFile::fake()->create($nome, 20, $mime),
            ], ['Accept' => 'application/json'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['arquivo']);
        }

        $this->assertDatabaseCount('tarefa_anexos', 0);
    }

    public function test_colaborador_nao_alocado_nao_anexa(): void
    {
        ['empresa' => $empresa, 'tarefa' => $tarefa] = $this->cenario();
        $colab = User::factory()->colaborador()->create(['empresa_id' => $empresa->id]);
        Sanctum::actingAs($colab);

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertDatabaseCount('tarefa_anexos', 0);
    }

    public function test_visualizador_nao_anexa(): void
    {
        ['empresa' => $empresa, 'tarefa' => $tarefa] = $this->cenario();
        $viewer = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'visualizador',
        ]);
        $tarefa->responsaveis()->sync([$viewer->id]);
        Sanctum::actingAs($viewer);

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png'),
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->assertDatabaseCount('tarefa_anexos', 0);
    }

    public function test_outro_tenant_nao_anexa_nem_baixa(): void
    {
        ['admin' => $admin, 'tarefa' => $tarefa] = $this->cenario();
        Sanctum::actingAs($admin);
        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('arte.png'),
        ], ['Accept' => 'application/json'])->assertCreated();
        $anexo = TarefaAnexo::query()->first();

        $outro = User::factory()->create(['papel' => 'admin']);
        Sanctum::actingAs($outro);

        $this->post("/api/v1/tarefas/{$tarefa->id}/anexos", [
            'arquivo' => UploadedFile::fake()->image('intruso.png'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->get("/api/v1/tarefas/{$tarefa->id}/anexos/{$anexo->id}/download")->assertNotFound();
        $this->deleteJson("/api/v1/tarefas/{$tarefa->id}/anexos/{$anexo->id}")->assertNotFound();

        $this->assertDatabaseHas('tarefa_anexos', ['id' => $anexo->id]);
    }

    /**
     * @return array{admin: User, empresa: Empresa, tarefa: Tarefa}
     */
    private function cenario(): array
    {
        $empresa = Empresa::factory()->create();
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel' => 'admin',
        ]);
        $cliente = Cliente::factory()->create(['empresa_id' => $empresa->id]);
        $servico = Servico::factory()->create(['empresa_id' => $empresa->id]);
        $tarefa = Tarefa::factory()->create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'titulo' => 'Reels — Educ',
        ]);

        return compact('admin', 'empresa', 'tarefa');
    }
}
