# Quick Start

Self-host the RustDesk API server (admin console + client API) in one command.

## 1. Run it

```bash
docker compose up -d
```

That's the whole setup. It uses **SQLite by default**, so there is no database to install.
All data persists in a Docker volume.

- **Admin console:** http://localhost:21114/admin
- **Client API base:** http://localhost:21114/api
- **Default login:** `admin` / `admin` — change it immediately (or set `ADMIN_PASS` before the
  first `up`, see below).

## 2. Point it at your RustDesk servers

Set these once (e.g. in a `.env` file next to `docker-compose.yml`, or in your shell), then
`docker compose up -d`:

```env
ADMIN_PASS=choose-a-strong-password
RUSTDESK_ID_SERVER=your.server:21116
RUSTDESK_RELAY_SERVER=your.server:21117
RUSTDESK_API_SERVER=http://your.server:21114
RUSTDESK_KEY=<contents of id_ed25519.pub>
PORT=21114
```

In the RustDesk client, set **API Server** to your `RUSTDESK_API_SERVER` and log in.

## 3. Email (optional)

For 2FA email codes, invitations, and alarm notifications, add SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=secret
MAIL_FROM_ADDRESS=no-reply@example.com
```

## 4. Bigger fleets: use MySQL/MariaDB

Uncomment the `db` service and the `DB_*` lines in `docker-compose.yml`, then
`docker compose up -d`. Everything else stays the same.

## Common commands

```bash
docker compose logs -f rustdesk-api      # view logs
docker compose exec rustdesk-api php artisan rustdesk:user alice secret --admin   # add an admin
docker compose down                      # stop (data is kept in the volume)
docker compose pull && docker compose up -d   # update
```

---
Developers: see [AGENT.md](AGENT.md) and [docs/modernization/](docs/modernization/). The dev
stack with hot tooling/tests is `docker/compose.dev.yml`.
