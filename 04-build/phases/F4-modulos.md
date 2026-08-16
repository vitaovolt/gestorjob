# F4 Módulos — `gestor-job`

**Fase F4 concluída** (ciclos 1–9). Telas MVP do mapa + notif in-app/e-mail.  
**Pós-F6 (2026-08-16):** lacunas MVP — recorrência gera cards, custo no drawer, comentários/timeline.

## Ciclo 9 (fechado) — E-mail de prazo

`gestor:avisos-prazo` enfileira `PrazoHojeMail`; flag `notif_email`; idempotência `emails_prazo_enviados`.

Smoke operador: **OK 2026-08-15**. Automático: PHPUnit **89** · Playwright **15** · build OK.

## Pós-F6 — lacunas MVP

| Item | Entrega |
|------|---------|
| Recorrência | `recorrencias` + `gestor:gerar-recorrencias` (06:30) · painel no serviço |
| Custo no drawer | `custo_acumulado` / `horas_acumuladas` (Admin/Gerente) |
| Comentários | timeline usuário + sistema (mudança de status) |

## Ciclos

| # | Escopo | Status |
|---|--------|--------|
| 1–8 | Lista, CRUD, Super Admin, config, anexos, recuperar senha, wizard, notif in-app | **fechado** |
| 9 | E-mail de prazo (`notif_email`) | **fechado** (smoke OK 2026-08-15) |
| pós-F6 | Recorrência + custo drawer + comentários | **implementado** (aguardando smoke) |
| fase 2 | Calendário, feriados, dashboard margem | depois (produto) |

## Próximo

Smoke pós-F6 lacunas · depois Fase 2 (calendário / margem UI).
