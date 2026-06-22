# 15 · Deep‑Scan Synthesis — what to build, what to skip

Synthesizes the two deep scans of the RustDesk client ([13-deepscan-connection.md](13-deepscan-connection.md),
[14-deepscan-management.md](14-deepscan-management.md)) into decisions. The scans were honest
about dead‑ends — several attractive ideas can't work against a **stock** client.

## ✅ Built (this round) — stock‑client wins the panel was missing
- **`POST /api/audit/alarm`** — the client already emits **8 security alarm types** (IP‑whitelist
  hits, brute‑force, IPv6‑prefix abuse, terminal backoff/concurrency). New endpoint maps `typ`
  → label, records an `Alarm`, and emails via the existing alarm pipeline. *Pro's "connection
  alarm notification."* (`AuditController::alarm`, `AuditController::ALARM_TYPES`)
- **Operator session notes** — the controlling side POSTs `{id, session_id, note}` to
  `/api/audit/conn`; we now attach it to the open session (added `audit_conns.note`).
- **Live Sessions + force‑disconnect** — admin page of currently‑open connections (derived from
  the audit stream), with a Disconnect action that queues the `conn_id` and is delivered to the
  controlled device on its next heartbeat (`SystemController` returns `disconnect: [...]`, which
  the client honors). Gated by new `sessions.view` / `sessions.edit` permissions.

## ⏭️ Recommended next — net‑new, stock‑client, low‑risk
| Feature | Effort | Note |
|---------|:------:|------|
| **Unverified‑status gating + SSO‑only login policy** | S | Honor `UserStatus::Unverified (-1)` + `third_auth_type` from the auth payload: block token issuance until verified; forbid local‑password login for SSO‑only accounts. |
| **Console‑operation audit log** | S–M | Panel‑internal: record admin mutations (who changed what). Compliance value; pairs with Admin Roles. |
| **RDP / direct‑IP preset completion** | S | Surface the address‑book `rdpPort`/`rdpUsername` for one‑click RDP (`port_forward.rs` reads these). |
| **Per‑tag address‑book colors** | S | The client round‑trips `tag_colors`; we store a `color` already — wire it through the AB sync. |
| **Connection‑2FA remote‑clear + Telegram channel** | M | Can't mint device‑local 2FA secrets, but can **clear** them via strategy and reuse the Telegram `sendMessage` shape as an alarm/notification transport. |

## ❌ Deferred / not viable against a stock client (don't re‑investigate)
- **Custom auto‑update channel** — the client's version check is hardcoded to
  `api.rustdesk.com/version/latest`; the download host comes from that response, never from our
  `api-server`. Needs a client patch.
- **Plugin distribution/registry** — real, but behind the non‑default `plugin_framework` Cargo
  feature with an empty upstream source list. Forward‑looking only.
- **Online‑peers feed (client‑driven)** — stock desktop gets presence over the rendezvous/hbbs
  TCP path, not HTTP. A panel‑side live feed is possible from heartbeat data, but it isn't
  client‑driven.
- **Guest / temporary share links** — web‑client‑only; no stock‑desktop redeem path, and the
  webclient was removed for DMCA. The `ShareRecord` model stays latent.
- **Wake‑on‑LAN** — LAN‑only in the client; the only server lever is the `same_server` AB hint.
- **Privacy mode / virtual display / whiteboard** — client‑local, no server config key.
- **Relay geo/multi‑relay selection** — lives in `hbbs`, not the API server.

See [11-client-feature-opportunities.md](11-client-feature-opportunities.md) for the earlier
opportunity pass these build on.
