# 02 · The RustDesk Client HTTP API Contract

This is the **implementation spec**: the endpoints, JSON shapes, and behaviors the RustDesk
client (the `rustdesk` Rust repo) actually speaks to its API server. If we want a feature to
"just work" in stock clients, we build the server to match what is written here.

**Verification legend** — ✅ read directly from client source during this dive ·
🔎 reported from client source by research agent (cross‑checked against existing routes).

Client source anchors:
- `src/hbbs_http/sync.rs` — heartbeat, sysinfo, strategy ✅
- `src/hbbs_http/account.rs` — login/OIDC, auth body, 2FA fields ✅
- `src/hbbs_http/record_upload.rs` — session recording upload ✅
- `src/auth_2fa.rs` — TOTP/2FA ✅
- `libs/hbb_common/src/config.rs` (`keys` module) — `OPTION_PRESET_*` 🔎
- `src/ui_interface.rs`, `src/core_main.rs` — device deploy / CLI assign 🔎
- `src/common.rs` — `get_api_server`, `is_public`, `is_pro` ✅

---

## 1. Heartbeat & Strategy push ✅ — `POST /api/heartbeat`

Sent every ~3s base interval; full heartbeat at least every 15s, and immediately while
connections are active. `sync.rs:86‑274`.

**Request body:**
```json
{
  "id":   "<device id>",
  "uuid": "<base64 uuid>",
  "ver":  123456,                 // numeric version
  "conns": [12, 34],              // active connection ids (omitted if none)
  "modified_at": 1700000000       // client's last-known strategy timestamp
}
```

**Response — every field is optional and acted upon by the client:**
```json
{
  "sysinfo": true,                // force the client to re-POST /api/sysinfo
  "disconnect": [12],             // connection ids the client must drop
  "modified_at": 1700000123,      // server strategy timestamp; if newer, client stores it
  "strategy": {
    "config_options": { "<key>": "<value>", ... },   // pushed into client config
    "extra":          { "<key>": "<value>", ... }
  }
}
```

**Behavioral rules the server must honor:**
- The client tracks `strategy_timestamp` locally. It sends it as `modified_at`. When the
  server returns a **different** `modified_at`, the client adopts the new value and applies
  any `strategy.config_options`. This is the change‑detection handshake — a strategy edit
  must bump the timestamp so clients pull within one heartbeat (Pro propagates in ≤30s).
- `config_options` are merged into client settings. Empty value + empty default ⇒ option is
  removed (falls back to built‑in default); otherwise the (possibly empty) value is set.
  See `handle_config_options` `sync.rs:287`.
- Returning `disconnect: [ids]` force‑drops those sessions on the client.
- Returning `"sysinfo": true` makes the client clear its sysinfo hash and re‑upload.
- **Today's server returns `{}`** — so none of this works. This is gap #1.

---

## 2. System info & device registration ✅ — `POST /api/sysinfo`, `POST /api/sysinfo_ver`

`sync.rs:125‑230`. The client uploads a sysinfo document and skips re‑upload while a hash
matches; `/api/sysinfo_ver` lets it confirm the server still has the same version.

**`/api/sysinfo` body** (core device fields + optional presets):
```json
{
  "id": "...", "uuid": "...", "version": "...",
  "cpu": "...", "hostname": "...", "memory": "...", "os": "...", "username": "...",

  // OPTION_PRESET_* — present only when baked into the client (custom client / --assign):
  "address_book_name": "...",
  "address_book_tag": "...",
  "address_book_alias": "...",
  "address_book_password": "...",
  "address_book_note": "...",
  "username": "...",            // preset login username (OPTION_PRESET_USERNAME)
  "strategy_name": "...",       // assign device to a named strategy
  "device_group_name": "...",   // assign device to a device group
  "device_username": "...",     // override displayed username
  "device_name": "...",         // override hostname
  "note": "..."
}
```

**Responses the client understands:**
- `"SYSINFO_UPDATED"` — accepted/stored.
- `"ID_NOT_FOUND"` — device unknown ⇒ client retries on next heartbeat. **This is the hook
  for "require deployment for new devices"**: a Pro server returns this until the device is
  approved/deployed.

**`/api/sysinfo_ver`** returns an opaque version string used to short‑circuit uploads.

> Today's server stores the core fields but **ignores all preset keys** and always answers
> `SYSINFO_UPDATED` (`http/controller/api/peer.go:26`). Gaps #4 and #5.

The set of `OPTION_PRESET_*` keys (from `hbb_common` `keys`): preset address‑book
name/tag/alias/password/note, preset username, preset strategy name, preset device‑group
name, preset device username, preset device name, preset note. These are produced by the
**Custom Client Generator** and by `rustdesk.exe --assign` (see catalog §13).

---

## 3. Account login & OIDC ✅ — `POST /api/login`, `POST /api/oidc/auth`, `GET /api/oidc/auth-query`

### 3a. OIDC device flow (`account.rs:160‑320`)
1. `POST /api/oidc/auth`
   ```json
   { "op": "<provider>", "id": "...", "uuid": "...",
     "deviceInfo": { "os": "...", "type": "client|browser", "name": "..." } }
   ```
   → `{ "code": "...", "url": "<provider auth url>" }`
2. Client opens `url`, then polls `GET /api/oidc/auth-query?code=&id=&uuid=` (1s, ≤3min).
   While pending the server returns an error containing `"No authed oidc is found"`.
   On success it returns an **AuthBody** (below). All routes already exist in this repo.

### 3b. AuthBody / UserPayload — the shape every login must return
```json
{
  "access_token": "...",
  "type": "access_token",
  "tfa_type": "",            // "" | "email_check" | "totp"  ← 2FA negotiation
  "secret": "",              // 2FA secret/material when tfa_type is set
  "user": {
    "name": "...",
    "display_name": "...",
    "avatar": "...",
    "email": "...",
    "note": "...",
    "status": 1,             // 1 Normal | 0 Disabled | -1 Unverified
    "is_admin": false,
    "third_auth_type": "",   // provider if logged in via SSO
    "info": {
      "email_verification": false,
      "email_alarm_notification": false,
      "login_device_whitelist": [
        { "data": "<ip|uuid>", "info": {"os":"","type":"","name":""}, "exp": 0 }
      ]
    }
  }
}
```

> The current server returns a compatible subset but never populates `tfa_type`, `secret`,
> or `info.*`. Those are the 2FA / email‑verification / device‑whitelist hooks (gap #2).

---

## 4. Two‑factor authentication ✅ — `src/auth_2fa.rs` + login flow

- **TOTP:** SHA1, 6 digits, 30s step. Client stores an encrypted `TOTPInfo {name, secret,
  digits, created_at}`. Server signals TOTP by returning `tfa_type:"totp"` + `secret`.
- **Email verification:** server returns `tfa_type:"email_check"`; client then re‑calls
  `/api/login` with a `verificationCode`.
- Login request for the 2nd factor adds: `type:"email_code"`, `verificationCode`,
  `tfaCode` (for TOTP), `secret`. 🔎
- Pro detail (for parity): TOTP enrollment yields 6 single‑use backup codes; 2FA secret
  default expiry 180 days; enabling TOTP supersedes email verification.

---

## 5. Session recording upload ✅ — `POST /api/record`

`record_upload.rs`. Chunked upload driven by query params; body is raw bytes.

| Phase | Query | Body |
|-------|-------|------|
| start | `?type=new&file=<name>` | empty |
| chunk | `?type=part&file=<name>&offset=<n>&length=<m>` | bytes `[offset, offset+length)` |
| finish| `?type=tail&file=<name>&offset=0&length=<headerLen>` | first ≤1024 header bytes |
| abort | `?type=remove&file=<name>` | empty |

Response: `{}` on success, `{ "error": "<msg>" }` on failure (any error aborts the upload).
There is **no such route today** (gap #6). Note the in‑session **"Recording Session"**
permission is a separate Control‑Role concept (catalog §5).

---

## 6. Address book ✅ (routes) / 🔎 (legacy shapes)

The implemented client routes in this repo are the canonical target: `GET/POST /api/ab`
plus the personal set (`/api/ab/personal`, `/api/ab/settings`, `/api/ab/shared/profiles`,
`/api/ab/peers`, `/api/ab/tags/:guid`, `/api/ab/peer/*`, `/api/ab/tag/*`). Older Sciter
clients used `POST /api/ab/get` + `POST /api/ab` with a single `{ "data": "<json string>" }`
blob (tags + peers); newer Flutter clients use the granular per‑collection routes. Keep both
working; the granular set is the future. The notable peer fields the client round‑trips:
`id, username, hostname, platform, alias, tags, forceAlwaysRelay, rdpPort, rdpUsername,
loginName, password/hash`.

---

## 7. Device deployment & CLI assignment 🔎 — `POST /api/devices/deploy`, `POST /api/devices/cli`

Reported from `src/ui_interface.rs` and `src/core_main.rs`; these back `rustdesk.exe
--deploy` / `--assign`.

- `POST /api/devices/deploy` — `{ id, uuid, pk }` with a deployment **token**; result one of
  `OK | NOT_ENABLED | INVALID_INPUT | ID_TAKEN`. Used when "Require deployment for new
  devices" is on.
- `POST /api/devices/cli` — `{ id?, uuid, pk, address_book_name?, address_book_tag?,
  address_book_alias?, address_book_password?, address_book_note?, device_group_name?, note?,
  device_username?, device_name? }` with a token. CLI registration + presets.

Neither exists today. Confirm exact paths/fields against the client version you target
before implementing; treat these as the design reference for gap #4.

---

## 8. Audit ingestion ✅ — `POST /api/audit/conn`, `POST /api/audit/file`

Already implemented. hbbs/clients post connection (`new`/`close`) and file‑transfer events.
For parity with Pro's **alarm** logs and **console‑operation** logs we add new categories
on top of this ingestion path (see catalog §15).

---

## 9. Server resolution & "Pro" detection ✅ — `src/common.rs`, `sync.rs:308`

- `get_api_server`: explicit `api-server` → else derive from `custom-rendezvous-server`
  (port −2) → else `https://admin.rustdesk.com`.
- `is_public(url)`: true for `*.rustdesk.com`. The client **skips** heartbeat/sysinfo when
  the API is public, so self‑host is required for any of this.
- `is_pro()`: the client flips an internal `PRO` flag to **true** when `/api/sysinfo`
  returns `SYSINFO_UPDATED` and `/api/sysinfo_ver` responds. Implication: behaving like the
  sysinfo/heartbeat contract above is literally how a server advertises "Pro‑class"
  behavior to the client UI.

---

## 10. Endpoint summary (client → server)

| Endpoint | Method | Auth | Implemented here? |
|----------|--------|------|-------------------|
| `/api/login-options` | GET | no | ✅ |
| `/api/login` | POST | no | ✅ (no 2FA) |
| `/api/oidc/auth` | POST | no | ✅ |
| `/api/oidc/auth-query` | GET | no | ✅ |
| `/api/logout` | POST | yes | ✅ |
| `/api/currentUser` · `/api/user/info` | POST/GET | yes | ✅ (no 2FA/info fields) |
| `/api/heartbeat` | POST | no | ⚠️ stub (no strategy/disconnect) |
| `/api/sysinfo` | POST | no | ⚠️ no presets / no gating |
| `/api/sysinfo_ver` | POST | no | ✅ |
| `/api/ab*` | GET/POST/PUT/DELETE | yes | ✅ |
| `/api/audit/conn` · `/api/audit/file` | POST | no | ✅ |
| `/api/record` | POST | (token) | ❌ |
| `/api/devices/deploy` · `/api/devices/cli` | POST | token | ❌ |
