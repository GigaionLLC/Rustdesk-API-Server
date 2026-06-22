# 09 · Port Status — Parity Checklist (Go ➜ PHP)

Tracks parity of the PHP rewrite against the legacy Go implementation and the client
contract. Status: ⬜ not started · 🟦 in progress · ✅ done+verified.

## Client API (`/api/*` — doc 02) — wire‑compatible, do not rename JSON keys/paths
| Endpoint | Go | PHP | Notes |
|----------|:--:|:---:|------|
| `GET /api/` · `/api/version` | ✅ | ⬜ | |
| `POST /api/login` (+2FA) | ✅(no 2FA) | ⬜ | add TOTP/email per doc 02 §3–4 |
| `GET /api/login-options` | ✅ | ⬜ | |
| `POST /api/oidc/auth` · `GET /api/oidc/auth-query` | ✅ | ⬜ | |
| `POST /api/logout` · `currentUser` · `user/info` | ✅ | ⬜ | |
| `POST /api/heartbeat` | ⚠️ stub | ✅ | strategy-push + change-detection verified |
| `POST /api/sysinfo` · `sysinfo_ver` | ⚠️ | ✅ | presets + ID_NOT_FOUND gating verified |
| `GET/POST /api/ab*` (personal+shared) | ✅ | ⬜ | |
| `POST /api/audit/conn` · `audit/file` | ✅ | ⬜ | |
| `POST /api/record` | ❌ | ⬜ | new (recording upload) |
| `POST /api/devices/deploy` · `devices/cli` | ❌ | ⬜ | new (deployment) |

## Admin console
| Area | Go | PHP | Notes |
|------|:--:|:---:|------|
| Auth/login + captcha | ✅ | 🟦 | login page renders (dark theme); auth logic next |
| Dashboard (stats/charts) | ⚠️ | 🟦 | shell + ApexCharts render w/ demo data; wire real stats |
| Users | ✅ | ⬜ | |
| Devices/Peers | ✅ | ⬜ | + live online/sessions |
| Address books (+collections/rules) | ✅ | ⬜ | |
| Tags · Groups · Device groups | ✅ | ⬜ | |
| OAuth providers | ✅ | ⬜ | |
| LDAP/AD | ✅ | ⬜ | |
| Audit logs (conn/file) | ✅ | ⬜ | + alarm, console-op |
| Login logs | ✅ | ⬜ | |
| Share records (guest) | ✅ | ⬜ | |
| Server commands (hbbs/hbbr) | ✅ | ⬜ | |
| Tokens / API keys | ⚠️ session | ⬜ | scoped keys |

## New / borrowed features (doc 04/06)
| Feature | PHP | Notes |
|---------|:---:|------|
| Mail/SMTP (templates + logs) | ⬜ | ref: lantongxue |
| 2FA TOTP + email verification | ⬜ | ref: lantongxue |
| Session management | ⬜ | auth-token rotation |
| Version-capability gating | ⬜ | ref: lantongxue |
| Strategy settings-push | ✅ | heartbeat config_options push, priority device>user>group, verified |
| Preset auto-registration | ✅ | sysinfo OPTION_PRESET_* → strategy/device-group/address-book, verified |
| Access control / roles | ⬜ | teams/MSP |

## Cross-cutting
| Item | Status |
|------|:------:|
| English: identifiers/comments | ⬜ |
| English: docs/README | ⬜ |
| Docker dev stack green | ✅ |
| Admin shell renders (login+dashboard, verified) | ✅ |
| Playwright E2E | ⬜ |
| Pint/PHPStan/ESLint gates | ⬜ |
