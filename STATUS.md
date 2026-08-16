# STATUS — `gestor-job`

| Campo | Valor |
|-------|--------|
| slug | `gestor-job` |
| etapa_atual | `e4` |
| fase_e4 | `F6` |
| status | `aguardando_aprovacao` |
| proximo_passo | Smoke F6 com orchestrator. Cole o CARTÃO. |
| skill | `educraft-dev-orchestrator` |
| remoto | `git@github.com:vitaovolt/gestorjob.git` |

## Capacidades ativas

Ver [CAPABILITIES.md](CAPABILITIES.md).

| Pack | Status |
|------|--------|
| multi-tenant | sim (F1: scope + unique por tenant) |
| queues | sim |
| files | sim |
| reporting | sim (F1: margem por competência) |
| realtime | avaliar |
| integrations | nao (Fase 3) |
| mobile | nao (Fase 3) |
| fiscal | nao |
| offline-sync | nao |

## Gates

- [x] E1 Descoberta (retrofit 2026-08-13 — origem: proposta + canvas)
- [x] E2 Fluxos + lo-fi (`02-lofi/prototipo-lofi.html`)
- [x] E3 Identidade + hi-fi (`03-hifi/`)
- [x] E4 F0 Bootstrap (E2E verde · smoke OK)
- [x] E4 F1 Domínio (E2E verde · smoke OK)
- [x] E4 F2 Auth (E2E verde · smoke OK)
- [x] E4 F3 Vertical slice (E2E verde · smoke OK 2026-08-14)
- [x] E4 F4 ciclo 1 Lista + clientes (E2E verde · smoke OK 2026-08-14)
- [x] E4 F4 ciclo 2 Serviços + colaboradores (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 3 Super Admin + convite (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 4 Config + permissões (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 5 Anexos (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 6 Recuperar senha (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 7 Wizard onboarding (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 8 Notificações in-app (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 ciclo 9 E-mail de prazo (E2E verde · smoke OK 2026-08-15)
- [x] E4 F4 Módulos (MVP telas + notif in-app/e-mail)
- [x] E4 F5 Hardening (E2E verde · smoke OK 2026-08-15)
- [ ] E4 F6 Deploy

## Links úteis

- Lo-fi: [02-lofi/prototipo-lofi.html](02-lofi/prototipo-lofi.html)
- Identidade: [03-hifi/identidade-visual.html](03-hifi/identidade-visual.html)
- Hi-fi: [03-hifi/prototipo-hifi.html](03-hifi/prototipo-hifi.html)
- F0: [04-build/phases/F0-bootstrap.md](04-build/phases/F0-bootstrap.md)
- F1: [04-build/phases/F1-dominio.md](04-build/phases/F1-dominio.md)
- F2: [04-build/phases/F2-auth.md](04-build/phases/F2-auth.md)
- F3: [04-build/phases/F3-vertical-slice.md](04-build/phases/F3-vertical-slice.md)
- F4: [04-build/phases/F4-modulos.md](04-build/phases/F4-modulos.md)
- F5: [04-build/phases/F5-hardening.md](04-build/phases/F5-hardening.md)
- F6: [04-build/phases/F6-deploy.md](04-build/phases/F6-deploy.md)
- Como usar o framework: `educraft-devkit/COMECE-AQUI.md`

## Histórico rápido

| Data | Evento |
|------|--------|
| 2026-08 | Lo-fi, identidade e hi-fi criados (referência E2/E3 do kit) |
| 2026-08-13 | Adequação ao skeleton Educraft Devkit · repo GitHub `vitaovolt/gestorjob` |
| 2026-08-14 | F0–F3 + F4 ciclo 1 (ver histórico detalhado no git / fases) |
| 2026-08-15 | F4 ciclos 2–8 fechados (smoke OK) |
| 2026-08-15 | F4 ciclo 9: e-mail de prazo · PHPUnit 89 · Playwright 15 · build OK |
| 2026-08-15 | Gate F4 ciclo 9 + F4 Módulos fechados (smoke OK) → F5 Hardening |
| 2026-08-15 | F5 Hardening · PHPUnit 97 · Playwright 17 · smoke OK → F6 Deploy |
| 2026-08-16 | F6 Deploy implementada · PHPUnit 101 · deploy-health · aguardando smoke |
