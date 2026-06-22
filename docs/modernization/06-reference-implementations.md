# 06 · Reference Implementations — Other Open‑Source API Servers

There is more than one open‑source RustDesk API server. Studying them saves us work: some
have **already implemented features we list as gaps**. This doc compares them and says
exactly what to borrow.

| Project | Path analyzed | Stack | Frontend | Client target | Posture |
|---------|---------------|-------|----------|---------------|---------|
| **lejianwen/rustdesk-api** (this repo) | `D:\git\rustdesk-api` | Go · **Gin** · **GORM** · SQLite/MySQL/PostgreSQL | `rustdesk-api-web` (Vue) | ~1.4.2 | **Broad** feature set, mature auth |
| **lantongxue/rustdesk-api-server-pro** | `D:\git\rustdesk-api-server-pro` | Go · **Iris MVC** · **XORM** · SQLite/MySQL | **soybean‑admin** (Vue3) | **1.4.6** | **Narrow but modern**, well‑tested |

> Despite the `-pro` name, lantongxue's project is MIT‑licensed and open source (not the
> commercial Pro). Its README notes a planned rewrite (issue #30) and "simplest possible
> code" philosophy.

## TL;DR strategic read

lantongxue's server is a cleaner, newer‑client, better‑tested codebase that has **already
built four things on our gap list** — SMTP/mail, 2FA + email codes, session management, and
version‑capability gating — but it **lacks the breadth** this repo already has (OIDC, LDAP,
groups, device groups, address‑book sharing rules, server commands, guest sharing). So:

- **Borrow its designs** for Mail, 2FA, AuthToken, and version‑capability (below).
- **Keep this repo's breadth** (OIDC/LDAP/groups/sharing/server‑cmd) — don't regress it.
- Neither implements the real **Strategy settings‑push** or **preset auto‑registration** —
  those remain greenfield for us (gaps #1 and #6 in [04](04-gap-analysis.md)).

---

## What lantongxue's repo already solves (study these)

### A. SMTP / mail subsystem — gap #4 ✅ (reference quality)
`backend/app/service/mail.go`, `backend/app/model/mail_template.go`,
`backend/app/model/mail_logs.go`, admin controllers `mail_template.go` / `mail_logs.go`.
- DB‑stored **mail templates** (`type`, `subject`, `contents`) with `{$var}` substitution.
- Every send is recorded in **`mail_logs`** (from/to/uuid/status/logs) — built‑in deliver‑
  ability auditing.
- Uses `github.com/xhit/go-simple-mail/v2`; encryption `none | ssl/tls | starttls`;
  singleton service; config under `smtpConfig:` in `server.yaml`.
- **Take‑away for us:** adopt this template+log shape for our `service/mail.go` (roadmap
  2.1). It directly satisfies the Pro "Settings → SMTP + Check" UX and the alarm/verify
  needs.

### B. 2FA (TOTP) + email login verification — gaps #2 & #3 ✅
`backend/app/service/user.go` (`Login`, `LoginVerifyByEmailCode`, `LoginVerifyBy2FACode`),
`backend/app/model/verify_code.go`, `backend/app/controller/api/login.go`.
- Per‑user `LoginVerify` mode: `email_check` or `tfa_check`; TOTP secret stored on the user
  (`TwoFactorAuthSecret`), validated with `github.com/pquerna/otp/totp`.
- `/api/login` is the single entry: first call (account+password) returns
  `{type:"email_check", tfa_type:"email_check"|"tfa_check", secret:"<uuid>"}`; the client
  re‑calls with `type:"email_code"` + `verificationCode` (and `tfaCode` for TOTP).
- A **`verify_code`** row tracks each challenge: `type (mail/2fa)`, `uuid` (the `secret`),
  `code`, `rustdesk_id`, `expired` (10 min for email), `status (unused/used/expired)`.
- **Matches the client contract exactly** (see [02](02-client-api-contract.md) §3–4). This
  is the cleanest worked example of the 2FA handshake; mirror it in our login service.

### C. AuthToken design — modernization (token model split)
`backend/app/model/auth_token.go`, `GetLoginToken` in `service/user.go`.
- Token = `HmacSha256(rustdesk_id_uuid_userId_time, signKey)`; bound to `rustdesk_id`,
  `uuid`, device os/type/name; 90‑day expiry; **issuing a new token expires prior tokens for
  the same device** (clean session rotation).
- **Take‑away:** good template for our session‑token hygiene and the eventual session
  manager; informs the token/API‑key split in [05](05-roadmap-and-implementation.md) Phase 0.

### D. Version‑capability gating — new pattern worth adopting
`backend/helper/version/capability.go`, used in `controller/api/system.go`.
- Parses the client's reported version and computes capabilities, e.g.
  `translate_mode = version >= 1.4.6`, returned inside the heartbeat `strategy` object.
- **Take‑away:** as we track newer clients, a small `helper/version` that gates features by
  client version keeps behavior correct across the install base. We should add an equivalent.
- ⚠️ **Caveat / lesson:** their heartbeat sets `modified_at = now` on **every** beat and puts
  capability flags under `strategy`. That's fine for static capability flags, but it is
  **not** the Pro settings‑sync semantics — the client's strategy consumer expects
  `strategy.config_options`/`extra` and only re‑applies when `modified_at` *changes*. When we
  build real Strategy (gap #1), bump `modified_at` only on actual change and put pushed
  settings under `config_options`. Don't copy the always‑now timestamp.

### E. Session management & device tracking — gap #9 (partial) ✅
`backend/app/controller/admin/sessions.go`, `model/device.go`, `controller/api/system.go`.
- `Device{ rustdesk_id, uuid, cpu, hostname, os, version, is_online, conns, … }`; heartbeat
  upserts it and tracks `is_online` + `conns`; admin **Sessions** view lists auth tokens.
- **`/api/sysinfo` returns `ID_NOT_FOUND`** for unknown devices (heartbeat is what creates
  them) — a working example of the deployment‑gating hook we want for gap #5.

### F. Engineering quality to emulate
- **Go tests + a compatibility matrix** (`backend/test/compatibility`,
  `test/api/*_test.go`) and **Playwright E2E** (`soybean-admin/tests/e2e`) covering
  login/devices/users/audit. CI wires optional full‑stack E2E.
- **soybean‑admin** Vue3 dashboard (statistics panel, i18n) — more modern than the current
  `rustdesk-api-web`; worth evaluating for our admin UX refresh.
- **Single‑binary** deploy (plan to embed the built `dist/` via `embed.FS`); Cobra CLI with
  `sync` (migrate), `user add … --admin`, `start`, and a `rustdesk` command group.

---

## What it does NOT have (where this repo stays ahead)

| Capability | lejianwen (this repo) | lantongxue |
|------------|:---------------------:|:----------:|
| OIDC / OAuth providers | ✅ GitHub/Google/OIDC/Linux.do/WebAuth | ❌ `login-options` stubbed (returns empty) |
| LDAP / AD | ✅ | ❌ |
| User groups | ✅ | ❌ |
| Device groups | ✅ (CRUD) | ❌ (flat devices) |
| Address‑book collections + sharing rules | ✅ read/rw/full | ⚠️ basic AB + tags + peers |
| Server commands to hbbs/hbbr | ✅ | ❌ |
| Guest web‑client sharing / share records | ✅ | ❌ |
| Multi‑DB incl. PostgreSQL | ✅ | ⚠️ SQLite/MySQL |
| Real Strategy settings‑push | ❌ | ❌ (capability flags only) |
| Preset auto‑registration (`OPTION_PRESET_*`) | ❌ | ❌ |

---

## Net recommendation

Treat lantongxue's repo as the **canonical reference** for implementing our **Mail (2.1)**,
**2FA/email‑verify (1.4/2.2)**, **AuthToken hygiene (Phase 0)**, and **version‑capability
gating** work — its code is small, current to client 1.4.6, and tested. Keep building the
**breadth + Strategy + presets + access‑control** features that neither open‑source server
has, which is where a "better RustDesk API" is genuinely differentiated. Cross‑references:
[04-gap-analysis.md](04-gap-analysis.md) for priorities,
[05-roadmap-and-implementation.md](05-roadmap-and-implementation.md) for where each plugs in.
