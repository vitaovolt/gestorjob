<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\Servico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Servico>
 */
class ServicoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nome' => 'Post feed',
            'descricao' => 'Post único para feed',
            'preco_venda' => 280,
            'custo_estimado' => 140,
            'tempo_estimado_minutos' => 120,
            'checklist_padrao' => ['Briefing', 'Arte', 'Copy', 'Revisão', 'Agendar'],
            'recorrencia' => null,
        ];
    }
}
