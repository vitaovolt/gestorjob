# Backend — Gestor Job

Laravel 12 API-only (DNA Educraft desacoplado — **sem** Inertia).

## Stack

- PHP 8.2+ · Laravel 12 · Sanctum · PostgreSQL
- Rotas: `/api/v1/*`
- OpenAPI: `docs/openapi.yaml`

## Setup local

```powershell
copy .env.example .env
php artisan key:generate
powershell -File ..\..\..\educraft-devkit\scripts\ensure-pgsql-db.ps1 -BackendPath .
php artisan migrate
php artisan serve
```

Health: `GET http://localhost:8000/api/v1/health` — inclui `checks.database` (503 se o banco falhar).

Deploy: ver `docs/DEPLOY.md` na raiz do monorepo.

## Estrutura

- Controllers: `app/Http/Controllers/Api`
- Auth Sanctum (HasApiTokens) — login na F2
- Fila: `QUEUE_CONNECTION=database` (jobs table). Worker entra quando houver e-mail (F4)
- Anexos: S3 `s3://gestorjob/anexos` (privado; download pela API). PHPUnit usa disco local fake.
- Backup Postgres: `gestor:backup-postgres` → `s3://gestorjob/postgres` (diário 02:30, 14 dias)
