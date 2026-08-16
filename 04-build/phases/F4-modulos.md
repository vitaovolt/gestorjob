# F4 Módulos — `gestor-job` (fechada)

**Fase F4 concluída** (ciclos 1–9). Telas MVP do mapa + notif in-app/e-mail. Calendário/feriados/dashboard margem = **fase 2 do produto** (fora desta F4).

## Ciclo 9 (fechado) — E-mail de prazo

`gestor:avisos-prazo` enfileira `PrazoHojeMail`; flag `notif_email`; idempotência `emails_prazo_enviados`.

Smoke operador: **OK 2026-08-15**. Automático: PHPUnit **89** · Playwright **15** · build OK.

## Ciclos

| # | Escopo | Status |
|---|--------|--------|
| 1–8 | Lista, CRUD, Super Admin, config, anexos, recuperar senha, wizard, notif in-app | **fechado** |
| 9 | E-mail de prazo (`notif_email`) | **fechado** (smoke OK 2026-08-15) |
| fase 2 | Calendário, feriados, dashboard margem | depois (produto) |

## Próximo

**F5 Hardening** — `04-build/phases/F5-hardening.md`
