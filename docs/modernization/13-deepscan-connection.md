# 13 · Deep Scan — Connection / In-Session / Security surface

A read-only deep dive of the RustDesk client (`D:\git\rustdesk`, Rust) focused on the
**connection / in-session / security** area, hunting for **net-new, server-implementable**
features our self-hosted panel could add — especially Pro-only capabilities or things the
forked OSS panel never exposed.

**Scope discipline.** This doc deliberately does **not** re-report anything already covered in
[02-client-api-contract.md](02-client-api-contract.md), [03-pro-feature-catalog.md](03-pro-feature-catalog.md),
[10-client-config-keys.md](10-client-config-keys.md), [11-client-feature-opportunities.md](11-client-feature-opportunities.md),
or [12-access-control-design.md](12-access-control-design.md). In particular the following are
**already documented and intentionally omitted here**: heartbeat Strategy push + the 161 config
keys (doc 10), the 12 `enable-*` in-session permissions and `access-mode`/`approve-mode`/
`verification-method`/`whitelist`/auto-disconnect *as strategy keys* (docs 10, 12), sysinfo
presets, account-login 2FA (TOTP/email), OIDC, LDAP, address books (incl. `rdpPort`/`rdpUsername`
fields), audit conn/file ingestion, session-recording upload, device deploy/CLI assign, access
control, admin/control roles, alarms-as-a-concept, `is_pro()`, Wake-on-LAN.

What's **new in this doc**: the things those docs *mention in passing or not at all* — the
**connection-2FA management surface** (TOTP secret + Telegram bot the host stores locally), the
**full alarm taxonomy** the client actually emits (8 types, not the vague "alarm" of doc 03/11),
the **operator session-note** posted to `/api/audit/conn`, the **brute-force / IPv6-prefix
lockout telemetry**, the **terminal OS-login** audit/concurrency model, and an honest verdict on
privacy-mode / virtual-display / whiteboard / relay selection (mostly client-local or hbbs-owned,
with the few real server levers called out).

Effort legend: **S** ≈ 1–2 days · **M** ≈ 3–7 days · **L** ≈ 1–3 weeks (server side only).
All file:line citations are to the client repo `D:\git\rustdesk` (read-only).

---

## Ranked findings

| # | Finding | Server lever | Local-only? | Effort | Value |
|---|---------|--------------|-------------|--------|-------|
| 1 | **Full alarm taxonomy on `/api/audit/alarm`** — 8 distinct `AlarmAuditType`s the client already POSTs (ip-whitelist, >30 attempts, 6/min, IPv6-prefix abuse, terminal-OS-login backoff/concurrency). Today's panel ingests conn/file but not alarm. | Add `/api/audit/alarm` ingest + typed alarm log + email/Telegram notify | **Server-influenceable** (client emits; server stores/notifies) | **M** | ★★★★★ |
| 2 | **Operator session-note → `/api/audit/conn`** — the *controlling* side POSTs `{id, session_id, note}` so an operator can annotate a live session. Distinct from the host "new"/"close" conn audit. | Accept `note` on the conn-audit row; show/edit in session log UI | **Server-influenceable** | **S** | ★★★★☆ |
| 3 | **Connection-2FA management surface** (`auth_2fa.rs`) — TOTP secret (`2fa`) and Telegram bot (`bot` = `{token, chat_id}`) are stored as ordinary local config keys. Strategy/`config_options` writes any key verbatim, so the panel *can* provision/rotate/clear them and reuse the Telegram channel. | Strategy push of `2fa`/`bot`; "force connection-2FA" policy; reuse Telegram bot for panel alerts | **Partially server-influenceable** (push works; secret material is sensitive) | **M** | ★★★☆☆ |
| 4 | **Trusted-device (2FA-bypass) registry** — host keeps a local `TrustedDevice{hwid,id,name,platform,time}` list with expiry, gated by `enable-trusted-devices`. Pure local today; an admin would want visibility/revocation. | Surface + remotely clear via heartbeat `disconnect`-style command or a new key; toggle `enable-trusted-devices` via strategy | **Mostly local** (toggle is server-influenceable; the list is not exposed) | **M** | ★★★☆☆ |
| 5 | **Terminal OS-login hardening telemetry** — terminal sessions can auth against a real OS account; the client emits `TerminalOsLoginBackoff`/`TerminalOsLoginConcurrency` alarms and gates brute force. New conn-type `4 = Terminal`. | Ingest as part of #1; expose terminal-login failures in the log UI; conn-type filter | **Server-influenceable** (telemetry only) | **S** (rides on #1) | ★★★☆☆ |
| 6 | **Direct-IP / RDP port-forward presets** (`port_forward.rs`) — RDP creds come from `rdp_username`/`rdp_password` **env vars**; address-book already carries `rdpPort`/`rdpUsername`. Panel can complete the one-click-RDP story. | Store/serve `rdpPort`/`rdpUsername` (doc 07/11) + a `direct-access-port` strategy preset | **Server-influenceable** (via AB + strategy, both already planned) | **S** | ★★★☆☆ |
| 7 | **Privacy-mode implementation selector** (`privacy-mode-impl-key`) — host picks mag / exclude-from-capture / virtual-display backend. It's a real config key, so strategy-pushable, but it's a `KEYS_LOCAL/DISPLAY`-style local pref. | Optionally expose in strategy editor as an advanced key | **Mostly local** | **S** | ★★☆☆☆ |
| 8 | **Virtual-display / whiteboard / relay-selection** | — | **Client-local or hbbs-owned; no realistic API-server lever** | — | ★☆☆☆☆ |

> The two clear wins are **#1 (alarm ingestion + notify)** and **#2 (operator note)** — both are
> data the stock client *already sends* to endpoints we either stub or don't have, requiring
> **zero client changes**. Everything below is honestly graded, including the dead ends.

---

## 1. Full alarm taxonomy on `/api/audit/alarm`  ★★★★★ (M) — server-influenceable

**What the client does.** On security-relevant connection events the host POSTs to
`{api-server}/api/audit/alarm` (derived by `get_audit_server(..., "alarm")`,
`src/common.rs:1119-1125` → `format!("{}/api/audit/{}", url, typ)`). The body is
`{ id, uuid, typ: <i8>, info: "<json string>" }` (`src/server/connection.rs:1421-1438`,
`post_alarm_audit`). The `typ` is one of **eight** enum values
(`src/server/connection.rs:5493-5503`):

| `typ` | `AlarmAuditType` | Trigger | `info` payload | Citation |
|------|------------------|---------|----------------|----------|
| 0 | `IpWhitelist` | incoming IP not in `whitelist` | `{ ip }` | `connection.rs:1311-1314` |
| 1 | `ExceedThirtyAttempts` | >30 wrong passwords from an IP | `{ ip, id, name }` | `connection.rs:3941-3948` |
| 2 | `SixAttemptsWithinOneMinute` | >6 wrong in the current minute | `{ ip, id, name }` | `connection.rs:3952-3959` |
| 6 | `ExceedIPv6PrefixAttempts` | abuse across a /64,/56,/48 IPv6 netblock | `{ ip, id, name }` | `connection.rs:3859-3866` |
| 7 | `TerminalOsLoginBackoff` | OS-credential terminal login throttled by policy | `{ ip, id, name }` | `connection.rs:3904-3911` |
| 8 | `TerminalOsLoginConcurrency` | concurrent OS-credential terminal login blocked | `{ ip, id, name }` | `connection.rs:3671-3678` |

(Values 3/4/5 are commented-out/reserved — `connection.rs:5497-5499`.) The endpoint is silently
skipped when the API server is empty or public (`common.rs:1121-1123`), so this only fires for
self-hosters — i.e. *us*.

**What the server would implement.** A `POST /api/audit/alarm` handler that mirrors the existing
`/api/audit/conn` ingest: store `{device_id, uuid, typ, info, created_at}` in an `alarm_logs`
table, decode `typ` into the human label above, and (this is the Pro selling point) **fire a
notification** — email (we already have SMTP plumbing in the roadmap) and/or the same Telegram
bot from finding #3. Pro's "connection alarm notification" (doc 03 §10/§15) *is exactly this
endpoint*. Add a log page with a `typ` filter and a per-device/IP rollup.

**Why it's net-new here.** Doc 02 §8 lists conn/file audit as implemented and notes alarm as a
"new category … on top of this ingestion path", and doc 03/11 mention "alarm" abstractly — but
**no doc enumerates the eight concrete `typ`s or the exact `{id,uuid,typ,info}` body**, and the
current panel has no `/api/audit/alarm` route at all. This finding is the implementation spec.

**Effort/value.** M (one endpoint + model + a notify hook + a log view). ★★★★★ — it's a flagship
Pro security feature, the client already emits it, and it's the natural home for brute-force /
IP-block visibility that admins expect.

---

## 2. Operator session-note → `/api/audit/conn`  ★★★★☆ (S) — server-influenceable

**What the client does.** The **controlling/viewer** side (not the host) can attach a free-text
note to the active session. `SessionInterface::send_note` (`src/ui_session_interface.rs:589-597`)
POSTs to `{api-server}/api/audit/conn` a body of
`{ "id": <controlled-id>, "session_id": <u64>, "note": <string> }`
(`src/ui_session_interface.rs:2061-2064`). It is exposed to the UI as `fn send_note(String)`
(`src/ui/remote.rs:502`) and gated on the operator being logged in (`access_token` present,
`:579-581`). The host-side conn audit, by contrast, posts `{ip, action:"new"|"close", ...,
session_id}` (`connection.rs:1337-1340`, `1357-1368`).

**What the server would implement.** Extend the existing `/api/audit/conn` handler to recognize a
body carrying `note` (no `action`) and **upsert it onto the matching connection-audit row** keyed
by `(id, session_id)`. Surface/allow-edit the note in the connection-log UI. This gives "operator
annotated this session" — a real Pro console affordance — essentially for free, since the row
already exists from the `new`/`close` events.

**Why it's net-new.** Doc 02 §8 treats `/api/audit/conn` as a done, two-shape (`new`/`close`)
ingest. The **third shape — the operator note keyed by `session_id`** — isn't documented anywhere
and the current handler will drop it.

**Effort/value.** S (one branch in an existing handler + a UI field). ★★★★☆ — cheap, visible,
and it's a differentiator over both OSS forks.

---

## 3. Connection-2FA management surface (TOTP + Telegram bot)  ★★★☆☆ (M) — partial

**What the client does.** Connection-level 2FA (the controlled machine challenging the
*controller* for a code — distinct from account-login 2FA in doc 02 §4) is entirely in
`src/auth_2fa.rs` and stored as **two ordinary local config keys**:

- **`2fa`** — an encrypted `TOTPInfo{name, secret, digits:6, created_at}`; SHA1 / 6 digits / 30s,
  issuer `"RustDesk Connection"` (`auth_2fa.rs:17-40, 54-73, 91-109`). Created via `generate2fa`
  / `verify2fa` and persisted with `set_option("2fa", …)` (`auth_2fa.rs:96-99`).
- **`bot`** — an encrypted `TelegramBot{token, chat_id}` (`auth_2fa.rs:114-155`). `chat_id` is
  resolved by calling `https://api.telegram.org/bot<token>/getUpdates`
  (`auth_2fa.rs:165-204`); codes are delivered to the controller via
  `https://api.telegram.org/bot<token>/sendMessage` (`auth_2fa.rs:158-163`).

At connection time, when `require_2fa` is set and the session isn't recent/trusted, the host
generates a TOTP code and, **if a Telegram bot is configured, pushes the code to Telegram**
before returning `REQUIRE_2FA` to the controller (`connection.rs:1506-1537`). The challenge is
satisfied by an `Auth2fa{code, hwid}` message (`connection.rs:2612-2638`), and a successful
non-empty `hwid` is added to the trusted-device list (finding #4).

**What the server could implement (and the honest limits).**
- **Provision / rotate / clear via Strategy.** `handle_config_options` writes *any* key verbatim
  into the user config layer (doc 10 §1), and there is **no allow-list**. So the panel *can* push
  `2fa`/`bot` values — e.g. "enforce connection-2FA on this device group" by pushing a
  pre-generated `2fa` blob, or clear it by pushing `""`. **Caveat:** the stored values are
  AES-encrypted with a *device-local* key (`encrypt_vec_or_original(... "00" ...)`,
  `auth_2fa.rs:55,124`), so the server cannot mint a ciphertext the client will decrypt. Realistic
  server levers are therefore: (a) a **policy flag** the device reads to *require* connection-2FA
  (would need a new client key — not present today, so this is "influence the toggle, not the
  secret"), and (b) **clearing** `2fa`/`bot` remotely (pushing empty is safe).
- **Reuse the Telegram channel for panel alerts.** The Telegram request shapes
  (`getUpdates`/`sendMessage`) are a ready-made building block — the panel can offer its **own**
  bot for login alarms / alarm-log #1 notifications, storing `{token (encrypted), chat_id}`
  exactly like the client does. (Doc 11 §4b already flagged the *building-block* reuse for
  account-2FA; what's new here is using it as the **delivery channel for the alarm log in #1**.)

**Effort/value.** M. ★★★☆☆ — the secret-provisioning angle is limited by device-local encryption,
but the **Telegram-as-notification-channel** reuse and the **remote-clear** capability are
genuinely useful, and pair naturally with #1.

---

## 4. Trusted-device (2FA-bypass) registry  ★★★☆☆ (M) — mostly local

**What the client does.** After a successful connection-2FA, if the controller sent a non-empty
`hwid` and `enable-trusted-devices` is on, the host stores a
`TrustedDevice{hwid, time, id, name, platform}` (`connection.rs:2631-2638`) in local config
(`Config::get_trusted_devices`/`add_trusted_device`). On subsequent connections a matching,
non-expired `hwid`+id+name+platform **bypasses 2FA** (`connection.rs:2321-2333`). The host
advertises `enable_trusted_devices` in the login response (`connection.rs:2015`), and the toggle
is `enable-trusted-devices` (`config.rs:2948`, default ON).

**What the server could implement.**
- **Server-influenceable:** the **toggle** `enable-trusted-devices` is an ordinary strategy key
  (already in doc 10 §2b) — a "disallow trusted-device bypass org-wide" policy is one push.
- **Mostly local / the gap:** the **list itself** is never uploaded, so the panel has no
  inventory of which controllers have a standing 2FA bypass on which device, and no way to revoke
  a single trusted device remotely. There's no client endpoint for this today. A genuine
  Pro-grade feature would be "show/revoke trusted devices per host", but it would require a new
  client capability (upload the list + accept a clear command) — i.e. **not** achievable against
  the stock client beyond the all-or-nothing toggle.

**Effort/value.** M to do it properly (toggle now; inventory/revoke needs client cooperation).
★★★☆☆ — the toggle is trivial; the visibility/revoke is desirable but blocked by the client.

---

## 5. Terminal OS-login hardening telemetry  ★★★☆☆ (S, rides on #1) — server-influenceable

**What the client does.** The **terminal** in-session feature (conn-type `4 = Terminal`,
`connection.rs:1551-1552`) can authenticate against a real **OS account** rather than the
RustDesk password (`os_login.username`/`password`, the `TerminalAuthorizationMode::OsLogin`
branch, `connection.rs:3640-3690`). To resist brute force it applies a **credential policy**
(`evaluate_os_credential_policy`, `connection.rs:3885-3915`) and a **concurrency gate**
(`try_acquire_os_credential_login_gate`, `connection.rs:3660-3681`), emitting
`TerminalOsLoginBackoff` (typ 7) and `TerminalOsLoginConcurrency` (typ 8) alarms (see #1). There's
also a `terminal-persistent` option (`connection.rs:2425-2426, 5170-5172`) for keeping a terminal
session alive across reconnects.

**What the server would implement.** Nothing new beyond #1 — these two alarm types are part of the
same `/api/audit/alarm` stream. The value is **surfacing them distinctly** in the log UI (so an
admin sees "someone is brute-forcing the OS terminal login on host X") and adding the conn-type
`4 = Terminal` to the connection-log type filter (Pro lists exactly this set in doc 03 §15:
0 Remote / 1 File / 2 Port / 3 View Camera / 4 Terminal). The `enable-terminal` /
`terminal-persistent` toggles are already strategy keys (doc 10).

**Effort/value.** S once #1 exists. ★★★☆☆ — completes the security-log story for the (newer)
terminal feature, which neither OSS fork surfaces.

---

## 6. Direct-IP / RDP port-forward presets  ★★★☆☆ (S) — server-influenceable

**What the client does.** `src/port_forward.rs` listens on `127.0.0.1:<port>` and, for RDP
(`port == 0`), launches `mstsc /v:localhost:<port>` after seeding credentials into Windows via
`cmdkey`. **The RDP username/password are read from the `rdp_username` / `rdp_password`
environment variables** (`port_forward.rs:23-24`), and the listener is fed `remote_host` /
`remote_port` from the session config (`port_forward.rs:54-68`). The direct-access listener port
comes from `direct-access-port` (`rendezvous_mediator.rs:840-848`) and direct-IP mode from
`direct-server` (`rendezvous_mediator.rs:850-858`).

**What the server would implement.** The address book already round-trips `rdpPort` / `rdpUsername`
(doc 02 §6, doc 11 §7) — wiring those through to a **one-click RDP** entry (and letting the panel
seed the env vars / config so the operator doesn't re-type creds) closes the loop. `direct-server`
+ `direct-access-port` are ordinary strategy keys (doc 10 §2c), so a **"direct-IP access preset"**
(enable + fixed port + whitelist) is a packaged strategy. No new transport.

**Why it's net-new here.** Docs 02/11 note the AB *fields* exist; this finding pins the **client
mechanism** (`rdp_username`/`rdp_password` env vars + `mstsc`/`cmdkey`) that those fields must feed,
and the `direct-server`+`direct-access-port`+`whitelist` bundle as a deployable preset.

**Effort/value.** S. ★★★☆☆ — small, and "RDP through RustDesk with saved creds" is a real
convenience win once the AB stores the fields.

---

## 7. Privacy-mode implementation selector  ★★☆☆☆ (S) — mostly local

**What the client does.** Privacy mode (black-screen) has multiple Windows backends — magnifier,
exclude-from-capture, and virtual-display (`src/privacy_mode.rs:39-41`). The active one is chosen
by the local config key **`privacy-mode-impl-key`** (`privacy_mode.rs:123, 200`). The *permission*
to use privacy mode at all is the already-documented `enable-privacy-mode` strategy key (doc 10).

**What the server would implement.** Because `handle_config_options` writes any key, the panel
*could* expose `privacy-mode-impl-key` in an "advanced" section of the strategy editor (e.g. force
the virtual-display backend on a fleet). But it's a backend/UX preference, not a security control,
and it's effectively a local setting — low priority.

**Effort/value.** S. ★★☆☆☆ — niche; include only if building an exhaustive strategy editor.

---

## 8. Virtual-display, whiteboard, relay selection — honest dead ends  ★☆☆☆☆

- **Virtual display** (`src/virtual_display_manager.rs`): selects the IDD backend
  (`rustdesk_idd` vs `amyuni_idd`) and plugs monitors in/out on peer request. It reports
  `idd_impl` / virtual-display counts into the peer-info **sysinfo-ish** map
  (`virtual_display_manager.rs:49-63`) — purely informational, decided at build/runtime. **No
  config key, no server lever.** Client-local.
- **Whiteboard** (`src/whiteboard/`): a peer-to-peer in-session overlay; `mod.rs` exposes no
  `Config::get_option` / option key and is gated only by the in-session protocol, not by any
  host-security setting. Doc 03 already classifies whiteboard as "no dedicated Pro mechanism".
  **No server lever.** Client-local.
- **Relay selection / always-use-relay** (`src/rendezvous_mediator.rs:828-837`): the relay is
  chosen as local `relay-server` option → else the value **provided by the rendezvous server
  (hbbs)** → else derived from the ID-server host. The geo/multi-relay "pick the closest" logic
  lives entirely in **hbbs**, not the API server (doc 03 §11 already states this). The only API-
  side levers are the existing `relay-server` / `custom-rendezvous-server` / `force-always-relay`
  strategy keys (doc 10 §2c). **No new API-server lever.**

These are documented here so they aren't re-investigated: each is either client-local or an
hbbs concern, with no realistic self-hosted-panel feature beyond the strategy keys we already
catalog.

---

## Recommended to build (shortlist)

1. **`POST /api/audit/alarm` ingestion + typed alarm log + notify (#1).** ★★★★★ / M. The single
   highest-value item in this scan: the client already POSTs eight concrete alarm types
   (`{id,uuid,typ,info}`) to an endpoint we don't have. Store + label + email/Telegram-notify =
   Pro's "connection alarm" feature with zero client work. Pairs with the Telegram channel from #3.
2. **Operator session-note on `/api/audit/conn` (#2).** ★★★★☆ / S. One branch in the existing
   conn-audit handler to capture `{id, session_id, note}` and show it in the log UI. Tiny, visible.
3. **Terminal-login alarms + conn-type-4 filter in the log UI (#5).** ★★★☆☆ / S, rides on #1.
   Surfaces OS-credential brute-force on the terminal feature; just two more `typ`s plus a filter.
4. **Telegram-as-notification-channel + remote clear of connection-2FA (#3).** ★★★☆☆ / M. Reuse
   the client's exact `sendMessage` shape to deliver #1's alarms; offer `2fa`/`bot` clear via
   strategy. (Don't promise secret *provisioning* — device-local encryption blocks it.)
5. **Direct-IP / RDP preset packaging (#6) + `enable-trusted-devices` org policy (#4 toggle).**
   ★★★☆☆ / S each. Both ride entirely on the already-planned Strategy + address-book work; just
   expose them as packaged presets.

Everything in #7–#8 is client-local or hbbs-owned and **not** recommended as panel work.
