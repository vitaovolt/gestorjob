# Amazon SES + Cloudflare — Gestor Job

Região: **us-east-1**  
Domínio: **gestorjob.com.br**  
Remetente: `noreply@gestorjob.com.br`

## 1) Registros no Cloudflare (DNS)

Tipo **CNAME**, Proxy = **DNS only** (nuvem cinza) — DKIM/MAIL FROM não funcionam com proxy laranja.

### DKIM (3 registros)

| Nome | Destino |
|------|---------|
| `2zcptobgi7yonagg2uqecqkjenlxuwtu._domainkey` | `2zcptobgi7yonagg2uqecqkjenlxuwtu.dkim.amazonses.com` |
| `os46xbwwvqxhn5oojw5jxj2i6lktdflo._domainkey` | `os46xbwwvqxhn5oojw5jxj2i6lktdflo.dkim.amazonses.com` |
| `pyp3h4gzf3wurhzye6wzvqnydsbmoiwq._domainkey` | `pyp3h4gzf3wurhzye6wzvqnydsbmoiwq.dkim.amazonses.com` |

### MAIL FROM (`mail.gestorjob.com.br`)

| Tipo | Nome | Conteúdo | Prioridade |
|------|------|----------|------------|
| MX | `mail` | `feedback-smtp.us-east-1.amazonses.com` | 10 |
| TXT | `mail` | `v=spf1 include:amazonses.com ~all` | — |

### Domínio raiz (SPF + DMARC)

| Tipo | Nome | Conteúdo |
|------|------|----------|
| TXT | `@` | `v=spf1 include:amazonses.com ~all` *(se já existir SPF, só acrescente `include:amazonses.com` no registro atual — não crie dois SPF)* |
| TXT | `_dmarc` | `v=DMARC1; p=none; rua=mailto:dmarc@gestorjob.com.br` |

TTL: Auto / 5 min.

## 2) Conferir verificação SES

```bash
aws sesv2 get-email-identity --email-identity gestorjob.com.br --region us-east-1 --query "{Verified:VerifiedForSendingStatus,Dkim:DkimAttributes.Status,MailFrom:MailFromAttributes.MailFromDomainStatus}"
```

Esperado: `Verified: true`, DKIM `SUCCESS`, MailFrom `SUCCESS`.

## 3) Sandbox

Conta ainda em **sandbox**: só envia para endereços/domínios **verificados** no SES.  
Pedir produção: SES Console → Account dashboard → Request production access  
(caso de uso: transactional — convite, reset senha, prazo).

Enquanto sandbox: verifique um e-mail seu:

```bash
aws sesv2 create-email-identity --email-identity seu@email.com --region us-east-1
# confirme o link que chegar na caixa
```

## 4) App (já preparado)

`.env` produção:

```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@gestorjob.com.br
MAIL_FROM_NAME="Gestor Job"
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-1
```

Pacote: `aws/aws-sdk-php` no backend.  
Após alterar `.env`: `php artisan config:cache` + `sudo systemctl restart gestorjob-queue`.
