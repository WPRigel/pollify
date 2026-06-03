# AGENTS.md

Instructions for AI coding agents (Claude Code, Codex, etc.) working on this repo.
See CLAUDE.md for full architecture detail.

## Repo Overview

- Plugin: Pollify (poll-creator) — WordPress Gutenberg poll plugin
- Namespace: `wpRigel\Pollify\*` (PSR-4, autoloaded from `includes/`)
- Main file: `pollify.php`
- Block source: `src/poll/` → built to `build/poll/`
- Admin JS: `src/global/js/admin.js` → built to `assets/build/`
- REST API: `POST /wp-json/pollify/v1/vote/{client_id}`, CRUD at `pollify/v1/polls`
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

## WordPress Security (mandatory — no exceptions)

- **Nonces**: `wp_nonce_field()` on forms, `wp_verify_nonce()` before processing
- **Sanitize input**: `sanitize_text_field()`, `absint()`, `sanitize_email()`, etc.
- **Escape output**: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- **Capability checks**: `current_user_can()` before any privileged action
- **DB queries**: Always use `$wpdb->prepare()` — never raw string interpolation

## Coding Rules

- PHP minimum: 8.0. WordPress minimum: 6.0.
- No shortcodes — block-only approach
- Pro plugin extensibility via `pollify_map_feedback_classes` filter — do not break it
- Do not modify `vendor/` or `node_modules/`
- Keep changes focused — do not refactor unrelated code

## DO NOT (in CI/automated runs)

- Do NOT run `npm run build`, `npm run lint:*`, or `composer run *` — workflow handles checks
- Do NOT run `gh pr create` or push the branch — workflow handles that
- Do NOT run `npm install` or `composer install` — already done before agent runs

## After Implementation

Stage and commit all changed files:
```
git add -A
git commit -m "fix: description (#N)"
```

Then stop. Output the text: `IMPLEMENTATION_COMPLETE`
The workflow will run checks and create the PR automatically.
