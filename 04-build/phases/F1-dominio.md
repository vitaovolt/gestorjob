# F1 Domínio + dados — `gestor-job`

**Objetivo:** Tenant, clientes, serviços, colaboradores, tarefas e fórmula de margem. API autenticada (Bearer). Sem UI nova (telas na F3).

## Arquivos

- `code/backend/database/migrations/2026_08_14_200000_create_dominio_gestor_job_tables.php`
- `code/backend/app/Models/` (`Empresa`, `Cliente`, `Servico`, `Tarefa`, `Apontamento`, `User` + trait `PertenceAEmpresa`)
- `code/backend/app/Actions/` (`CriarColaborador`, `CriarTarefa`, `CalcularMargemCliente`)
- `code/backend/app/Http/Controllers/Api/` + Form Requests
- `code/backend/database/seeders/DatabaseSeeder.php`
- `code/backend/app/Console/Commands/IssueManualTokenCommand.php` (`gestorjob:token`)
- `code/backend/docs/openapi.yaml`
- `code/frontend/src/api/dominio.js` (client HTTP; sem telas)

CRUD **sem regra** (Cliente, Serviço): Form Request + Eloquent — sem DTO/VO/Repository.  
Regra de negócio: Actions. Listagens com `with()`. Índices nas colunas de filtro/ordem.

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Índices nas colunas de filtro/ordem reais
- [x] CRUD sem regra: sem camada extra (DTO/VO/Repository)
- [x] Smoke manual do operador (2026-08-14)

## Packs refletidos na F1

| Pack | O que entrou agora |
|------|-------------------|
| multi-tenant | `empresas` + global scope `PertenceAEmpresa`; Super Admin vê tudo |
| reporting | `CalcularMargemCliente`: fee − Σ(horas × custo/hora) no mês |
| queues / files | Sem mudança (já no F0; worker/upload na F4) |

## Suite E2E (automática) — gate

Regra: `educraft-devkit/standards/TESTES-FASE.md`

| # | Cenário (tudo o que esta fase entregou) | Arquivo de teste | OK? |
|---|-----------------------------------------|------------------|-----|
| 1 | `GET /api/v1/health` envelope + headers | `tests/Feature/HealthTest.php` | [x] |
| 2 | Schema F0 + F1 (empresas, clientes, tarefas, apontamentos, users.empresa_id) | `tests/Feature/BootstrapSchemaTest.php` | [x] |
| 3 | 401 sem token em clientes/serviços/tarefas/colaboradores/empresa/margem | `tests/Feature/DominioApiTest.php` | [x] |
| 4 | CRUD cliente/serviço/tarefa persiste (`assertDatabaseHas`); checklist herdado; update status | `tests/Feature/DominioApiTest.php` | [x] |
| 5 | GET empresa + POST colaborador dentro do plano | `tests/Feature/DominioApiTest.php` | [x] |
| 6 | GET margem (admin vê fee/custo/margem; colaborador 403) | `tests/Feature/DominioApiTest.php` | [x] |
| 7 | CNPJ duplicado no mesmo tenant → 422 | `tests/Feature/DominioApiTest.php` | [x] |
| 8 | Limite de seats (`CriarColaborador`) → 422 + `assertDatabaseMissing` | `tests/Feature/DominioApiTest.php` | [x] |
| 9 | Seed Agência Educ + Studio Norte | `tests/Feature/DominioApiTest.php` | [x] |
| 10 | Visualizador 403 em margem | `tests/Feature/DominioApiTest.php` | [x] |
| 11 | Isolamento: tenant A não vê cliente B (lista + show 404); CNPJ igual em tenants distintos OK | `tests/Feature/IsolamentoTenantTest.php` | [x] |
| 12 | `GET /empresas` 403 admin / 200 super_admin; colaboradores não vazam | `tests/Feature/IsolamentoTenantTest.php` | [x] |
| 13 | Fórmula margem 10h × R$70 com fee R$4000 → margem R$3300 | `tests/Unit/CalcularMargemClienteTest.php` | [x] |

**Comandos (agente rodou):**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan migrate --force
php artisan test
# Resultado: 16 passed (109 assertions)
```

Resultado: **16 passed / 0 failed**.

Fase **bloqueada** se qualquer cenário falhar. Não liberar teste manual.

## Dois tipos de teste (não misture)

| Tipo | Quem roda | O que é | Você precisa fazer? |
|------|-----------|---------|---------------------|
| Automático (E2E) | Agente, no PHPUnit | 16 testes no banco `gestor_job_testing` | Já rodou. Verde. |
| Smoke manual | Você, no PowerShell | 6–8 chamadas na API local, olho nu | **OK em 2026-08-14** |

Não abra o frontend. Não faça login. Não tem tela de clientes ainda. O smoke é: **subir a API → pegar um token → chamar URLs → conferir os nomes no JSON**.

O PowerShell mostra JSON como tabela (`success / data / message`). Para ver o conteúdo de verdade, sempre use `| ConvertTo-Json -Depth 6` no final.

---

## Como testar manualmente

Use **dois** terminais PowerShell. O terminal 1 fica ocupado com o servidor (`php artisan serve`) — não rode outros comandos nele. Tudo o que for token e `Invoke-RestMethod` vai no **terminal 2**.

### Passo 0 — o que o seed coloca no banco

`migrate:fresh --seed` apaga o banco `gestor_job` e cria este mundo de mentira:

| Quem | E-mail | Papel | Enxerga |
|------|--------|-------|---------|
| Super Admin | `plataforma@gestorjob.local` | plataforma | todas as agências |
| Mariana | `mariana@agenciaeduc.local` | admin da **Agência Educ** | Educ + Cliente C + tarefa Reels |
| Ana | `ana@agenciaeduc.local` | colaborador Educ | mesmo tenant, **sem** margem |
| Ops | `ops@studionorte.local` | admin do **Studio Norte** | só Cliente Norte |

Senha de todos: `password` (não usa nesta fase — não há tela de login).

### Passo 1 — Terminal 1: resetar dados e ligar a API

Cole **um comando de cada vez**. Espere o anterior terminar.

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

**OK neste passo:** a última linha fica parecida com `Server running on [http://127.0.0.1:8000]`. Deixe esse terminal aberto. Se já tinha um `serve` antigo, feche (Ctrl+C) antes de ligar de novo.

Se aparecer “usuário não encontrado” no passo 2, você pulou o `migrate:fresh --seed`.

### Passo 2 — Terminal 2: gerar o token da Mariana

Abra **outro** PowerShell (não o do `serve`).

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan gestorjob:token
```

O comando imprime um bloco. **Copie as duas linhas** `$token = ...` e `$h = ...` e cole no mesmo terminal 2. Não monte o header na mão.

Conferência obrigatória:

```powershell
$h.Authorization
```

**OK:** o texto **começa** com `Bearer 2|` (palavra Bearer, espaço, depois o token).

**Erro que já aconteceu:** `Authorization` só com `2|....` → todos os endpoints autenticados voltam **401**. Health continua 200 porque não pede token.

### Passo 3 — Health (sem token, de propósito)

```powershell
Invoke-RestMethod http://127.0.0.1:8000/api/v1/health | ConvertTo-Json
```

**Passou se** `success` = true e `data.service` = `gestor-job-api`.

**Falhou se** “Não é possível conectar” / conexão recusada → o terminal 1 não está com `serve` no ar.

### Passo 4 — Provar que sem token dá 401

```powershell
try { Invoke-RestMethod http://127.0.0.1:8000/api/v1/clientes } catch { $_.Exception.Message }
```

**Passou se** a mensagem contém `(401)`. Isso é esperado.

### Passo 5 — Empresa da Mariana

```powershell
Invoke-RestMethod http://127.0.0.1:8000/api/v1/empresa -Headers $h | ConvertTo-Json -Depth 5
```

**Passou se** `data.nome` = `Agência Educ` e `data.plano` = `pro`.

**401** → volte ao passo 2 e confira `$h.Authorization` (falta `Bearer`).

### Passo 6 — Clientes da Educ (isolamento)

```powershell
(Invoke-RestMethod http://127.0.0.1:8000/api/v1/clientes -Headers $h).data | Select-Object id, nome_fantasia, fee_mensal
```

**Passou se** aparecem **Educ** (fee 8000) e **Cliente C** (fee 4000).

**Falhou se** aparecer **Cliente Norte** — vazou o outro tenant.

### Passo 7 — Tarefa seed

```powershell
(Invoke-RestMethod http://127.0.0.1:8000/api/v1/tarefas -Headers $h).data | Select-Object id, titulo, status, atrasada
```

**Passou se** existe `Reels — Cliente Educ`, `status` = `execucao`. `atrasada` pode ser True ou False conforme o relógio (prazo = fim do dia do seed). Atraso é **flag**, não uma coluna do Kanban.

### Passo 8 — Margem (fórmula)

```powershell
(Invoke-RestMethod "http://127.0.0.1:8000/api/v1/relatorios/margem?competencia=2026-08" -Headers $h).data | ConvertTo-Json -Depth 6
```

**Passou se:**

- `competencia` = `2026-08`
- linha **Educ**: `fee` = 8000, `custo` maior que 0 (seed aponta ~2,67 h × R$70 ≈ 187), `margem` = fee − custo
- linha **Cliente C**: fee 4000, custo 0, margem 4000

**403** neste endpoint com o token da Mariana = bug (ela é admin). 403 é esperado só para Ana (colaborador).

### Passo 9 — Admin da Educ não lista a plataforma

```powershell
try { Invoke-RestMethod http://127.0.0.1:8000/api/v1/empresas -Headers $h } catch { $_.Exception.Message }
```

**Passou se** a mensagem contém `(403)`. Só o Super Admin lista todas as agências.

### Passo 10 — Trocar de óculos: Studio Norte

Ainda no terminal 2. Isso **substitui** `$h` pelo token do Ops. Depois disso as chamadas deixam de ser da Mariana.

```powershell
php artisan gestorjob:token ops@studionorte.local
```

Cole de novo o bloco `$token` + `$h` que o comando imprimir. Confira `$h.Authorization` (tem que ter `Bearer`).

```powershell
(Invoke-RestMethod http://127.0.0.1:8000/api/v1/clientes -Headers $h).data | Select-Object nome_fantasia
```

**Passou se** só **Cliente Norte**.

**Falhou se** aparecer Educ ou Cliente C.

### Checklist (marque na cabeça; se um item falhar, não feche o gate)

- [ ] Passo 3 — health 200
- [ ] Passo 4 — clientes sem token = 401
- [ ] Passo 5 — empresa = Agência Educ
- [ ] Passo 6 — clientes Educ + C, sem Norte
- [ ] Passo 7 — tarefa Reels em execucao
- [ ] Passo 8 — margem Educ fee 8000 e custo > 0
- [ ] Passo 9 — GET /empresas = 403
- [ ] Passo 10 — token Norte vê só Cliente Norte

### Se der erro

| Sintoma | Causa típica | O que fazer |
|---------|----------------|-------------|
| 401 em empresa/clientes/tarefas | Header sem a palavra `Bearer` | Passo 2: `$h.Authorization` tem que começar com `Bearer 2\|` |
| Conexão recusada | `serve` não está no ar | Terminal 1: `php artisan serve` |
| Usuário não encontrado no token | Banco sem seed | Terminal 1: parar serve, `migrate:fresh --seed --force`, servir de novo |
| JSON aparece como `@{...}` | PowerShell resumiu o objeto | Encadear `\| ConvertTo-Json -Depth 6` |
| 403 em `/relatorios/margem` com Mariana | Token errado (Ana?) | `gestorjob:token` sem e-mail = Mariana |

Não precisa testar o SPA nesta fase. http://localhost:5173 ainda mostra só “Bootstrap OK”.

### Frases prontas

OK (todos os 8 itens do checklist):
```
Use educraft-dev-orchestrator. Smoke F1 gestor-job OK. fechar gate
```

Ajuste:
```
Use educraft-dev-orchestrator.
ajuste gestor-job
<o que viu de errado, com o passo e o JSON>
```

## Notas

- Sanctum Bearer, **sem** `statefulApi()` (lição Nexo / F0).
- Token de smoke: comando Artisan, não tinker (lição nfse CSRF / tinker vs PS).
- Fórmula: **Fee − (Σ horas × custo/hora) = margem**. Atraso = badge (`atrasada`), não coluna do Kanban.
- UI de clientes/Kanban/timer fica na F3.
