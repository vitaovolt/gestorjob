# F0 Bootstrap — `gestor-job`

**Objetivo:** Backend Laravel API + frontend SPA. CORS, Sanctum, OpenAPI, banco PostgreSQL via script.

## Critério de done

- [x] API Laravel 12 + SPA React/Vite/Tailwind
- [x] DB `gestor_job` (+ `gestor_job_testing`)
- [x] `php artisan migrate` OK (users, cache, jobs, sanctum)
- [x] OpenAPI + `GET /api/v1/health` (`gestor-job-api`)
- [x] Suite E2E verde (agente)
- [x] `npm run build` FE OK
- [x] LESSONS + sync KB
- [x] Smoke manual do operador (2026-08-14)

## Packs refletidos na F0

| Pack | O que entrou agora |
|------|-------------------|
| queues | Tabela `jobs` + `QUEUE_CONNECTION=database` (worker/e-mail na F4) |
| files | Disco `anexos` em `storage/app/private/anexos` (upload na F4) |
| multi-tenant | Sem models ainda — F1 |
| reporting | Sem agregações ainda — F1/F4 |

## Suite E2E (automática) — gate

Regra: `educraft-devkit/standards/TESTES-FASE.md`

| # | Cenário (tudo o que esta fase entregou) | Arquivo de teste | OK? |
|---|-----------------------------------------|------------------|-----|
| 1 | `GET /api/v1/health` envelope + service `gestor-job-api` + headers | `tests/Feature/HealthTest.php` | [x] |
| 2 | Migrate: users, jobs, job_batches, failed_jobs, personal_access_tokens, cache | `tests/Feature/BootstrapSchemaTest.php` | [x] |
| 3 | Frontend build | `npm run build` | [x] |

**Comandos (agente rodou):**

```powershell
cd code\backend
php artisan migrate --force
php artisan test
# Resultado: 2 passed (19 assertions)

cd ..\frontend
npm run build
# Resultado: built OK (vite 8.2.1)
```

Resultado: **2 passed / 0 failed** (PHPUnit) + build FE verde.

Fase **bloqueada** se qualquer cenário falhar. Não liberar teste manual.

## O que é o smoke desta fase

Checagem rápida no PC: a **API** responde health e a **tela** do SPA mostra “Bootstrap OK”. Não é login nem Kanban.

## Como testar manualmente (só após E2E verde)

### A) Preparar — dois terminais

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan serve
```

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL API | http://localhost:8000/api/v1/health |
| URL FE | http://localhost:5173 |
| DB | `gestor_job` |
| Usuário seed | — (ainda não há auth) |
| Senha seed | — |

### B) Passos

1. No browser ou PowerShell, abrir a health:
   ```powershell
   Invoke-RestMethod http://localhost:8000/api/v1/health
   ```
   **Esperado:** `success=True`, `data.service=gestor-job-api`, `data.status=ok`
2. Abrir http://localhost:5173
   **Esperado:** card “Bootstrap OK” com o JSON do health (API precisa estar no ar)

### Checklist

- [x] Health JSON 200 com envelope Educraft
- [x] SPA carrega e mostra o health
- [x] Sem tela de login ainda (normal na F0)

### Frases prontas

OK:
```
Use educraft-dev-orchestrator. Smoke F0 gestor-job OK. fechar gate
```

Ajuste:
```
Use educraft-dev-orchestrator.
ajuste gestor-job
<o que viu de errado>
```

## Notas

- Composer no PATH: `C:\composer\composer.bat` (não `C:\ProgramData\ComposerSetup\bin`)
- PHP 8.2 → Laravel **12** (o 13 pede PHP 8.3)
- Sanctum Bearer, **sem** `statefulApi()` (lição nfse CSRF)
- `ensure-pgsql-db.ps1` ainda tem ParserError no Windows PowerShell 5 — DB criado via `psql` (já na fila do KB)
