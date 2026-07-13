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

## Desenvolvimento local (Windows + Supabase, sem Docker)

1. Copiar `env/laravel.local.supabase.example` para `.env` e ajustar credenciais Supabase.
2. `php artisan key:generate` (se `APP_KEY` estiver vazio).
3. **`php artisan migrate --force`** — obrigatório antes de usar Contratação (tabelas `contratacoes`, `contratacao_qqp_itens`, etc.). Sem migrate, submeter solicitação retorna `relation "contratacoes" does not exist`.
4. Opcional: `php artisan db:seed --class=TenantDemoSeeder` (usuário demo).
5. Subir API + front: `scripts\dev-local-supabase.ps1` na raiz do monorepo.

## Desenvolvimento local (Docker)

Monorepo `Portal_Fornecedor_new-ondemo`:

```bash
docker compose --env-file .env up -d --build
php artisan migrate --force
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

### Swagger UI (OpenAPI 3)

| Ambiente | URL |
|----------|-----|
| Local Docker | http://api.portalfornecedor.local/docs |
| Local `artisan serve` | http://api.portalfornecedor.local:8000/docs |
| Homolog | https://api.homolog.portalfornecedor.com.br/docs |

Spec em `public/docs/openapi.yaml`. Use **Authorize** com `Bearer {token}` para rotas autenticadas; em login, informe `X-Tenant-Slug` (ex.: `clientex`) se não estiver no host multi-tenant.

Config Supabase de referência: `../env/laravel.config/database.pgsql.supabase.snippet.php`
