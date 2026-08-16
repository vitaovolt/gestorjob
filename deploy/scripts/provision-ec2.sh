#!/bin/bash
set -euo pipefail

echo "=== 0) disk / ownership ==="
df -h / | tail -1
sudo chown -R ubuntu:ubuntu /var/www/gestorjob
cd /var/www/gestorjob

echo "=== 1) deploy key ==="
if [ ! -f "$HOME/.ssh/gestorjob_github" ]; then
  ssh-keygen -t ed25519 -C "gestorjob-ec2-deploy" -f "$HOME/.ssh/gestorjob_github" -N ""
fi
chmod 600 "$HOME/.ssh/gestorjob_github"
echo "PUBKEY:"
cat "$HOME/.ssh/gestorjob_github.pub"

echo "=== 2) pull latest (HTTPS) ==="
mkdir -p ~/.ssh
ssh-keyscan -H github.com >> ~/.ssh/known_hosts 2>/dev/null || true
git remote set-url origin https://github.com/vitaovolt/gestorjob.git
git fetch origin
git reset --hard origin/main
git log -1 --oneline

echo "=== 3) database ==="
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)
sudo -u postgres psql -v ON_ERROR_STOP=1 <<SQL
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'gestor_job') THEN
    CREATE ROLE gestor_job LOGIN PASSWORD '${DB_PASS}';
  ELSE
    ALTER ROLE gestor_job WITH LOGIN PASSWORD '${DB_PASS}';
  END IF;
END
\$\$;
SQL
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='gestor_job'" | grep -q 1 \
  || sudo -u postgres psql -c "CREATE DATABASE gestor_job OWNER gestor_job;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE gestor_job TO gestor_job;"
# schema privileges (PG15+)
sudo -u postgres psql -d gestor_job -c "GRANT ALL ON SCHEMA public TO gestor_job;" || true
umask 077
printf '%s' "$DB_PASS" > /tmp/gestor_job_db_pass.txt

echo "=== 4) .env ==="
cd /var/www/gestorjob/code/backend
if [ ! -f .env ]; then
  cp .env.example .env
fi
python3 - <<'PY'
from pathlib import Path
p = Path('.env')
text = p.read_text()
lines = text.splitlines()
kv = {}
order = []
for line in lines:
    if not line.strip() or line.strip().startswith('#') or '=' not in line:
        order.append(('raw', line))
        continue
    k, _, v = line.partition('=')
    kv[k] = v
    order.append(('kv', k))

updates = {
  'APP_NAME': '"Gestor Job"',
  'APP_ENV': 'production',
  'APP_DEBUG': 'false',
  'APP_URL': 'https://app.gestorjob.com.br',
  'FRONTEND_URL': 'https://app.gestorjob.com.br',
  'LOG_LEVEL': 'error',
  'DB_CONNECTION': 'pgsql',
  'DB_HOST': '127.0.0.1',
  'DB_PORT': '5432',
  'DB_DATABASE': 'gestor_job',
  'DB_USERNAME': 'gestor_job',
  'DB_PASSWORD': Path('/tmp/gestor_job_db_pass.txt').read_text().strip(),
  'SESSION_DOMAIN': 'app.gestorjob.com.br',
  'SANCTUM_STATEFUL_DOMAINS': 'app.gestorjob.com.br',
  'QUEUE_CONNECTION': 'database',
  'MAIL_MAILER': 'log',
  'MAIL_FROM_ADDRESS': '"noreply@gestorjob.com.br"',
}
for k,v in updates.items():
    kv[k] = v

out = []
seen = set()
for kind, val in order:
    if kind == 'raw':
        out.append(val)
    else:
        out.append(f'{val}={kv[val]}')
        seen.add(val)
for k,v in updates.items():
    if k not in seen:
        out.append(f'{k}={v}')
p.write_text('\n'.join(out) + '\n')
print('env_written')
PY
rm -f /tmp/gestor_job_db_pass.txt
chmod 600 .env

echo "=== 5) composer + key + migrate ==="
composer install --no-dev --optimize-autoloader --no-interaction
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi
php artisan migrate --force
php artisan db:seed --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "=== 6) frontend build ==="
cd /var/www/gestorjob/code/frontend
npm ci
VITE_API_URL=/api/v1 npm run build

echo "=== 7) nginx ==="
sudo cp /var/www/gestorjob/deploy/nginx/app.gestorjob.com.br.conf /etc/nginx/sites-available/app.gestorjob.com.br.conf
sudo ln -sf /etc/nginx/sites-available/app.gestorjob.com.br.conf /etc/nginx/sites-enabled/app.gestorjob.com.br.conf
mkdir -p /var/www/gestorjob/code/frontend/dist
sudo nginx -t
sudo systemctl reload nginx

echo "=== 8) systemd queue ==="
sudo cp /var/www/gestorjob/deploy/systemd/gestorjob-queue.service /etc/systemd/system/gestorjob-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now gestorjob-queue

echo "=== 9) local smoke ==="
curl -sS -H 'Host: app.gestorjob.com.br' http://127.0.0.1/api/v1/health || true
echo
echo "DONE"
