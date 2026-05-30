# Local Development

## Requirements

- Docker Desktop
- Node.js 22+ if running the frontend directly on the host
- PHP 8.3+ and Composer if running the backend directly on the host

## Docker

Copy backend environment variables:

```bash
cp backend/.env.example backend/.env
```

Start the stack:

```bash
docker compose up -d
```

Useful URLs:

- Backend via Nginx: `http://localhost:8080`
- Frontend: `http://localhost:3000`
- MariaDB: `localhost:3306`
- Redis: `localhost:6379`

## Site Preview

Sites can be previewed before DNS is configured. In the admin panel, open a Site
and use the `Preview` action, or visit:

`http://localhost:3000/preview/{site-slug}`

For example, a site with slug `hartslagmeters_nl` can be previewed at:

`http://localhost:3000/preview/hartslagmeters_nl`

Run Laravel commands through the PHP service:

```bash
docker compose exec php php artisan migrate
docker compose exec php php artisan queue:work
```

If Docker is not running, the backend and frontend can still be run directly
from `backend/` and `frontend/` once dependencies are installed.
