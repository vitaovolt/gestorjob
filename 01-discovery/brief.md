# Brief — `gestor-job`

## Problema / gargalo

Agências de marketing perdem margem sem enxergar o custo real do job. Tarefas, horas e fee ficam em planilhas soltas. O mesmo serviço (ex.: folder mensal) custa 8h num cliente e 62h em outro — sem fases de timesheet isso não aparece.

## Objetivo (30–60–90 dias)

| Horizonte | Meta |
|-----------|------|
| 30 dias | Tenant opera Kanban + timer por fase + cadastros (cliente, serviço, colaborador) |
| 60 dias | Recorrência simples, permissões por role, e-mail/in-app de prazo |
| 90 dias | Dashboard de margem (fee − Σ horas × custo/hora) para Admin/Gerente |

## Perfis de usuário

| Camada | Perfil | Foco |
|--------|--------|------|
| Plataforma | Super Admin | Empresas, planos, limites |
| Tenant | Admin | Equipe, serviços, config, margem |
| Operação | Gerente | Kanban da equipe, prazos, relatórios |
| Operação | Colaborador | Só tarefas alocadas; executar + timer |
| Operação | Visualizador | Leitura das tarefas alocadas |

## Escopo MVP

- Multi-tenant básico (agências isoladas)
- Clientes / Serviços / Colaboradores (custo/hora)
- Kanban hub (home) + detalhe lateral
- Cronômetro com fases: Análise → Produção → Revisão → Correção; pausa ao sair da tela
- Recorrência semanal simples (gera cards à frente)
- Permissões por role (Admin / Gerente / Colaborador / Visualizador)
- Notificações in-app + e-mail
- Atraso = badge/borda, **não** coluna

## Fora de escopo

- Portal do cliente, white-label, app mobile (Fase 3)
- Integrações Drive / Figma / Meta (Fase 3) — no MVP, Drive é URL no cadastro
- Calendário institucional avançado + templates ricos (Fase 2, junto com dashboard)

## Integrações / restrições

- E-mail transacional (convite, prazo) via fila
- Upload de anexos na tarefa
- Sem fiscal, sem offline

## Particularidades (capacidades)

Preencher também `../CAPABILITIES.md`.

| Necessidade | Pack sugerido | Notas |
|-------------|---------------|-------|
| N agências isoladas | multi-tenant | Desde o dia 1 |
| Convite e prazos por e-mail | queues | |
| Anexos na tarefa | files | |
| Margem vs. fee | reporting | UI Fase 2; dados desde F1 |
| Notificação in-app | realtime | avaliar (polling no MVP) |

## Critérios de sucesso

- Admin liga uma agência nova (wizard) e chega num Kanban vazio utilizável
- Colaborador só vê o que está alocado; timer inicia ao abrir a tarefa
- Admin/Gerente vê custo acumulado no drawer e, na Fase 2, margem por cliente
- Fórmula visível: **Fee − (Σ horas × custo/hora) = margem real do mês**
