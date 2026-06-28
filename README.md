# PF_new_ondemo_back

API **Portal Fornecedor On Demand** — Laravel 10+, PHP 8.2, PostgreSQL, Redis, integrações SOAP/REST.

## Stack

- Laravel (REST + JWT)
- PostgreSQL (Supabase em prod / Docker local)
- Redis (filas e cache)
- AWS S3 / MinIO (PDFs e anexos)

## Desenvolvimento local

O Docker Compose do monorepo fica no repositório principal do projeto. Este diretório é montado no container `api`:

```bash
docker compose --env-file .env up -d --build
```

## Estrutura prevista

```
app/
├── Modules/          # Contratacao, Aprovacao, NotaFiscal, Tenant, Integracao
routes/
database/migrations/
```

## Documentação

Especificações em `meta_specs/` no monorepo local (`Portal_Fornecedor_new-ondemo`).
