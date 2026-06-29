# PF_new_ondemo_back

API **Portal Fornecedor On Demand** — Laravel 10, PHP 8.1+, PostgreSQL (Supabase), Redis.

## Stack

- Laravel 10 (REST; JWT na Fase 0)
- PostgreSQL — Supabase em homolog/prod; Docker local no monorepo
- Redis (filas e cache)
- AWS S3 / MinIO (PDFs e anexos)

## Estrutura

```
app/
├── Models/                    # Tenant, UsuarioCliente, …
├── Modules/
│   ├── Identidade/            # Fase 0 — cadastro, login, perfis
│   ├── Assinatura/            # Trial + gateway
│   ├── Contratacao/
│   ├── Fornecedor/
│   ├── NotaFiscal/
│   └── IntegracaoSenior/
database/migrations/           # Espelho das tabelas Supabase (Fase 0)
routes/api.php                 # /api/health, /api/v1/…
```

## Setup (VPS ou máquina com Composer + extensões PHP)

```bash
composer install --no-dev --optimize-autoloader
cp ../env/laravel.homolog.supabase.local .env   # ou .env.example
php artisan key:generate
php artisan migrate --force    # idempotente se tabelas já existirem no Supabase
php artisan db:seed --class=TenantDemoSeeder    # opcional — seed demo
```

**Supabase:** `config/database.php` já usa `DB_SSLMODE=require`. Homolog: porta **6543** (pooler).

## Desenvolvimento local (Docker)

Monorepo `Portal_Fornecedor_new-ondemo`:

```bash
docker compose --env-file .env up -d --build
```

## API (rascunho Fase 0)

| Método | Rota | Status |
|--------|------|--------|
| GET | `/api/health` | OK |
| POST | `/api/v1/public/cadastro` | 501 — pendente |
| POST | `/api/v1/auth/login` | 501 — pendente |
| GET | `/api/v1/me` | 501 — pendente |
| GET | `/api/v1/me/modulos` | 501 — pendente |

## Documentação

Specs em `meta_specs/` no monorepo `Portal_Fornecedor_new-ondemo`.

Config Supabase de referência: `../env/laravel.config/database.pgsql.supabase.snippet.php`
