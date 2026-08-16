<?php

namespace Database\Seeders;

use App\Models\Apontamento;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'plataforma@gestorjob.local'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'papel' => 'super_admin',
                'empresa_id' => null,
            ]
        );

        $educ = Empresa::query()->updateOrCreate(
            ['nome' => 'Agência Educ'],
            ['plano' => 'pro', 'limite_usuarios' => 12, 'status' => 'ativo', 'wizard_concluido_em' => now()]
        );

        $norte = Empresa::query()->updateOrCreate(
            ['nome' => 'Studio Norte'],
            ['plano' => 'starter', 'limite_usuarios' => 5, 'status' => 'ativo', 'wizard_concluido_em' => now()]
        );

        $mariana = User::query()->updateOrCreate(
            ['email' => 'mariana@agenciaeduc.local'],
            [
                'name' => 'Mariana Costa',
                'password' => Hash::make('password'),
                'empresa_id' => $educ->id,
                'papel' => 'admin',
                'custo_hora' => 90,
                'carga_semanal_horas' => 40,
                'departamento' => 'Direção',
            ]
        );

        $ana = User::query()->updateOrCreate(
            ['email' => 'ana@agenciaeduc.local'],
            [
                'name' => 'Ana Silva',
                'password' => Hash::make('password'),
                'empresa_id' => $educ->id,
                'papel' => 'colaborador',
                'custo_hora' => 70,
                'carga_semanal_horas' => 40,
                'departamento' => 'Criação',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'vista@agenciaeduc.local'],
            [
                'name' => 'Vista Oliveira',
                'password' => Hash::make('password'),
                'empresa_id' => $educ->id,
                'papel' => 'visualizador',
                'custo_hora' => null,
                'carga_semanal_horas' => 20,
                'departamento' => 'Cliente',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'ops@studionorte.local'],
            [
                'name' => 'Ops Norte',
                'password' => Hash::make('password'),
                'empresa_id' => $norte->id,
                'papel' => 'admin',
                'custo_hora' => 65,
                'carga_semanal_horas' => 40,
                'departamento' => 'Operação',
            ]
        );

        $clienteEduc = Cliente::query()->updateOrCreate(
            ['empresa_id' => $educ->id, 'nome_fantasia' => 'Educ'],
            [
                'razao_social' => 'Educ Tecnologia LTDA',
                'cnpj' => '11222333000181',
                'segmento' => 'Educação',
                'status' => 'ativo',
                'contato_nome' => 'Paulo',
                'email' => 'paulo@educ.test',
                'fee_mensal' => 8000,
                'tipo_faturamento' => 'mensal',
                'dia_vencimento' => 10,
            ]
        );

        Cliente::query()->updateOrCreate(
            ['empresa_id' => $educ->id, 'nome_fantasia' => 'Cliente C'],
            [
                'segmento' => 'Indústria',
                'status' => 'ativo',
                'contato_nome' => 'Tess',
                'fee_mensal' => 4000,
                'tipo_faturamento' => 'mensal',
                'dia_vencimento' => 15,
            ]
        );

        Cliente::query()->updateOrCreate(
            ['empresa_id' => $norte->id, 'nome_fantasia' => 'Cliente Norte'],
            [
                'segmento' => 'Varejo',
                'status' => 'ativo',
                'fee_mensal' => 2500,
                'tipo_faturamento' => 'mensal',
            ]
        );

        $reels = Servico::query()->updateOrCreate(
            ['empresa_id' => $educ->id, 'nome' => 'Reels Instagram'],
            [
                'preco_venda' => 450,
                'tempo_estimado_minutos' => 180,
                'checklist_padrao' => ['Briefing', 'Arte', 'Copy', 'Revisão', 'Agendar'],
            ]
        );

        Servico::query()->updateOrCreate(
            ['empresa_id' => $educ->id, 'nome' => 'Post feed'],
            [
                'preco_venda' => 280,
                'tempo_estimado_minutos' => 120,
                'checklist_padrao' => ['Briefing', 'Arte', 'Copy', 'Agendar'],
                'recorrencia' => ['frequencia' => 'semanal', 'dias' => ['ter', 'qui', 'sab'], 'prazo_d_menos' => 1],
            ]
        );

        $tarefa = Tarefa::query()->updateOrCreate(
            ['empresa_id' => $educ->id, 'titulo' => 'Reels — Cliente Educ'],
            [
                'cliente_id' => $clienteEduc->id,
                'servico_id' => $reels->id,
                'status' => 'execucao',
                'prioridade' => 'urgente',
                'prazo_em' => now()->endOfDay(),
                'briefing' => 'Formato 9:16 · tom leve · CTA link bio.',
                'fase_timer' => 'producao',
            ]
        );
        $tarefa->responsaveis()->syncWithoutDetaching([$ana->id, $mariana->id]);

        if ($tarefa->checklistItens()->count() === 0) {
            foreach (['Briefing', 'Arte', 'Copy', 'Revisão', 'Agendar'] as $ordem => $titulo) {
                $tarefa->checklistItens()->create([
                    'titulo' => $titulo,
                    'feito' => $ordem < 2,
                    'ordem' => $ordem,
                ]);
            }
        }

        if ($tarefa->apontamentos()->count() === 0) {
            Apontamento::query()->create([
                'empresa_id' => $educ->id,
                'tarefa_id' => $tarefa->id,
                'user_id' => $ana->id,
                'fase' => 'producao',
                'iniciado_em' => now()->startOfMonth()->addHours(10),
                'encerrado_em' => now()->startOfMonth()->addHours(12)->addMinutes(40),
                'segundos' => (int) (2.67 * 3600),
                'custo_hora_snapshot' => 70,
            ]);
        }
    }
}
