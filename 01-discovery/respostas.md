# Respostas consolidadas — `gestor-job`

Fonte: proposta Gestor Job + canvas de alinhamento + protótipos lo-fi/hi-fi (retrofit 2026-08-13).

## Produto

- Plataforma SaaS para **agências de marketing**.
- Valor: encadeamento **Tarefa → Timesheet (fases) → Custo real → Margem vs. fee**.
- Fórmula: `Fee − (Σ horas × custo/hora) = margem do cliente no mês`.
- Mesmo serviço, custos diferentes por cliente — só visível com fase + custo/hora do colaborador.

## Decisões fechadas

- Multi-tenant desde o dia 1
- Kanban como home
- Timer ao abrir / pausa ao sair
- Colaborador só vê alocadas
- Exclusão de tarefa só com permissão
- Atraso não é coluna
- Fases do timer: Análise → Produção → Revisão → Correção
- Lembrete de prazo é separado do timer

## MVP vs. depois

- **MVP (Fase 1):** tenant, cadastros, Kanban + drawer, timer, recorrência simples, roles, notif in-app + e-mail
- **Fase 2:** dashboard de margem, relatórios, calendário/feriados, templates avançados
- **Fase 3:** portal do cliente, Drive/Figma/Meta, white-label, mobile
