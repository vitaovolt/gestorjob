# Deploy — Gestor Job

Padrão Educraft (igual **nfse-empresa**): [educraft-devkit/standards/DEPLOY-GITHUB.md](../educraft-devkit/standards/DEPLOY-GITHUB.md).

**Modelo:** CI no GitHub-hosted → Deploy no **self-hosted runner** na EC2.  
**Não** abrir SSH (22) para o mundo. **Não** usar `SSH_HOST` / `SSH_PRIVATE_KEY` no Actions.

| Item | Valor |
|------|--------|
| Domínio | https://app.gestorjob.com.br |
| EC2 | `34.224.58.173` (origem; DNS via Cloudflare) |
| Path | `/var/www/gestorjob` |
| Stack | Laravel API (`code/backend`) + SPA (`code/frontend/dist`) no mesmo host (`/api` → PHP-FPM) |

## Automação no servidor (os dois)

| Mecanismo | O que faz | Como está |
|-----------|-----------|-----------|
| **crontab** `* * * * * php artisan schedule:run` | Dispara o scheduler Laravel (ex.: `gestor:avisos-prazo` às 07:00) | instalado no user `ubuntu` |
| **systemd** `gestorjob-queue` | `php artisan queue:work database` — processa e-mails/jobs da fila | `enabled` + `active` |

Sem o cron, o schedule não roda. Sem o worker, e-mails/jobs ficam parados na tabela `jobs`.

## E-mail (Amazon SES)

Padrão kit: [educraft-devkit/standards/SES-CLOUDFLARE.md](../educraft-devkit/standards/SES-CLOUDFLARE.md).  
Tokens deste domínio: [SES-CLOUDFLARE.md](SES-CLOUDFLARE.md). Conta AWS = mesma da EC2 (`205472166347`).

## Fluxo

```
Push/merge em main
  → CI (ubuntu-latest: PHPUnit + npm build)
  → Deploy (self-hosted na EC2): git fetch → composer → migrate → npm build → reload
```

Disparo manual: Actions → **Deploy to Production** → Run workflow.

## Secrets (repositório GitHub)

| Secret | Obrigatório? | Função |
|--------|--------------|--------|
| `DEPLOY_PATH` | sim | `/var/www/gestorjob` |
| `REPO_DEPLOY_KEY` | não* | Fallback se faltar `~/.ssh/gestorjob_github` |

\*Preferido: chave na EC2. Sem chave, o workflow usa HTTPS (repo público).

Cadastrar no GitHub → Settings → Secrets → Actions:

- `DEPLOY_PATH` = `/var/www/gestorjob`

Deploy key pública (já gerada na EC2) → Settings → Deploy keys:

```
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIKQQTCP9lqkOcOwV/XQ3gp9zA7fU6+p74wsBk1ZSAcgj gestorjob-ec2-deploy
```

**Não** criar: `SSH_HOST`, `SSH_USER`, `SSH_PRIVATE_KEY`.

## Produção atual (2026-08-16)

| Item | Status |
|------|--------|
| App | https://app.gestorjob.com.br |
| Health | `/api/v1/health` com `checks.database=ok` |
| TLS | Let’s Encrypt (certbot) |
| Queue | `gestorjob-queue.service` |
| Node | **20.x** no servidor (Vite 8 exige ≥20) |
| Path | `/var/www/gestorjob` |
## No servidor (uma vez)

### 1) Clone

```bash
sudo mkdir -p /var/www/gestorjob && sudo chown -R ubuntu:ubuntu /var/www/gestorjob
cd /var/www/gestorjob
git clone git@github.com:vitaovolt/gestorjob.git .
```

### 2) Deploy Key no disco

```bash
ssh-keygen -t ed25519 -C "gestorjob-ec2-deploy" -f ~/.ssh/gestorjob_github -N ""
cat ~/.ssh/gestorjob_github.pub
```

GitHub → Deploy keys → Add (`EC2 gestorjob`, sem write).

### 3) Runner self-hosted

Labels `self-hosted`, `Linux`, `X64` — status **Idle**.

### 4) App + Security Group

1. DB PostgreSQL `gestor_job` + usuário
2. `code/backend/.env` de produção (não versionar) — ver abaixo
3. `php artisan key:generate`
4. Nginx: `deploy/nginx/app.gestorjob.com.br.conf` → sites-enabled + `certbot --nginx`
5. Queue: `deploy/systemd/gestorjob-queue.service`
7. Cloudflare: registro **A** `app` → IP público atual da EC2 (proxied OK); SSL Full ou Full (strict)
8. Security Group: porta **22** só no IP `/32` do admin

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.gestorjob.com.br
FRONTEND_URL=https://app.gestorjob.com.br
DB_CONNECTION=pgsql
DB_DATABASE=gestor_job
QUEUE_CONNECTION=database
```

SPA build: `VITE_API_URL=/api/v1` (proxy Nginx no mesmo domínio).

## Artefatos no repo

- `deploy/nginx/app.gestorjob.com.br.conf`
- `deploy/systemd/gestorjob-queue.service`

## Smoke pós-deploy

```bash
curl -sS https://app.gestorjob.com.br/api/v1/health
# esperado: "status":"ok", "checks":{"database":"ok"}
```

Checklist: login SPA (`mariana@agenciaeduc.local` / `password` no seed) · criar tarefa · health 200 · headers `X-Frame-Options`.

### E-mail (Amazon SES)

Ver [SES-CLOUDFLARE.md](SES-CLOUDFLARE.md) — DNS no Cloudflare + `MAIL_MAILER=ses`.

### Security Group

Porta **22** só no IP `/32` do admin. 80/443 abertos (ou só Cloudflare → origem).
