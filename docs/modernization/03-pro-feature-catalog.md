# 03 · RustDesk Server Pro — Feature Catalog

The authoritative list of what Pro offers, distilled from the official docs
(`https://rustdesk.com/docs/en/self-host/rustdesk-server-pro/`, sourced via the
`rustdesk/doc.rustdesk.com` markdown). For each feature: what it is, the keys/flows behind
it, and how the client participates. Use this as the "north star" of what's *possible*; see
[04-gap-analysis.md](04-gap-analysis.md) for what's worth building here.

> **Note on scope.** RustDesk Server Pro's `hbbs`/`hbbr` are closed‑source binaries. Some
> features (geo relay selection, the actual relay health‑checking) live in `hbbs`, not in
> the API layer. Where a feature needs `hbbs` cooperation it's flagged. The
> open‑source `rustdesk-server` (lejianwen fork) is the realistic backend pairing.

## Pro‑exclusive feature list (verbatim from "When to choose Pro")
Account/user system · Web console · API · OIDC, LDAP, 2FA · Address book · Log management
(connection, file transfer, alarm, …) · Device management · **Security Settings sync
(Strategy)** · Access control · Multiple relay servers (auto‑select closest) · Custom client
generator · WebSocket · Web client self‑host.

**Ports:** 21114 console/API · 21115 hbbs · 21116 TCP+UDP rendezvous · 21117 relay ·
21118 WS(ID) · 21119 WS(relay). **License** is tied to one `hbbs`; relays need none.

---

## 1. Web Console
Admin control plane on `:21114`. Default `admin` / `test1234` (change immediately).
Manages users/groups/roles, devices/device‑groups/assignment, strategies, access rules,
logs & active sessions, SMTP, relays, tokens, and custom client builds. Non‑admins get a
self‑service view of their own devices/logs/settings.
- **Automatic Configs / "Windows EXE":** generates a client config string; Windows can
  encode config into the `rustdesk.exe` filename (client ≥ 1.1.9). Exposes `Key`,
  `API Server`, copyable config.

## 2. Users & Groups
Local users (admin flag, email, note, password) assignable to a user group. Disabling a
user blocks access to their devices. User groups support **cross‑group access** (`Can access
to` / `Can be accessed from`, immediate effect). `Individual` plan = single user.

## 3. Access Control (who may connect to whom)
- A device may be assigned to one user, one device group, or both.
- Disabled user **or** disabled device ⇒ not accessible.
- **User‑group** cross access + **device‑group** access are **cumulative** (allow if either
  permits). Device‑group access needs client ≥ 1.3.8 / Server Pro ≥ 1.5.0.
- Controlling side is the **logged‑in user** (web/iOS clients have no device id).

## 4. Admin Role (delegated, scoped console management)
Grant partial console admin without full admin. A user may hold several roles (union of
perms). **Role types:** Global / Individual (own devices+logs) / Group‑Scoped (selected
user & device groups, optionally unassigned devices). Fine‑grained permission catalog over
Users, Devices, User Groups, Device Groups, Audit Logs (Edit = disconnect), Strategies,
Control Roles, Custom Clients. Edit⊃View; Edit⊅Assign; View⊅Members.

## 5. Control Role (in‑session permissions)
What a controller may do **after** connecting. One role per user; **control perms override
the controlled device's local settings**. Needs controlled device ≥ 1.4.5. Tri‑state per
perm (Use‑Client / Enable / Disable; Disable wins). **12 permissions:** Keyboard/Mouse,
Remote Printer, Clipboard, File Transfer, Audio, Camera, Terminal, TCP Tunnel, Remote
Restart, **Recording Session**, Block User Input, Remote Configuration Modification.
Built‑ins: *Not Logged* and *Default*.

## 6. Strategy (Security Settings sync) — the flagship
Bulk‑applies client settings to many devices. **Exactly one effective strategy per device;
priority Device > User > Device‑Group.** Per‑strategy: enable/disable, rename, duplicate,
delete, edit devices/users/device‑groups, edit content. **Propagation ≤30s** via the
heartbeat `modified_at`/`strategy` handshake (see contract §1). User‑changed options are
preserved unless the admin changes the strategy. *This is exactly what the client's
`config_options` push consumes.*

## 7. Two‑Factor Auth + Email verification
- **Email verification:** set email → enable "email login verification" → code at next login.
- **TOTP** (Authy / MS / Google Authenticator): QR or secret; **6 single‑use backup codes**.
  TOTP supersedes email verification. Changing account settings also requires 2FA.
- 2FA states not‑enabled / enabled / expired; **default expiry 180 days**.
- Admin reset: `rustdesk-utils reset_2fa_verification`; enforce via `users.py
  enable-2fa-enforce`.

## 8. OIDC / SSO
Delegate login to Google, Okta, Azure (Entra ID), GitHub, GitLab, Facebook, etc. (OIDC Core
1.0). Setup: create IdP app → Client ID/Secret/Issuer → Settings → OIDC → New provider.
Callback path `api/oidc/callback`. Azure issuer
`https://login.microsoftonline.com/<tenant>/v2.0` (enable ID tokens).

## 9. LDAP / AD
Authenticate against a directory; **user created on first successful login**. Fields: Host,
Port (389/636), Base DN, Scope (one/sub), Bind DN/Password, Filter, Username Attribute
(`uid`/`sAMAccountName`), StartTLS, NoTLSVerify. **Limits:** local→LDAP conversion
unsupported; **LDAP groups not yet supported**.

## 10. SMTP / Email
Powers invitations, login verification codes, and **connection alarm notifications**.
Settings → SMTP (host, port, account, password/app‑password, From; `Check` to test).
**Microsoft 365 XOAUTH2** supported (Server Pro ≥ 1.8.1): Azure app w/ `SMTP.SendAsApp`,
Exchange service principal, OAuth2 tenant/client/secret in console.

## 11. Relay servers + Geo
Add extra `hbbr` nodes; `hbbs` health‑checks every few seconds and routes to the
**geographically closest online relay**. Relays need no license. Setup: deploy `hbbr`, copy
the `id_ed25519[.pub]` key pair, open TCP 21117/21119, add hostnames in Settings → Relay.
Geo uses **MaxMind GeoLite2 City** `.mmdb` on `hbbs` (+ optional Geo overrides, "Reload
Geo"). *Relay selection logic is inside `hbbs`.*

## 12. License / Registration
License bound to one `hbbs` (Stripe purchase; self‑service portal for renew/upgrade/
migrate). Set/refresh in console. Proxy supported for license verification
(`proxy=http://…`/socks5).

## 13. Custom Client Generator (white‑label)
Build branded, code‑signed, pre‑configured clients (name, logo, icon, embedded
ID/Key/API/Relay + presets). Platforms: Windows x64, macOS arm64/x64, Linux, Android arm64.
Managed in console (gated by Admin Role "Custom Clients"). Related client‑config methods:
manual config, import/export server config, deploy scripts, `rustdesk.exe --config <string>`,
clipboard import.
- **CLI assignment** (`rustdesk.exe --assign --token …`): `--user_name`, `--strategy_name`,
  `--address_book_name|tag|alias|password|note`, `--device_group_name`, `--note`,
  `--device_username`, `--device_name`, `--deploy`. These map 1:1 to the `OPTION_PRESET_*`
  sysinfo keys (contract §2).

## 14. WebSocket + self‑hosted Web Client
WSS via nginx: `/ws/id`→`127.0.0.1:21118`, `/ws/relay`→`127.0.0.1:21119`. Self‑hosted web
client at `https://DOMAIN/web`. Clients opt in with `allow-websocket=Y`; a WS‑only client
can run with only 443 open.

## 15. Logs / Audit (four categories)
**Connection**, **File transfer**, **Alarm** (connection alarm emails, needs SMTP), and
**Console operation** audits. Filter by peer id, conn‑type (0 Remote Desktop / 1 File
Transfer / 2 Port Transfer / 3 View Camera / 4 Terminal), device id, operator, date.
"Audit Logs‑Edit" can **disconnect active connections**. "Only admin can access logs"
toggle.

## 16. API tokens + Python CLI
Settings → Tokens → Create with **scopes** (Device, Audit Log, User, Group, Strategy,
Address Book). Drive everything via CLI scripts, each taking `--url --token`:
`users.py`, `user_group.py`, `device_group.py`, `devices.py`, `ab.py`, `strategies.py`,
`audits.py`. (Includes 2FA enforcement, force‑logout, access‑control JSON, etc.)

## 17. Installation & ops (FAQ)
Install via Docker (`pro.yml`), `install.sh` (systemd `rustdesk-hbbs`/`hbbr` + optional
Nginx/Certbot), or convert‑from‑OSS. `rustdesk-utils`: `set_password`, `genkeypair`,
`validatekeypair`, `doctor`, `reset_email_verification`, `reset_2fa_verification`. Manual
HTTPS via Nginx + Certbot. Firewall via ufw/firewalld.

---

## Things people ask about that have **no** dedicated Pro mechanism
- **Whiteboard, Wake‑on‑LAN, IP whitelist** — client/strategy‑level, not separate server
  features. (RustDesk client *does* have WOL "wake a peer through another peer", but it is
  not an API‑server feature.)
- **Session recording** — surfaces as the Control‑Role "Recording Session" permission and
  the client's `/api/record` upload; no standalone console page.
- **Preset address‑book auto‑registration** — achieved via `--assign … --address_book_*` /
  `--deploy`, i.e. the sysinfo preset keys, not a console button.

## Source pages
Overview · Web Console · Access Control (permissions) · Admin Role · Control Role · Strategy
· 2FA · OIDC (+Azure) · LDAP · SMTP (+Microsoft‑365) · Relay · License · Custom Client
(client‑configuration) · FAQ · Installscript (Docker/Script/Windows). Raw markdown base:
`https://raw.githubusercontent.com/rustdesk/doc.rustdesk.com/master/content/self-host/rustdesk-server-pro/`.
