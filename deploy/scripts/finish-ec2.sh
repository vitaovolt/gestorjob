#!/bin/bash
set -euo pipefail
cd /var/www/gestorjob
git fetch origin
git reset --hard origin/main

cd /var/www/gestorjob/code/backend
# ensure FRONTEND_URL still in .env
grep -q '^FRONTEND_URL=https://app.gestorjob.com.br' .env || echo 'FRONTEND_URL=https://app.gestorjob.com.br' >> .env
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

cd /var/www/gestorjob/code/frontend
npm ci
VITE_API_URL=/api/v1 npm run build

sudo cp /var/www/gestorjob/deploy/nginx/app.gestorjob.com.br.conf /etc/nginx/sites-available/app.gestorjob.com.br.conf
sudo ln -sf /etc/nginx/sites-available/app.gestorjob.com.br.conf /etc/nginx/sites-enabled/app.gestorjob.com.br.conf
sudo nginx -t
sudo systemctl reload nginx

sudo cp /var/www/gestorjob/deploy/systemd/gestorjob-queue.service /etc/systemd/system/gestorjob-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now gestorjob-queue

# cron schedule
CRON_LINE="* * * * * cd /var/www/gestorjob/code/backend && php artisan schedule:run >> /dev/null 2>&1"
crontab -l 2>/dev/null | grep -F "artisan schedule:run" >/dev/null || (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -

echo "=== smoke local ==="
curl -sS -H 'Host: app.gestorjob.com.br' http://127.0.0.1/api/v1/health
echo
systemctl is-active gestorjob-queue
echo DONE
