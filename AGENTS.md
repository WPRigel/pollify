# AGENTS.md

Instructions for AI coding agents working on this repo. See CLAUDE.md for full architecture.

## Repo Overview

- Plugin: Pollify (poll-creator) — WordPress Gutenberg poll plugin
- Namespace: `wpRigel\Pollify\*` (PSR-4, autoloaded from `includes/`)
- Main file: `pollify.php`
- Block source: `src/poll/` → built to `build/poll/`
- REST API: `POST /wp-json/pollify/v1/vote/{client_id}`
- All vote validation: `includes/Model/Feedback.php` → `validate_vote_request()`

## Branch Naming

Always branch from `develop`:
- Bug fix: `fix/issue-{N}-{slug}`
- Feature: `feature/issue-{N}-{slug}`

## Commit Format

```
fix: short description (#N)
feat: short description (#N)
```

## WordPress Security (mandatory)

- **Nonces**: `wp_nonce_field()` on forms, `wp_verify_nonce()` before processing
- **Sanitize input**: `sanitize_text_field()`, `absint()`, `sanitize_email()`, etc.
- **Escape output**: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Capability checks**: `current_user_can()` before any privileged action
- **DB queries**: `$wpdb->prepare()` — never raw string interpolation

## Coding Rules

- PHP minimum 8.0, WordPress minimum 6.0
- Block-only approach — no shortcodes
- Pro extensibility via `pollify_map_feedback_classes` filter — do not break it
- Do not modify `vendor/` or `node_modules/`
- Keep changes focused — do not refactor unrelated code

## Checks (run before every PR)

```bash
npm run lint:js
npm run lint:css
composer run cs
composer run test
```

All must pass. Fix failures before creating PR.
