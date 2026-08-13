# F5 Hardening

**Objetivo:** Checklist segurança AjudaJá na API, policies, rate limit, CORS prod.

## Arquivos

- middleware, policies, config CORS/prod
- testes de authz / negação de acesso
- submit único em mutações críticas (FE + BE) — `educraft-devkit/standards/SUBMIT-UNICO.md`

## Critério de done

- [ ] Escopo da fase implementado
- [ ] Suite E2E da fase **verde** (agente executou)
- [ ] Roteiro manual preenchido (seeds, URLs, passos)
- [ ] OpenAPI atualizado (se API)
- [ ] LESSONS.md da fase + sync KB
- [ ] Submits/confirmações mutáveis com guarda anti-reenvio (flag FE + UI disabled; BE idempotente onde o risco de duplicata for alto)
- [ ] Camadas: `auth:sanctum` + Policy por resource + middleware transversal se pack pedir
- [ ] Feature: 401 anônimo + 403/404 policy deny (não só `assertOk`)

## Suite E2E (automática) — gate

Regra: `educraft-devkit/standards/TESTES-FASE.md`

| # | Cenário (tudo o que esta fase entregou) | Arquivo de teste | OK? |
|---|-----------------------------------------|------------------|-----|
| 1 | | | [ ] |

**Comandos (agente roda e cola o resultado):**

```bash
cd code/backend && php artisan test --filter=<SuiteDaFase>
# a partir de F3 / quando houver UI:
cd code/frontend && npx playwright test <spec-da-fase>
```

Fase **bloqueada** se qualquer cenário falhar. Não liberar teste manual.

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
| Usuário seed | |
| Senha seed | |

### Passos

1. …

### Esperado

- …
