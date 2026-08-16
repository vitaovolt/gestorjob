#!/bin/bash
set -euo pipefail
for d in app.lindinhaperfumaria.com.br barbasclub.educraft.com.br clinicaveterinaria2v.educraft.com.br quiz.institutolg.com.br saborearte.educraft.com.br; do
  echo "Deleting cert: $d"
  sudo certbot delete --cert-name "$d" --non-interactive 2>&1 | tail -5 || true
done
echo "Remaining:"
sudo ls /etc/letsencrypt/live/
sudo ls /etc/letsencrypt/renewal/
df -h / | tail -1
