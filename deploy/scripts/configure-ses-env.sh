#!/bin/bash
set -euo pipefail
export KEY_ID="$1"
export SECRET="$2"
cd /var/www/gestorjob/code/backend

composer require aws/aws-sdk-php --no-interaction --update-no-dev 2>&1 | tail -25

python3 <<'PY'
import os
from pathlib import Path
p = Path('.env')
lines = p.read_text().splitlines()
updates = {
  'MAIL_MAILER': 'ses',
  'MAIL_FROM_ADDRESS': '"noreply@gestorjob.com.br"',
  'MAIL_FROM_NAME': '"Gestor Job"',
  'AWS_ACCESS_KEY_ID': os.environ['KEY_ID'],
  'AWS_SECRET_ACCESS_KEY': os.environ['SECRET'],
  'AWS_DEFAULT_REGION': 'us-east-1',
}
kv = {}
order = []
for line in lines:
    if not line.strip() or line.strip().startswith('#') or '=' not in line:
        order.append(('raw', line))
        continue
    k, _, v = line.partition('=')
    kv[k] = v
    order.append(('kv', k))
for k, v in updates.items():
    kv[k] = v
out = []
seen = set()
for kind, val in order:
    if kind == 'raw':
        out.append(val)
    else:
        out.append(f'{val}={kv[val]}')
        seen.add(val)
for k, v in updates.items():
    if k not in seen:
        out.append(f'{k}={v}')
p.write_text('\n'.join(out) + '\n')
print('env_updated')
PY

chmod 640 .env
sudo chown ubuntu:www-data .env
php artisan config:cache
sudo systemctl restart gestorjob-queue
php artisan tinker --execute="echo config('mail.default').' | '.config('mail.from.address').' | '.config('services.ses.region').' | key='.(config('services.ses.key') ? 'yes' : 'no');"
echo DONE
