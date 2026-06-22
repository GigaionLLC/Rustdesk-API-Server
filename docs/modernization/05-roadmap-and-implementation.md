# 05 · Roadmap & Implementation Notes

Sequenced plan with concrete hooks into this repo. Each item lists **where** it plugs in
(models/services/routes/handlers) so work can start without re‑discovery. File:line refs
were accurate at writing — re‑confirm before editing.

Conventions in this repo to follow:
- Model in `model/`, business logic in `service/<area>.go` (registered on
  `service.AllService`), HTTP in `http/controller/{api,admin}/`, request DTOs in
  `http/request/`, routes in `http/router/{api,admin}.go`, migrations/auto‑migrate in the
  app bootstrap (`service/app.go`/`config/gorm.go`). Add `RUSTDESK_API_*` env keys alongside
  any new `conf/config.yaml` entry.

---

## Phase 0 — Modernization groundwork (do alongside Phase 1)

- **Bump Go + deps:** `go.mod` Go 1.22 → current stable; update Gin, GORM, golang‑jwt,
  swag; `go mod tidy`; re‑run `go generate`.
- **Authz seam first.** Before building roles, introduce a thin permission check
  (`service/user.go` + a new `middleware/permission.go`) that today just maps `is_admin` →
  all‑perms. Everything in Phase 3 extends this without re‑plumbing handlers.
- **Token model split.** Keep `UserToken` as the session token; add a separate `ApiKey`
  model for Phase 3 #13 (don't overload one table).
- **Docs/DX:** English‑first quickstart; document every env var; a Bruno/Postman collection
  generated from the swagger specs.

---

## Phase 1 — Client‑ready wins (no client changes needed)

### 1.1 Strategy / Settings sync ★★★ (gap #1) — the flagship
The client is already asking for it (contract §1). Build:

**Models** (`model/strategy.go`):
```text
Strategy            { id, name, enabled, options(json: config_options),
                      extra(json), modified_at, created/updated }
StrategyAssignment  { id, strategy_id, target_type(device|user|device_group),
                      target_id, created }   // resolve with priority device>user>group
```
- Store `options` as the client `config_options` map (advanced‑settings keys). `modified_at`
  must bump on any content or assignment change.

**Service** (`service/strategy.go`): `ResolveForPeer(peer) -> (options, modified_at)`
implementing **Device > User > DeviceGroup** precedence; CRUD; assignment editors.

**Wire into heartbeat** (`http/controller/api/index.go:41`): after the online‑time update,
resolve the peer's strategy and, when the request's `modified_at` differs, return:
```json
{ "modified_at": <serverTs>, "strategy": { "config_options": {…}, "extra": {…} } }
```
Keep returning `{}` when nothing applies. Target ≤30s propagation (heartbeat cadence already
satisfies this).

**Admin routes** (`http/router/admin.go`): `/api/admin/strategy/{list,detail/:id,create,
update,delete}` + assignment endpoints, mirroring the existing group/tag controllers.

**Front‑end:** new "Strategies" section in `rustdesk-api-web` (separate repo) — a key/value
editor over advanced‑settings keys + a device/user/group assignment picker.

### 1.2 Preset auto‑registration ★★ (gap #6)
In `SysInfo` (`http/controller/api/peer.go:26`) parse the preset fields (contract §2). On
first contact, when present:
- `address_book_name`/`tag`/`alias`/`password`/`note` → upsert an `AddressBookCollection`
  + `AddressBook` entry for the resolved owner.
- `strategy_name` → create/lookup a `StrategyAssignment(device)`.
- `device_group_name` → assign `Peer.GroupId` / device‑group membership.
- `device_username`/`device_name`/`note` → persist on the `Peer`.
Add a request DTO in `http/request/api/peer.go` for these optional fields.

### 1.3 Live sessions + force‑disconnect ★★ (gap #9)
- Read `conns` in `Heartbeat`; persist a lightweight `ActiveSession{peer_id, conn_id, ip,
  started_at, last_seen}` (or cache‑backed via `lib/cache`).
- Admin endpoint to request a disconnect → set a pending flag; the next heartbeat response
  for that peer returns `{ "disconnect": [conn_id] }`.
- Admin "Active sessions" list + "Disconnect" action. Reuse `AuditConn` for history.

### 1.4 2FA — TOTP ★★★ (gap #2)
**Model** `model/user2fa.go`: `{ user_id, type(totp), secret(encrypted), enabled,
backup_codes(json, hashed), enabled_at, expire_at }` (default 180‑day expiry per Pro).
**Service** `service/twofa.go`: enroll (QR/secret), verify (RFC 6238, SHA1/6/30s — matches
`src/auth_2fa.rs`), consume backup code, reset.
**Login flow** (`http/controller/api/login.go` + `service/user.go`): when a user has TOTP,
return AuthBody with `tfa_type:"totp"` + `secret`; accept the second `/api/login` call with
`tfaCode`/`secret`. Populate `user.info` fields in AuthBody (contract §3b).
**Admin:** enable/disable/reset 2FA per user; optional enforce‑per‑group.

---

## Phase 2 — Foundational services

> **Reference implementation:** `lantongxue/rustdesk-api-server-pro` already has working
> Mail (templates + logs), 2FA/email‑verify, AuthToken rotation, and version‑capability code.
> Read [06-reference-implementations.md](06-reference-implementations.md) before starting
> 1.4, 2.1, and 2.2 — it maps the exact files to study (and the one anti‑pattern to avoid:
> don't set heartbeat `modified_at = now` every beat).

### 2.1 SMTP / email subsystem ★★★ (gap #4) — build before 2.2/2.3
- `config/smtp.go` + `conf/config.yaml` `smtp:` block (host, port, account, password/
  app‑password, from, tls, optional OAuth2/XOAUTH2 for M365) + env keys.
- `service/mail.go`: templated sends (verification code, invitation, password reset, alarm).
- Admin "Settings → SMTP" with a **Check/test** action (Pro parity).

### 2.2 Email login verification ★★ (gap #3) — depends on 2.1
`tfa_type:"email_check"` path in login: generate a code, email it, accept it on the second
`/api/login`. Store `email_verification` on the user and surface it in AuthBody `user.info`.

### 2.3 Device deployment & approval ★★ (gap #5)
- Config flag "require deployment for new devices". When on, `SysInfo` returns
  `ID_NOT_FOUND` for unknown devices (contract §2) instead of auto‑creating.
- `POST /api/devices/deploy` (and `/api/devices/cli`) accepting `{id,uuid,pk,…presets}` +
  a deployment **API key** (Phase 3 #13); results `OK|NOT_ENABLED|INVALID_INPUT|ID_TAKEN`.
- Admin "pending devices" approve/reject queue.

### 2.4 Session‑recording upload ★★ (gap #7)
- `POST /api/record` handler implementing the chunked protocol (contract §5): `type=new`
  opens a file (validate name, scope to owner), `part` appends at `offset`, `tail`
  finalizes, `remove` deletes. Store under a records dir or OSS (`config/oss.go` already
  exists). Reuse `AuditConn` linkage by peer/session for indexing.
- Admin browse/download/playback; retention/quota config. Respect the Control‑Role
  "Recording Session" toggle once Phase 3 lands.

### 2.5 Alarm logs + notifications ★★ (gap #8)
New audit category on the existing `/api/audit/*` ingestion path + `model/audit.go`. On
qualifying connection events, email via 2.1 when `email_alarm_notification` is set on the
user (field already present in the client AuthBody `user.info`).

---

## Phase 3 — Platform depth (teams / MSP)

### 3.1 Granular access control ★★★ (gap #10)
Implements Pro's cumulative model: device assignable to one user and/or one device group;
user‑group `can_access_to` / `can_be_accessed_from`; **access if user‑group OR device‑group
permits**; disabled user/device ⇒ deny. New models: `UserGroupAccess`,
`DeviceGroupAccess`; enforce in the peer/AB "accessible devices" queries
(`/api/peers`, `/api/device-group/accessible`, `service/peer.go`).

### 3.2 Admin Role (scoped console) ★★ (gap #12)
Role/permission matrix replacing the boolean: types Global / Individual / Group‑Scoped; a
user may hold several (union). Build on the Phase‑0 authz seam; enforce in
`middleware/admin_privilege.go` → a permission‑aware middleware. Permission catalog per
catalog §4.

### 3.3 Control Role (in‑session permissions) ★★ (gap #11)
12 toggles delivered to the controlled device **through the strategy/option push** (Phase
1.1). One role per user; merge with strategy options when resolving the heartbeat response.
Needs controlled client ≥ 1.4.5.

### 3.4 Scoped API tokens + CLI ★★ (gap #13)
`model/apiKey.go` `{ user_id, name, token, scopes(json), expire_at, last_used }`; scopes:
Device, Audit, User, Group, Strategy, AddressBook. Admin "Settings → Tokens". Ship a small
CLI (Go subcommands in `cmd/` or a thin Python set) mirroring Pro's `users.py`,
`devices.py`, `strategies.py`, `ab.py`, `audits.py`.

---

## Phase 4 — Heavy / hbbs‑bound (scope down or defer)

### 4.1 Multiple relays + geo ★ (gap #14)
API can **manage** the relay list (model + admin CRUD) and push it via the existing
`relay-servers` server‑cmd to hbbs. True **geo‑closest** selection lives inside `hbbs`
(MaxMind) — out of scope unless paired with a cooperating open‑source hbbs build.

### 4.2 Custom Client Generator ★★ (gap #15)
Full white‑label (branding, signing, multi‑platform build) is XL. **Realistic subset to
ship first:** generate the config string, the Windows filename‑encoding form, and
`--config`/`--assign` command snippets from console settings (catalog §13) — i.e. a
"deployment helper", not a binary builder.

---

## Suggested order of execution
1. **1.1 Strategy** + **0 authz seam** + **0 Go/deps bump** (parallelizable).
2. **1.2 Presets**, **1.3 Live sessions/disconnect** (small, build on 1.1).
3. **1.4 TOTP**, then **2.1 SMTP** → **2.2 email verification**, **2.5 alarms**.
4. **2.3 deployment/approval**, **2.4 recording**.
5. **3.1 access control** → **3.2 admin roles** → **3.3 control roles** → **3.4 API tokens/CLI**.
6. **4.x** only if a use case demands it.

## Definition of done (per feature)
- Server matches the client contract in [02](02-client-api-contract.md) (verified against a
  real client where client‑facing).
- Admin CRUD + `rustdesk-api-web` UI where user‑facing.
- `conf/config.yaml` + `RUSTDESK_API_*` env + README/docs updated.
- Swagger regenerated (`go generate`); service/handler tests added.
- Back‑compat: heartbeat/sysinfo still return safe defaults when the feature is unused.
