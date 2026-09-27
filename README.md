# Amenhoot

Quiz en vivo (estilo Kahoot) con Laravel + Inertia + Vue + Reverb.

## Local

```powershell
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

## Despliegue (Dokploy)

Repo: https://github.com/albergm98/amenhoot

1. En Dokploy: **Create Application** → Provider **GitHub** → `albergm98/amenhoot` → branch `main`.
2. **Build Type**: `Dockerfile` (ruta `Dockerfile`, context `.`).
3. Puerto publicado: **80**.
4. Variables de entorno mínimas:

```env
APP_NAME=Amenhoot
APP_ENV=production
APP_DEBUG=false
APP_URL=https://TU-DOMINIO
APP_KEY=base64:...   # php artisan key:generate --show
LOG_CHANNEL=stderr
DB_CONNECTION=sqlite
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=amenhoot
REVERB_APP_KEY=amenhoot-key
REVERB_APP_SECRET=amenhoot-secret
REVERB_HOST=TU-DOMINIO
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
VITE_APP_NAME=Amenhoot
VITE_REVERB_APP_KEY=amenhoot-key
```

5. Deploy. El entrypoint hace `migrate --seed` (usuario anfitrión `anfitrion@stellar.test`).
6. Traefik/Dokploy debe encaminar el dominio al contenedor (HTTP 80); los websockets van por `/app` en el mismo host.
