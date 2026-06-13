# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
npm run build          # Build poll block + admin JS
npm run start          # Watch mode
npm run build:custom   # Build admin JS only (assets/build)
npm run lint:js        # Lint JS
npm run lint:css       # Lint CSS
npm run zip            # Package plugin zip
composer run cs        # PHP CodeSniffer check
composer run cs:fix    # PHP CodeSniffer auto-fix
```

## Architecture

### Bootstrap
`pollify.php` → defines constants (`POLLIFY_PATH`, `POLLIFY_URL`, etc.) → instantiates `Plugin::run()` which wires `Assets`, `Menu` (admin only), `Apis`, `Blocks`.

### Database Tables
- `pollify_poll` — poll rows with `client_id` (UUID) as the public key
- `pollify_poll_options` — options per poll (`poll_id` FK)
- `pollify_vote` — votes with `client_id`, `option_ids`, `user_id`, `user_ip`

All CRUD goes through `FeedbackManager` (singleton). Cache group: `pollify_poll_cache`.

### Block
Single Gutenberg block registered at `src/poll/`. Edit logic in `edit.js`; frontend interaction in `view.js`. Built output lands in `build/poll/`. Admin JS (`src/global/js/admin.js`) builds to `assets/build/`.

### Pro Plugin Extensibility
`FeedbackFactory` resolves poll `type` → PHP class via `pollify_map_feedback_classes` filter. Pro plugin registers additional types (vote, kudos, nps, engagement) through this filter. Each pro type's model extends `Model\Feedback` and calls `parent::validate_vote_request()` — never re-implements it.

### REST API
- `POST /wp-json/pollify/v1/vote/{client_id}` — submit vote (nonce: `pollify-vote`)
- `pollify/v1/polls` — CRUD via `PollsController`

### Namespace
All PHP: `wpRigel\Pollify\*`, autoloaded via PSR-4 from `includes/`.

## Issue Workflow

### Slash Commands
- `/create-issue` — summarize current chat discussion → create structured GitHub issue → optionally start working
- `/work-on-issue N` — fetch issue #N → create branch → implement → run checks → create PR
- `/review-pr` — audit current branch for WordPress security issues before PR

### Working on an Issue
When asked to work on an issue (via `/work-on-issue N` or directly):
1. `gh issue view N --json number,title,body,labels` — read full context
2. Branch from `develop`: `fix/issue-N-slug` (bugs) or `feature/issue-N-slug` (features)
3. Implement all acceptance criteria — these are the definition of done
4. Run `npm run lint:js && npm run lint:css && composer run cs && composer run test` — fix all failures
5. Commit: `fix: description (#N)` or `feat: description (#N)`
6. Push branch, generate WP Playground URL, create PR via `gh pr create --reviewer sabbir1991`
7. Comment on issue with PR link

### WP Playground URL (repo is public)
```bash
BRANCH=$(git branch --show-current)
python3 -c "
import json, urllib.parse, sys
branch = sys.argv[1]
bp = {'steps': [{'step': 'installPlugin', 'pluginData': {'resource': 'url', 'url': 'https://github.com/WPRigel/pollify/archive/refs/heads/' + branch + '.zip'}, 'options': {'activate': True}}]}
print('https://playground.wordpress.net/#' + urllib.parse.quote(json.dumps(bp, separators=(',', ':'))))
" "$BRANCH"
```

### WordPress Security (non-negotiable)
- Nonces: `wp_nonce_field()` + `wp_verify_nonce()` on all forms/AJAX
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Escape all output: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- Capability checks: `current_user_can()` before privileged actions
- DB: `$wpdb->prepare()` — never raw string interpolation
