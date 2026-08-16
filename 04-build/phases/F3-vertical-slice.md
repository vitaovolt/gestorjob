# F3 Vertical slice — Kanban + timer — `gestor-job`

**Objetivo:** Fluxo completo na tela: login → quadro Kanban → timer por fase → mover card. Playwright obrigatório. Atraso = badge, não coluna.

## Arquivos

- `code/backend/app/Actions/IniciarTimer.php` + `PausarTimer.php`
- `code/backend/app/Http/Controllers/Api/TarefaController.php` (timer + checklist)
- `code/backend/database/migrations/2026_08_14_210000_apontamentos_um_timer_aberto_por_user.php`
- `code/backend/tests/Feature/TimerKanbanTest.php`
- `code/frontend/src/pages/KanbanPage.jsx` + `TaskDrawer` + `CreateTaskModal` + `AppShell` + `ToastContext`
- `code/frontend/e2e/kanban-timer.spec.js` + `playwright.config.js`

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Feature do slice afirma efeito (DB), não só HTTP 200
- [x] Listagens com `with()` (cliente, serviço, responsáveis, checklist, timer aberto)
- [x] UX: CTA “+ Tarefa” / fases do timer; um primário laranja; toast suave abaixo do cabeçalho (não cobre CTAs); “Processando…”
- [x] Smoke manual do operador (2026-08-14)

## Endpoints novos

| Método | Path | Efeito |
|--------|------|--------|
| POST | `/api/v1/tarefas/{id}/timer` | Abre apontamento; um por usuário; `a_fazer` → `execucao` |
| POST | `/api/v1/tarefas/{id}/timer/pausar` | Fecha apontamento e grava `segundos` (inteiro) |
| PUT | `/api/v1/tarefas/{id}/checklist/{item}` | Marca item do checklist |

Mover coluna continua `PUT /tarefas/{id}` com `status`.

## Suite E2E (automática) — gate

| # | Cenário | Arquivo | OK? |
|---|---------|---------|-----|
| 1 | Timer 401 sem token | `TimerKanbanTest.php` | [x] |
| 2 | Iniciar/pausar grava apontamento + segundos | `TimerKanbanTest.php` | [x] |
| 3 | Segundo timer em outra tarefa → 422 (unique + Action) | `TimerKanbanTest.php` | [x] |
| 4 | Trocar fase encerra o apontamento anterior | `TimerKanbanTest.php` | [x] |
| 5 | Checklist `feito` no DB | `TimerKanbanTest.php` | [x] |
| 6 | Timer de outro tenant → 404 | `TimerKanbanTest.php` | [x] |
| 7 | Regressão F0–F2 | demais PHPUnit | [x] |
| 8 | Browser: login → arrastar → timer → pause/play retoma | `e2e/kanban-timer.spec.js` | [x] |
| 9 | Frontend build | `npm run build` | [x] |

**Comandos (agente rodou):**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan test
# 30 passed (182 assertions)

cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npx playwright test
# 1 passed
npm run build
# built OK
```

Resultado: **30 passed / 0 failed** (PHPUnit) + **1 passed** (Playwright) + build verde.

## Dois tipos de teste (não misture)

| Tipo | Quem roda | O que é | Você precisa fazer? |
|------|-----------|---------|---------------------|
| Automático | Agente | PHPUnit + Playwright + build | Já rodou. Verde. |
| Smoke | Você | Login no browser e usar o quadro | Feito (2026-08-14) |

Nesta fase o smoke é **tela** (Kanban + timer). A API já foi coberta pelo PHPUnit/Playwright.

---

## Como testar manualmente

### Passo 1 — Terminal 1: API

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

**OK:** `Server running on [http://127.0.0.1:8000]`

Se o Playwright rodou há pouco, o banco já foi resetado. Rode o `migrate:fresh --seed` de novo para ter o card **Reels — Cliente Educ** em Execução.

### Passo 2 — Terminal 2: frontend

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npm run dev
```

**OK:** http://localhost:5173

### Passo 3 — Login e hover

1. Abra http://localhost:5173  
   **Esperado:** tela de login. **Entrar** com cursor de mão.
2. Entre com Mariana / `password`.  
   **Esperado:** quadro Kanban. Passe o mouse no card **Reels — Cliente Educ**: o card sobe, a borda fica laranja e o cursor vira **mãozinha de arrastar** (não a seta).

### Passo 4 — Arrastar

1. Arraste o Reels da coluna **Em execução** para **Em revisão**.  
   **Esperado:** a coluna destino destaca em laranja; recado “Tarefa movida” **abaixo** do cabeçalho (os botões + Tarefa e Sair continuam visíveis); o card muda de coluna.

### Passo 5 — Timer (relógio grande)

1. Clique no card (não arraste).  
   **Esperado:** drawer. Relógio grande + ícone de **play** ao lado. Se a tarefa já tem fase (seed: Produção), o texto é **Pausado · Produção**.
2. Clique **Produção** (ou o play).  
   **Esperado:** o bloco vira laranja, texto **Rodando · Produção**, o relógio anda; o botão vira **pause** ao lado do relógio; recado **por cima** do drawer.
3. Clique o **pause**.  
   **Esperado:** **Pausado · Produção**; o relógio **não zera** (fica o tempo da fase); aparece o **play**.
4. Clique o **play**.  
   **Esperado:** volta a **Rodando** na mesma fase, continuando o tempo.
5. Clique **Revisão** (outra fase).  
   **Esperado:** relógio **começa do zero** em Revisão.

Não inicie dois timers em cards diferentes sem pausar — a API recusa (422).

### Passo 6 — Criar (opcional)

1. Clique **+ Tarefa** (único botão laranja do topo).  
   **Esperado:** modal. Título, **Criar tarefa** (vira **Processando…**).
2. Card novo aparece em **A fazer**.

### Checklist

- [ ] Hover no card: mãozinha + elevação + borda laranja
- [ ] Arrastar Reels para outra coluna funciona
- [ ] Login abre o Kanban
- [ ] Produção mostra relógio **Rodando** (não só o botão)
- [ ] Pause/play ficam **ao lado do relógio** (não no meio das fases)
- [ ] Play retoma a mesma fase; outra fase começa do zero
- [ ] Pausar para o timer
- [ ] Atraso não é uma coluna
- [ ] + Tarefa é o CTA primário
- [ ] Toast não cobre + Tarefa / Sair (fica abaixo do cabeçalho)
- [ ] Com o drawer aberto, o toast aparece **na frente** (não atrás do modal)

### Se der erro

| Sintoma | Causa | O que fazer |
|---------|-------|-------------|
| Quadro vazio | Seed não rodou / Playwright limpou o DB | `migrate:fresh --seed --force` e recarregue |
| “Suba a API em :8000” | `serve` fora | Terminal 1 |
| Ainda vê “Login OK / próxima fase” | Vite antigo | Confirme `npm run dev` neste repo; Ctrl+F5 |
| 422 ao iniciar timer | Já há um timer aberto | Pausar no card anterior |

### Frases prontas

OK:
```
Use educraft-dev-orchestrator. Smoke F3 gestor-job OK. fechar gate
```

Ajuste:
```
Use educraft-dev-orchestrator.
ajuste gestor-job
<o que viu de errado, com o passo>
```

## Notas

- Um timer aberto por usuário (índice único parcial no Postgres + Action).
- Play retoma a última `fase_timer` (novo apontamento; o relógio soma os segundos fechados da fase). Trocar de fase zera o relógio na fase nova.
- `segundos` gravado como inteiro (Carbon 3 devolve float; coluna PG é integer).
- Lista/calendário, clientes CRUD na tela, margem UI = F4.
