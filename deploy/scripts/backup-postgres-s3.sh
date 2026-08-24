#!/usr/bin/env bash
# Dump do PostgreSQL direto para s3://gestorjob/postgres (sem arquivo local).
set -euo pipefail

BUCKET="${AWS_BUCKET:-gestorjob}"
PREFIX="postgres"
RETENCAO_DIAS="${BACKUP_RETENCAO_DIAS:-14}"
STAMP="$(date +%Y%m%d_%H%M%S)"
ARQUIVO="gestor_job_${STAMP}.sql.gz"

: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=5432}"
: "${DB_USERNAME:?DB_USERNAME obrigatório}"
: "${DB_DATABASE:?DB_DATABASE obrigatório}"
: "${DB_PASSWORD:?DB_PASSWORD obrigatório}"

export PGPASSWORD="$DB_PASSWORD"

pg_dump --no-owner --no-acl \
  -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" \
  --format=plain \
  | gzip -9 \
  | aws s3 cp - "s3://${BUCKET}/${PREFIX}/${ARQUIVO}" --sse AES256

aws s3 ls "s3://${BUCKET}/${PREFIX}/" \
  | awk '{print $4}' \
  | grep -E '^gestor_job_.*\.sql\.gz$' \
  | while read -r nome; do
      data="${nome#gestor_job_}"
      data="${data%.sql.gz}"
      dia="${data%%_*}"
      if [[ "$dia" =~ ^[0-9]{8}$ ]]; then
        limite="$(date -d "-${RETENCAO_DIAS} days" +%Y%m%d)"
        if [[ "$dia" < "$limite" ]]; then
          aws s3 rm "s3://${BUCKET}/${PREFIX}/${nome}"
        fi
      fi
    done

echo "OK s3://${BUCKET}/${PREFIX}/${ARQUIVO}"
