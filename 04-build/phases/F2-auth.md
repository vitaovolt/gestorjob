# F2 Auth — `gestor-job`

**Objetivo:** Login, eu, logout e refresh com Sanctum Bearer. Tela de login + rota protegida. Sem Kanban (F3). Sem `statefulApi`/CSRF.

## Arquivos

- `code/backend/app/Http/Controllers/Api/AuthController.php`
- `code/backend/app/Http/Requests/LoginRequest.php`
- `code/backend/routes/api.php`
- `code/backend/tests/Feature/AuthTest.php`
- `code/backend/docs/openapi.yaml`
- `code/frontend/src/api/auth.js` + `client.js` (interceptor Bearer)
- `code/frontend/src/context/AuthContext.jsx`
- `code/frontend/src/pages/LoginPage.jsx`
- `code/frontend/src/pages/HomePage.jsx`
- `code/frontend/src/components/layout/ProtectedRoute.jsx`

Rotas privadas continuam no grupo `auth:sanctum` (não `middleware('auth')` de web).

## Critério de done

- [x] Escopo da fase implementado
- [x] Suite E2E da fase **verde** (agente executou)
- [x] Roteiro manual preenchido (seeds, URLs, passos)
- [x] OpenAPI atualizado
- [x] LESSONS.md da fase + sync KB
- [x] Rotas privadas no grupo `auth:sanctum`
- [x] Feature: 401 sem token em rota protegida
- [x] Smoke manual do operador (2026-08-14)

## Endpoints


| Método | Path                   | Auth                                  |
| ------ | ---------------------- | ------------------------------------- |
| POST   | `/api/v1/auth/login`   | público (`throttle:login` 5/min)      |
| GET    | `/api/v1/auth/me`      | Sanctum                               |
| POST   | `/api/v1/auth/logout`  | Sanctum (apaga o token atual)         |
| POST   | `/api/v1/auth/refresh` | Sanctum (rotaciona: o anterior morre) |


## Suite E2E (automática) — gate

Regra: `educraft-devkit/standards/TESTES-FASE.md`


| #   | Cenário                                                                        | Arquivo                      | OK? |
| --- | ------------------------------------------------------------------------------ | ---------------------------- | --- |
| 1   | Login devolve Bearer + grava `personal_access_tokens`; e-mail case-insensitive | `tests/Feature/AuthTest.php` | [x] |
| 2   | Senha errada → 422 e **não** cria token                                        | `tests/Feature/AuthTest.php` | [x] |
| 3   | me/logout/refresh/clientes sem token → 401                                     | `tests/Feature/AuthTest.php` | [x] |
| 4   | Logout apaga o token; o mesmo Bearer deixa de autenticar                       | `tests/Feature/AuthTest.php` | [x] |
| 5   | Refresh rotaciona; token antigo 401, novo 200                                  | `tests/Feature/AuthTest.php` | [x] |
| 6   | Token inventado → 401                                                          | `tests/Feature/AuthTest.php` | [x] |
| 7   | Seed Mariana consegue logar na Agência Educ                                    | `tests/Feature/AuthTest.php` | [x] |
| 8   | Regressão F0/F1 (health, domínio, isolamento)                                  | demais Feature/Unit          | [x] |
| 9   | Frontend build                                                                 | `npm run build`              | [x] |


**Comandos (agente rodou):**

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan test
# Resultado: 23 passed (141 assertions)

cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npm run build
# Resultado: built OK (vite 8.2.1)
```

Resultado: **23 passed / 0 failed** + build FE verde.

## Dois tipos de teste (não misture)


| Tipo             | Quem roda                         | O que é                              | Você precisa fazer?     |
| ---------------- | --------------------------------- | ------------------------------------ | ----------------------- |
| Automático (E2E) | Agente, PHPUnit + `npm run build` | 23 testes + bundle                   | Já rodou. Verde.        |
| Smoke manual     | Você                              | API de login **e** a tela no browser | **OK em 2026-08-14** |


Nesta fase o smoke é **API + tela**. Não use `gestorjob:token` como caminho principal (isso era F1). Não espere o Kanban — a home autenticada só confirma a sessão.

---

## Como testar manualmente

Dois terminais. O 1 fica no `serve`. O 2 gera o seed e chama a API. O browser usa o frontend no 3 (ou o mesmo 2 depois da API).

### Passo 0 — o que o seed coloca


| E-mail                       | Senha      | Quem                  |
| ---------------------------- | ---------- | --------------------- |
| `mariana@agenciaeduc.local`  | `password` | Admin da Agência Educ |
| `ana@agenciaeduc.local`      | `password` | Colaborador Educ      |
| `ops@studionorte.local`      | `password` | Admin Studio Norte    |
| `plataforma@gestorjob.local` | `password` | Super Admin           |


### Passo 1 — Terminal 1: API

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\backend
php artisan migrate:fresh --seed --force
php artisan serve
```

**OK:** `Server running on [http://127.0.0.1:8000]`. Deixe aberto.

### Passo 2 — Terminal 2: frontend

```powershell
cd C:\Users\Admin\Documents\EDUCRAFT\GestorJob\code\frontend
npm run dev
```

**OK:** Vite em [http://localhost:5173](http://localhost:5173)

### Passo 3 — API login (PowerShell, terminal 3 ou o 1 **depois** de parar o serve — melhor um terceiro)

Se só tiver dois terminais, faça este bloco **antes** do `npm run dev`, ou abra um terceiro PowerShell.

```powershell
$body = @{ email = "mariana@agenciaeduc.local"; password = "password" } | ConvertTo-Json
$login = Invoke-RestMethod http://127.0.0.1:8000/api/v1/auth/login -Method Post -Body $body -ContentType "application/json"
$login.data.user.name
$login.data.user.empresa.nome
$login.data.token_type
$token = $login.data.token
$h = @{ Authorization = "Bearer $token"; Accept = "application/json" }
$h.Authorization
```

**Passou se:** nome `Mariana Costa`, empresa `Agência Educ`, tipo `Bearer`, e `$h.Authorization` **começa** com `Bearer 2|`.

**422** → senha errada ou seed não rodou.  
**Conexão recusada** → `serve` não está no ar.

### Passo 4 — /me e 401 sem token

```powershell
Invoke-RestMethod http://127.0.0.1:8000/api/v1/auth/me -Headers $h | ConvertTo-Json -Depth 5
try { Invoke-RestMethod http://127.0.0.1:8000/api/v1/auth/me } catch { $_.Exception.Message }
```

**Passou se:** o primeiro mostra o e-mail da Mariana; o segundo contém `(401)`.

### Passo 5 — senha errada não entra

```powershell
$ruim = @{ email = "mariana@agenciaeduc.local"; password = "errada" } | ConvertTo-Json
try {
  Invoke-RestMethod http://127.0.0.1:8000/api/v1/auth/login -Method Post -Body $ruim -ContentType "application/json"
} catch { $_.Exception.Message }
```

**Passou se:** `(422)`.

### Passo 6 — tela de login

1. Abra [http://localhost:5173](http://localhost:5173)
  **Esperado:** redireciona para `/login`. Card Gestor**Job**, campos e-mail/senha, botão **Entrar**.
2. A tela já vem preenchida com Mariana / `password`. Clique **Entrar**.
  **Esperado:** botão vira **Entrando…** (não clique de novo). Depois: “Olá, Mariana”, “Agência Educ · admin”.
3. Clique **Sair**.
  **Esperado:** volta para `/login`.
4. Tente senha `errada`.
  **Esperado:** mensagem de credenciais inválidas; continua no login.

Não precisa abrir Kanban. Não precisa do token artesanal.

### Checklist

- [x] Login API da Mariana devolve Bearer + Agência Educ
- [x] `/auth/me` com Bearer 200; sem Bearer 401
- [x] Senha errada 422
- [x] [http://localhost:5173](http://localhost:5173) abre o login (não o card “Bootstrap OK”)
- [x] Entrar mostra “Olá, Mariana”
- [x] Sair volta ao login
- [x] Senha errada na tela mostra erro, não entra

### Se der erro


| Sintoma                                        | Causa                                     | O que fazer                                     |
| ---------------------------------------------- | ----------------------------------------- | ----------------------------------------------- |
| Ainda aparece “Bootstrap OK”                   | Frontend antigo / Vite não recarregou     | Confirme `npm run dev` neste repo; Ctrl+F5      |
| Login API 401                                  | Você mandou o token sem `Bearer` no `/me` | `$h.Authorization` tem que começar com `Bearer` |
| Tela “Não foi possível entrar” com senha certa | API fora ou proxy                         | `serve` no :8000; Vite proxy `/api` → 8000      |
| 429 no login                                   | Muitas tentativas (5/min)                 | Espere 1 minuto                                 |


### Frases prontas

OK:

```
Use educraft-dev-orchestrator. Smoke F2 gestor-job OK. fechar gate
```

Ajuste:

```
Use educraft-dev-orchestrator.
ajuste gestor-job
<o que viu de errado, com o passo>
```

## Notas

- Bearer, **sem** `statefulApi()` (lição Nexo CSRF).
- Convite e “esqueci a senha” não entram nesta fase.
- `php artisan gestorjob:token` ainda existe, mas o fluxo principal passou a ser o login.

