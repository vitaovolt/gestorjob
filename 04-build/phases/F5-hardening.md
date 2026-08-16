# F5 Hardening — `gestor-job` (fechada)

**Fase F5 concluída.** Smoke operador: **OK 2026-08-15**. Automático: PHPUnit **97** · Playwright **17** · build OK.

**Objetivo:** Checklist segurança na API — policies, rate limit, CORS prod, headers, submit único, UI sem CTA indevido.

## Arquivos tocados

- `app/Policies/{Cliente,Servico,Tarefa,Empresa,User}Policy.php`
- `TarefaPolicy`: `denyAsNotFound` (não visível) vs `deny` (papel sem mutação)
- Form Requests + controllers com `Gate::authorize` / `$this->authorize`
- `SecurityHeaders`, `config/cors.php`, `AppServiceProvider` (`FRONTEND_URL` obrigatório em production; limiters `api`/`login`/`recuperar`)
- FE: nav cadastros para tenant (leitura); CTA/ações só com `gerir_cadastros` / `cadastrar_equipe`
- Seed: `vista@agenciaeduc.local` (visualizador)
- `tests/Feature/{HardeningTest,PolicyAuthzTest}.php`
- `e2e/hardening.spec.js`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado (v0.5.0)
- [x] LESSONS.md da fase + sync KB
- [x] Submits/confirmações mutáveis com guarda anti-reenvio
- [x] Camadas: `auth:sanctum` + Policy por resource + SecurityHeaders
- [x] Feature: 401 anônimo + 403/404 policy deny

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Headers CSP / nosniff / frame DENY | `HardeningTest` + `e2e/hardening.spec.js` | [x] |
| 2 | CORS prod sem `FRONTEND_URL` → origins vazios | `HardeningTest` | [x] |
| 3 | Login throttle → 429 | `HardeningTest` | [x] |
| 4 | Anônimo 401; visualizador 403 em mutações; colaborador cria cliente 403 | `PolicyAuthzTest` | [x] |
| 5 | Tarefa não alocada → 404 (não 403) | `ConfiguracaoPermissaoTest` | [x] |
| 6 | Visualizador vê clientes sem CTA criar | `e2e/hardening.spec.js` | [x] |

**Comandos:**

```bash
cd code/backend && php artisan test
cd code/frontend && npx playwright test e2e/hardening.spec.js && npm run build
```

Resultado (agente): PHPUnit **97** passed · Playwright **17** · hardening **2** · build OK.

## Como testar manualmente (só após E2E verde)

### Preparar

```bash
cd code/backend
php artisan migrate:fresh --seed
php artisan serve
```

```bash
cd code/frontend
npm run dev
```

| Item | Valor |
|------|--------|
| URL API | http://localhost:8000 |
| URL FE | http://localhost:5173 |
| Admin | `mariana@agenciaeduc.local` / `password` |
| Visualizador | `vista@agenciaeduc.local` / `password` |

### Passos

1. Login como `vista@…` → Clientes na nav; lista Educ visível; sem “+ Cliente” / Editar.
2. DevTools → Network em `/api/v1/health` → `X-Frame-Options: DENY`, CSP `default-src 'none'`.
3. Login como Mariana → mutações normais (cliente/tarefa) OK.
4. (Opcional) Em prod-like: `APP_ENV=production` sem `FRONTEND_URL` → boot deve falhar; com URL só essa origem no CORS.

### Esperado

- Leitura OK para visualizador; mutação bloqueada na API (403) e sem affordance na UI.
- Isolamento/não-visível continua **404**.
