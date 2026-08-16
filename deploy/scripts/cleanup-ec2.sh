#!/bin/bash
set -euo pipefail

echo "=== BEFORE ==="
df -h / | tail -1

echo "=== Crontab (schedule Laravel) ==="
crontab -l || true

echo "=== Queue systemd ==="
systemctl is-active gestorjob-queue || true
systemctl is-enabled gestorjob-queue || true

echo "=== Postgres DBs ==="
sudo -u postgres psql -c '\l' | cat

echo "=== Cleanup nginx leftovers ==="
sudo rm -f \
  /etc/nginx/sites-available/app.lindinhaperfumaria.com.br \
  /etc/nginx/sites-available/barbasclub.educraft.com.br \
  /etc/nginx/sites-available/clinicaveterinaria2v.educraft.com.br \
  /etc/nginx/sites-available/instituto-lg-quiz \
  /etc/nginx/sites-enabled/app.lindinhaperfumaria.com.br \
  /etc/nginx/sites-enabled/barbasclub.educraft.com.br \
  /etc/nginx/sites-enabled/clinicaveterinaria2v.educraft.com.br \
  /etc/nginx/sites-enabled/instituto-lg-quiz

# keep default disabled if unused
if [ -L /etc/nginx/sites-enabled/default ]; then
  sudo rm -f /etc/nginx/sites-enabled/default
fi

echo "=== Cleanup Let's Encrypt certs (outros projetos) ==="
for d in app.lindinhaperfumaria.com.br barbasclub.educraft.com.br clinicaveterinaria2v.educraft.com.br quiz.institutolg.com.br saborearte.educraft.com.br; do
  if [ -f "/etc/letsencrypt/renewal/${d}.conf" ] || [ -d "/etc/letsencrypt/live/${d}" ]; then
    sudo certbot delete --cert-name "$d" --non-interactive || true
  fi
done

echo "=== Cleanup home leftovers ==="
rm -f /home/ubuntu/deploy_clinica2v /home/ubuntu/deploy_clinica2v.pub
rm -rf /home/ubuntu/ec2

echo "=== Drop unused Postgres DBs ==="
for db in barbasclub_db clinicaveterinaria2v_db lindinha_db sabor_arte_db; do
  if sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='${db}'" | grep -q 1; then
    echo "Dropping $db"
    sudo -u postgres psql -c "DROP DATABASE ${db};" || true
  fi
done

echo "=== Caches ==="
sudo apt-get clean || true
sudo rm -rf /var/cache/apt/archives/*.deb || true
rm -rf /home/ubuntu/.composer/cache 2>/dev/null || true
rm -rf /home/ubuntu/.npm/_cacache 2>/dev/null || true
rm -rf /tmp/provision-gestorjob.sh /tmp/finish-gestorjob.sh 2>/dev/null || true
# composer cache global
rm -rf /home/ubuntu/.cache/composer 2>/dev/null || true

echo "=== journal vacuum ==="
sudo journalctl --vacuum-size=50M || true

echo "=== nginx test ==="
sudo nginx -t
sudo systemctl reload nginx

echo "=== smoke ==="
curl -sS https://app.gestorjob.com.br/api/v1/health || curl -sS -H 'Host: app.gestorjob.com.br' http://127.0.0.1/api/v1/health
echo
systemctl is-active gestorjob-queue
crontab -l

echo "=== AFTER ==="
df -h / | tail -1
echo "sites-enabled:"; ls /etc/nginx/sites-enabled/
echo "certs:"; sudo ls /etc/letsencrypt/live/ || true
echo "www:"; ls /var/www/
echo "home:"; ls /home/ubuntu/
echo "DONE"
