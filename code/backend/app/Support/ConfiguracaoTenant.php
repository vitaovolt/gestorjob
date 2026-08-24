<?php

namespace App\Support;

use App\Models\Empresa;

class ConfiguracaoTenant
{
    public const PADRAO = [
        'gerente_cria_usuarios' => true,
        'colaborador_cria_tarefas' => false,
        'gerente_exclui_tarefas' => false,
        'timer_ao_abrir' => false,
        'notif_email' => true,
        'notif_in_app' => true,
        'digest_diario' => false,
        'colaborador_so_alocadas' => true,
        'expediente_dias' => ['seg', 'ter', 'qua', 'qui', 'sex'],
        'expediente_hora_fim' => '18:00',
    ];

    public const DIAS_SEMANA = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];

    public const CHAVES_GERENTE = [
        'notif_email',
        'notif_in_app',
        'digest_diario',
    ];

    /**
     * @return list<string>
     */
    public static function chaves(): array
    {
        return array_keys(self::PADRAO);
    }

    /**
     * @return list<string>
     */
    public static function chavesBool(): array
    {
        return array_values(array_filter(
            self::chaves(),
            fn (string $chave) => is_bool(self::PADRAO[$chave]),
        ));
    }

    /**
     * @param  array<string, mixed>|null  $salvo
     * @return array<string, mixed>
     */
    public static function mesclar(?array $salvo): array
    {
        $merged = [];
        foreach (self::PADRAO as $chave => $padrao) {
            if (! array_key_exists($chave, $salvo ?? [])) {
                $merged[$chave] = $padrao;

                continue;
            }
            $merged[$chave] = self::normalizar($chave, $salvo[$chave], $padrao);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>|null  $salvo
     */
    public static function normalizar(string $chave, mixed $valor, mixed $padrao): mixed
    {
        if (is_bool($padrao)) {
            return (bool) $valor;
        }
        if ($chave === 'expediente_hora_fim') {
            $hora = is_string($valor) ? trim($valor) : '';

            return preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora : $padrao;
        }
        if ($chave === 'expediente_dias') {
            $lista = is_array($valor) ? $valor : [];
            $filtrada = array_values(array_filter(
                array_map('strval', $lista),
                fn (string $dia) => in_array($dia, self::DIAS_SEMANA, true),
            ));

            return $filtrada !== [] ? $filtrada : $padrao;
        }

        return $valor ?? $padrao;
    }

    /**
     * @return array{papeis: list<string>, linhas: list<array<string, mixed>>}
     */
    public static function matriz(Empresa $empresa): array
    {
        $c = $empresa->config();

        return [
            'papeis' => ['admin', 'gerente', 'colaborador', 'visualizador'],
            'linhas' => [
                [
                    'id' => 'ver_so_alocadas',
                    'label' => 'Ver só tarefas alocadas',
                    'celulas' => [
                        'admin' => self::celula('traco'),
                        'gerente' => self::celula('traco'),
                        'colaborador' => self::celula('config', $c['colaborador_so_alocadas'], 'colaborador_so_alocadas'),
                        'visualizador' => self::celula('config', $c['colaborador_so_alocadas'], 'colaborador_so_alocadas'),
                    ],
                ],
                [
                    'id' => 'criar_tarefas',
                    'label' => 'Criar tarefas',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('sim', true),
                        'colaborador' => self::celula('config', $c['colaborador_cria_tarefas'], 'colaborador_cria_tarefas'),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
                [
                    'id' => 'excluir_tarefas',
                    'label' => 'Excluir tarefas',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('config', $c['gerente_exclui_tarefas'], 'gerente_exclui_tarefas'),
                        'colaborador' => self::celula('nao', false),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
                [
                    'id' => 'alocar',
                    'label' => 'Alocar responsáveis',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('sim', true),
                        'colaborador' => self::celula('nao', false),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
                [
                    'id' => 'cadastrar_equipe',
                    'label' => 'Cadastrar equipe',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('config', $c['gerente_cria_usuarios'], 'gerente_cria_usuarios'),
                        'colaborador' => self::celula('nao', false),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
                [
                    'id' => 'relatorios',
                    'label' => 'Relatórios financeiros',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('sim', true),
                        'colaborador' => self::celula('nao', false),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
                [
                    'id' => 'configuracoes',
                    'label' => 'Configurações do tenant',
                    'celulas' => [
                        'admin' => self::celula('sim', true),
                        'gerente' => self::celula('parcial'),
                        'colaborador' => self::celula('nao', false),
                        'visualizador' => self::celula('nao', false),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{tipo: string, valor: bool|null, chave: string|null}
     */
    private static function celula(string $tipo, ?bool $valor = null, ?string $chave = null): array
    {
        return [
            'tipo' => $tipo,
            'valor' => $valor,
            'chave' => $chave,
        ];
    }
}
