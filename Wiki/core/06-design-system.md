---
type: "core"
name: "Design System & UI Standards"
status: "stable"
description: "The single source of truth for the admin dashboard's visual design."
---

# 🎨 Design System

The admin console is a **modern dark dashboard** built with **Bootstrap 5 + jQuery + original
CSS** — no Vue, no third-party template assets. The implementation lives in
[`public/assets/css/theme-dark.css`](../../public/assets/css/theme-dark.css) (tokens +
components) and [`public/assets/js/app.js`](../../public/assets/js/app.js) (behavior). This
doc is the contract; the CSS is the source of truth. **Never hardcode colors or invent
classes — use the tokens and `rd-*` components below.**

## 🌈 Color tokens (CSS variables on `:root`)

| Token | Value | Purpose |
|-------|-------|---------|
| `--rd-bg` | `#070d19` | App background |
| `--rd-surface` | `#0c1427` | Cards, sidebar |
| `--rd-surface-2` | `#0f1a30` | Inputs, raised surfaces |
| `--rd-surface-3` | `#16223d` | Hover / active |
| `--rd-border` | `#1b2942` | Hairline borders |
| `--rd-border-strong` | `#243556` | Input borders |
| `--rd-text` | `#d3d8e3` | Body text |
| `--rd-text-muted` | `#7987a1` | Secondary text, labels |
| `--rd-text-bright` | `#f2f4f8` | Headings, values |
| `--rd-primary` | `#6571ff` | Brand / primary accent |
| `--rd-success` | `#05c27b` | Online, success, saved |
| `--rd-warning` | `#fbbc06` | Dirty/unsaved, caution |
| `--rd-danger` | `#ff3366` | Offline, errors, destructive |
| `--rd-info` | `#0090e7` | Informational |

Each status color has a `--rd-*-soft` translucent variant for icon/badge backgrounds.

## 📐 Geometry tokens
`--rd-radius` 12px (cards) · `--rd-radius-sm` 8px (inputs/buttons) · `--rd-sidebar-w` 248px ·
`--rd-navbar-h` 60px · `--rd-shadow` card elevation. Font: **Inter**, system fallback.

## 🧩 Components (use these `rd-*` classes)

| Component | Class | Notes |
|-----------|-------|-------|
| App shell | `.rd-app` > `.rd-sidebar` + `.rd-main` (`.rd-navbar` + `.rd-content`) | Defined in `layouts/admin.blade.php` |
| Sidebar nav item | `.rd-nav__item` (+`.active`), grouped by `.rd-nav__label` | Remix icons |
| Card | `.rd-card` > `.rd-card__header` / `.rd-card__body` | |
| Stat tile | `.rd-stat` + `.rd-stat__icon--{primary\|success\|warning\|danger}` | Dashboard KPIs |
| Table | `.rd-table` | Hover rows, uppercase muted headers |
| Badge | `.rd-badge--{online\|offline\|muted}` | Use the `.dot` for status dots |
| Form | `.rd-field` > `.rd-label` + `.rd-input`/`.rd-select`/`.rd-textarea` + `.rd-help` | |
| Button | `.rd-btn--{primary\|ghost\|danger}` | |
| Live-save button | `.rd-btn--save` with `data-state` | State machine below |
| Toast | created by `RD.toast(msg, type)` | `success\|error\|info` |

## ✨ Interactive states & behavior (jQuery, `window.RD`)

- **Live-save forms:** add `class="rd-liveform" data-url data-method` to a form with a
  `.rd-btn--save`. `app.js` drives `data-state`: `idle → dirty → saving → saved|error`. The
  button recolors per state (idle default, dirty=warning, saved=success, error=danger) and
  shows a spinner while saving. Submits JSON via `RD.api()` (bearer + CSRF auto-attached).
- **Toasts:** `RD.toast('message', 'success')`.
- **Confirm before destroy:** any element with `data-confirm="message"` prompts first
  (rule: destructive actions require confirmation).
- **Charts:** `RD.areaChart(selector, series, categories, color)` wraps ApexCharts in dark mode.
- **Sidebar:** collapses under 992px; `.rd-sidebar__toggle` opens it.

## ✅ Rules
1. Use tokens/variables — no literal hex in views.
2. Reuse `rd-*` components; extend the theme CSS rather than adding page-level `<style>`.
3. Remix Icon for iconography (`ri-*`).
4. Every list/table action that deletes data uses `data-confirm`.
5. Forms that save in place use the live-save pattern, not full-page reloads.
