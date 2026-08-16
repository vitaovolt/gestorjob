#!/bin/bash
cd /var/www/gestorjob/code/backend
echo "=== MAIL / QUEUE env ==="
grep -E '^(MAIL_|QUEUE_|APP_URL|FRONTEND)' .env || true
echo "=== queue service ==="
systemctl is-active gestorjob-queue
systemctl is-enabled gestorjob-queue
echo "=== pending jobs ==="
sudo -u postgres psql -d gestor_job -c "SELECT COUNT(*) AS pending FROM jobs;"
echo "=== failed jobs ==="
sudo -u postgres psql -d gestor_job -c "SELECT COUNT(*) AS failed FROM failed_jobs;"
echo "=== mailer from config cache ==="
php artisan tinker --execute="echo config('mail.default').PHP_EOL; echo config('mail.from.address').PHP_EOL; echo config('queue.default').PHP_EOL;"
