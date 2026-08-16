<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BootstrapSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabelas_base_existem(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasTable('job_batches'));
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasTable('personal_access_tokens'));
        $this->assertTrue(Schema::hasTable('cache'));
        $this->assertTrue(Schema::hasTable('empresas'));
        $this->assertTrue(Schema::hasTable('clientes'));
        $this->assertTrue(Schema::hasTable('servicos'));
        $this->assertTrue(Schema::hasTable('tarefas'));
        $this->assertTrue(Schema::hasTable('tarefa_responsaveis'));
        $this->assertTrue(Schema::hasTable('tarefa_checklist_itens'));
        $this->assertTrue(Schema::hasTable('apontamentos'));
        $this->assertTrue(Schema::hasTable('tarefa_anexos'));
        $this->assertTrue(Schema::hasTable('notificacoes'));
        $this->assertTrue(Schema::hasColumn('users', 'empresa_id'));
        $this->assertTrue(Schema::hasColumn('users', 'papel'));
        $this->assertTrue(Schema::hasColumn('users', 'custo_hora'));
    }
}
