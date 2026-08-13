# Dúvidas — rodada 1 (fechada no retrofit)

Prioridade: P0 bloqueia E2 | P1 importante | P2 nice

Origem: proposta + canvas + lo-fi (“decisões já fechadas”). Retrofit 2026-08-13.

## Negócio

| # | Dúvida | Pri | Status | Resposta |
|---|--------|-----|--------|----------|
| 1 | Multi-tenant desde o dia 1? | P0 | fechada | Sim. Super Admin cadastra empresa + plano + limites. |
| 2 | Home do sistema? | P0 | fechada | Kanban. Lista e calendário são visões alternativas. |
| 3 | Colaborador vê o quê? | P0 | fechada | Só tarefas alocadas. |
| 4 | Atraso é coluna? | P0 | fechada | Não. Badge/borda no card. |
| 5 | Como o timer se comporta? | P0 | fechada | Inicia ao abrir (Análise); pausa ao sair da tela; fases Análise/Produção/Revisão/Correção. |
| 6 | Quem vê financeiro/margem? | P0 | fechada | Admin e Gerente. Colaborador e Visualizador não. |
| 7 | Recorrência no MVP? | P1 | fechada | Semanal simples (ex.: IG 3×/sem), gera 2–4 semanas à frente. |
| 8 | Portal do cliente / mobile / Figma? | P1 | fechada | Fase 3. Fora do MVP. |
| 9 | Dashboard de margem no MVP? | P1 | fechada | Fase 2 do produto. Modelo de horas/custo já no domínio F1. |

## Técnicas

| # | Dúvida | Pri | Status | Resposta |
|---|--------|-----|--------|----------|
| 1 | Arquitetura | P0 | fechada | Laravel API + SPA React (DNA Educraft). Sem Inertia. |
| 2 | Isolamento tenant | P0 | fechada | Pack `multi-tenant`. Policies + tenant_id em todas as entidades de agência. |
| 3 | PWA / app nativo no MVP? | P0 | fechada | Não. Web desktop-first. Mobile = Fase 3. |
| 4 | Notificações ao vivo? | P1 | fechada | In-app + e-mail no MVP. WebSocket só se polling não bastar (`realtime=avaliar`). |
| 5 | Banco | P0 | fechada | PostgreSQL (script `ensure-pgsql-db.ps1` na F0). |
