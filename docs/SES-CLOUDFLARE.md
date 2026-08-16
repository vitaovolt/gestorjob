# Amazon SES + Cloudflare — Gestor Job

Padrão Educraft: [educraft-devkit/standards/SES-CLOUDFLARE.md](../educraft-devkit/standards/SES-CLOUDFLARE.md).

| Item | Valor |
|------|--------|
| Conta AWS | **`205472166347`** (mesma da EC2) |
| Região | `us-east-1` |
| Domínio | `gestorjob.com.br` |
| Remetente | `noreply@gestorjob.com.br` |
| Produção | Pedido de saída SES feito em **2026-08-16** (aguardar aprovação AWS) |

> Tokens DKIM são **por conta**. Se recriar a identidade SES, substitua os 3 CNAMEs abaixo.

## Registros Cloudflare (preencher após Create identity na conta certa)

Tipo **CNAME**, Proxy = **DNS only** (nuvem cinza).

### DKIM (3 registros)

| Nome | Destino |
|------|---------|
| `TOKEN._domainkey` | `TOKEN.dkim.amazonses.com` |
| `TOKEN._domainkey` | `TOKEN.dkim.amazonses.com` |
| `TOKEN._domainkey` | `TOKEN.dkim.amazonses.com` |

### MAIL FROM (`mail.gestorjob.com.br`)

| Tipo | Nome | Conteúdo | Prioridade |
|------|------|----------|------------|
| MX | `mail` | `feedback-smtp.us-east-1.amazonses.com` | 10 |
| TXT | `mail` | `v=spf1 include:amazonses.com ~all` | — |

### Domínio raiz (SPF + DMARC)

| Tipo | Nome | Conteúdo |
|------|------|----------|
| TXT | `@` | `v=spf1 include:amazonses.com ~all` *(se já existir SPF, só acrescente `include:amazonses.com`)* |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@gestorjob.com.br` |

## App

```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@gestorjob.com.br
MAIL_FROM_NAME="Gestor Job"
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
```

Após `.env`: `php artisan config:cache` + `sudo systemctl restart gestorjob-queue`.
