# F6 Deploy + handoff — `gestor-job`

**Objetivo:** Deploy API + FE (CI + self-hosted), docs, ficha catalog, smoke health pós-deploy, lições finais.

## Arquivos tocados

- `.github/workflows/ci.yml` · `deploy.yml` — **self-hosted** + `~/.ssh/gestorjob_github` (padrão nfse-empresa)
- `docs/DEPLOY.md` — secrets, Deploy Key no disco, runner, SG sem 22 aberta, Nginx, smoke
- `HealthController` — `checks.database` (503 se falhar)
- `tests/Feature/DeployReadinessTest.php` · `e2e/deploy-health.spec.js`
- `educraft-devkit/catalog/gestorjob.md` · OpenAPI 0.6.0
- `.env.example` (BE/FE) com notas de produção
- Kit: `standards/DEPLOY-GITHUB.md` alinhado ao nfse-empresa

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado (v0.6.0)
- [x] LESSONS.md da fase + sync KB

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Health com `checks.database=ok` | `HealthTest` + `DeployReadinessTest` | [x] |
| 2 | Scheduler `gestor:avisos-prazo` listado | `DeployReadinessTest` | [x] |
| 3 | Workflows + `docs/DEPLOY.md` existem | `DeployReadinessTest` | [x] |
| 4 | Smoke HTTP health + headers | `e2e/deploy-health.spec.js` | [x] |
| 5 | FE build de produção | `npm run build` | [x] |

**Comandos:**

```bash
cd code/backend && php artisan test --filter="Health|DeployReadiness"
cd code/frontend && npx playwright test e2e/deploy-health.spec.js && npm run build
```

Resultado (agente): PHPUnit **101** · DeployReadiness **4** · Playwright deploy-health **1** (+ hardening OK) · `npm run build` OK.

## Como testar manualmente (só após E2E verde)

### Preparar (local = simula smoke pós-deploy)

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan migrate:fresh --seed
php artisan serve
```

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npm run build
npm run preview
# ou npm run dev se preferir
```

| Item | Valor |
|------|--------|
| Health | http://127.0.0.1:8000/api/v1/health |
| Admin | `mariana@agenciaeduc.local` / `password` |
| Doc | [docs/DEPLOY.md](../../docs/DEPLOY.md) |

### Passos

1. `curl http://127.0.0.1:8000/api/v1/health` → `"status":"ok"` e `"database":"ok"`.
2. Na EC2 (primeira vez): gerar `~/.ssh/gestorjob_github`, cadastrar `.pub` em Deploy keys; instalar runner; secret só `DEPLOY_PATH`.
3. Security Group: 22 só no seu IP `/32` — sem `0.0.0.0/0`.
4. Login SPA + Kanban (regressão). Após Actions verde: curl na URL pública.

### Esperado

- Health nunca “ok” sem banco; CI falha se PHPUnit/build quebrarem; deploy só após CI success; **sem** SSH inbound do GitHub.

## Checklist ops (operador / infra)

Ver [DEPLOY-GITHUB.md](../../../educraft-devkit/standards/DEPLOY-GITHUB.md) + [docs/DEPLOY.md](../../docs/DEPLOY.md).
