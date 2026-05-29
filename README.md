# Affiliate Portal

Multi-domain affiliate platform for Dutch product/category domains.

## Goal

Build one shared backend/admin application and one reusable frontend that can power multiple domains, for example:

- maskers.nl
- hartslagmeters.nl
- computeronderdelen.nl
- sweaters.nl
- ponchos.nl

Each domain can have its own theme, branding, layout choices, categories and affiliate feeds, while the backend remains shared.

## Stack

### Backend

- Laravel
- PHP 8.3+
- MariaDB/MySQL
- Redis for cache and queues
- Filament for the admin panel
- Laravel scheduler/queues for feed imports

### Frontend

- Nuxt 3
- Vue 3
- Vite
- Domain/site-aware theme configuration
- SEO-friendly server-side rendering

### Search

The first version can use MariaDB indexes/full-text search. When product volume grows, Meilisearch or Typesense can be added without changing the public frontend API.

## Repository structure

```text
backend/      Laravel API and admin application
frontend/     Nuxt frontend application
docker/       Docker support files
docs/         Architecture and implementation notes
```

## Local development concept

```bash
cp backend/.env.example backend/.env
docker compose up -d
```

Services:

- MariaDB on port `3306`
- Redis on port `6379`
- Backend API/admin through Nginx on port `8080`
- Frontend on Nuxt dev server

## First MVP

1. Create Laravel backend skeleton
2. Create Nuxt frontend skeleton
3. Add database tables for sites, partners, feeds, products, categories and clicks
4. Add Filament admin resources
5. Add a generic feed importer contract
6. Import one test feed for one domain
7. Render category/product listing pages in Nuxt
8. Add affiliate redirect tracking

## Architecture

See [`docs/architecture.md`](docs/architecture.md).

Additional notes:

- [`docs/local-development.md`](docs/local-development.md)
- [`docs/data-model.md`](docs/data-model.md)
