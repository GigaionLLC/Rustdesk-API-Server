# 08 · Build Log (PHP rewrite)

Chronological record of what was built and **verified**. Newest at top.

## 2026-06-18 — Admin shell renders (first visible milestone)
- Built the dark dashboard frontend: `public/assets/css/theme-dark.css`,
  `public/assets/js/app.js` (jQuery live-save state machine, toasts, AJAX+bearer, ApexCharts
  wrapper), Blade `layouts/admin` + `admin/partials/{sidebar,navbar}` + `admin/dashboard` +
  `admin/login`; routes in `routes/web.php`.
- **Verified in Docker:** `php artisan serve` → `/admin/login` and `/admin` both HTTP 200;
  all compiled Blade views lint-clean (`php -l`); Playwright screenshots captured to
  `docs/modernization/_screens/`.
- Caught + fixed a Blade gotcha: inline array literals inside `@json()` break the directive
  arg parser — defaults must be computed in an `@php` block.
- Client research agents delivered: `10-client-config-keys.md` (161 option keys) and
  `11-client-feature-opportunities.md` (12 ranked opportunities).

## 2026-06-18 — Foundation
- Decisions locked: PHP backend rewrite · HTML/jQuery/CSS (Blade, no Vue) modern dark
  dashboard · full English (incl. identifiers) · Docker build/test with Playwright + linters.
- Added `docker/Dockerfile.toolchain` (PHP 8.5 + Composer + Node 20 + Playwright + linters'
  system deps) and `docker/compose.toolchain.yml` (app + MariaDB + Mailpit).
- Building the toolchain image (`rustdesk-api-php-toolchain`).
- Master plan: [07-rewrite-plan-php.md](07-rewrite-plan-php.md).

> Convention: each entry notes the command(s) used to verify (build/lint/test/E2E) so a
> reader can reproduce. "Verified" means it ran green in the toolchain image.
