# F1 Domínio + dados

**Objetivo:** Migrations, models PT, services, seed. Sem UI completa além do necessário para API.

## Arquivos

- `code/backend/database/migrations/`
- `code/backend/app/Models/`
- `code/backend/app/Services/`
- `code/backend/database/seeders/`

CRUD **sem regra**: Form Request + Model + Resource — sem DTO/VO/Repository.  
Listagens com relação: `with()`. Migrations: índice nas colunas filtradas/ordenadas (não só PK).

## Critério de done

- [ ] Escopo da fase implementado
- [ ] Suite E2E da fase **verde** (agente executou)
- [ ] Roteiro manual preenchido (seeds, URLs, passos)
- [ ] OpenAPI atualizado (se API)
- [ ] LESSONS.md da fase + sync KB
- [ ] Índices nas colunas de filtro/ordem reais
- [ ] CRUD sem regra: sem camada extra (DTO/VO/Repository)

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
