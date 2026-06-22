# RustDesk‑API Modernization & Feature Program

> **Goal:** modernize `rustdesk-api`, make it intuitive, and close the gap with the
> features the RustDesk client already speaks and that **RustDesk Server Pro** offers —
> using only what an open‑source API server can legitimately provide.

This folder is the working knowledge base for that effort. It was produced from a deep
dive across three repositories plus the official Pro documentation:

| Source | What we mined it for |
|--------|----------------------|
| `rustdesk-api` (this repo) | Current routes, models, services, auth, config — the baseline. |
| `rustdesk` (the client, Rust) | The **HTTP contract** the client actually speaks — the real spec we must satisfy. |
| `rustdesk-server-pro` (install scripts) | How Pro is deployed (`hbbs`/`hbbr`, ports, license). The server itself is closed‑source. |
| `rustdesk-api-server-pro` (lantongxue, MIT) | A second open‑source API server — reference implementation; already solves several of our gaps (see doc 06). |
| [Pro docs](https://rustdesk.com/docs/en/self-host/rustdesk-server-pro/) | The authoritative Pro feature catalog. |

## Documents in this set

1. **[01-architecture-and-current-state.md](01-architecture-and-current-state.md)** —
   What `rustdesk-api` is today: architecture, every route, the data model, auth, config.
2. **[02-client-api-contract.md](02-client-api-contract.md)** —
   The endpoints/JSON the **client** expects. This is the implementation spec; build to it.
3. **[03-pro-feature-catalog.md](03-pro-feature-catalog.md)** —
   Every RustDesk Server Pro feature, with the keys/flows behind each.
4. **[04-gap-analysis.md](04-gap-analysis.md)** —
   Feature‑by‑feature: have / partial / missing, with effort & value ratings.
5. **[05-roadmap-and-implementation.md](05-roadmap-and-implementation.md)** —
   Prioritized roadmap with concrete implementation notes mapped to this repo's files.
6. **[06-reference-implementations.md](06-reference-implementations.md)** —
   Other open‑source RustDesk API servers (esp. `lantongxue/rustdesk-api-server-pro`) and
   exactly what to borrow — several of our gaps are already solved there.

## Executive summary

`rustdesk-api` is already a strong, multi‑DB (SQLite/MySQL/PostgreSQL), Gin/GORM
re‑implementation of the RustDesk API server. It covers **login (password / OAuth /
OIDC / LDAP), personal & shared address books, tags, groups, device groups, peer/device
listing, connection & file audit logs, login logs, guest web‑client sharing, server
commands to hbbs/hbbr, a web admin, and a web client.**

The biggest opportunities — features the **client already supports** but the server does
**not** implement — are:

| # | Capability | Status today | Why it matters |
|---|------------|--------------|----------------|
| 1 | **Strategy / Settings sync** (push client config via heartbeat) | ❌ absent | The single highest‑value Pro feature. Client is ready; server just never replies with `strategy`. |
| 2 | **2FA (TOTP) + email login verification** | ❌ absent | Client sends/expects `tfa_type`, `secret`, `email_check`, device whitelist. |
| 3 | **SMTP / email subsystem** | ❌ absent | Unlocks #2, invitations, password reset, and alarm notifications. |
| 4 | **Device deployment & approval** (`/api/devices/deploy`, `--deploy`, `ID_NOT_FOUND` gating) | ❌ absent | Controlled onboarding of new devices. |
| 5 | **Preset auto‑registration** (`OPTION_PRESET_*` in sysinfo) | ❌ ignored | Auto‑file devices into address book / strategy / device‑group on first contact. |
| 6 | **Session‑recording upload** (`/api/record`) | ❌ absent | Client streams recordings to this endpoint; nothing receives them. |
| 7 | **Granular access control & roles** (user‑group cross access, device‑group access, control roles, admin roles) | ⚠️ only `is_admin` + basic groups | Needed for teams/MSPs. |
| 8 | **Scoped API tokens + CLI** | ⚠️ session tokens only | Automation parity with Pro's `*.py` CLI. |
| 9 | **Force‑disconnect / live sessions** (heartbeat `conns`/`disconnect`) | ❌ absent | Heartbeat already carries the data. |
| 10 | **Multiple relays + geo routing** | ⚠️ single relay + manual cmd | Depends on hbbs; partial via server‑cmd today. |

See **[04-gap-analysis.md](04-gap-analysis.md)** for the full table and
**[05-roadmap-and-implementation.md](05-roadmap-and-implementation.md)** for sequencing.

## How to use this set

- Implementing a feature? Start in **02** (what the client expects) → **04** (the gap) →
  **05** (where it plugs into this repo).
- Planning? Read this page + **04** + **05**.
- The line references in these docs (e.g. `http/controller/api/index.go:41`) were accurate
  at the time of writing; verify before editing — the code moves.
