# Pollify #

**Contributors:** [sabbir1991](https://profiles.wordpress.org/sabbir1991), [wprigel](https://profiles.wordpress.org/wprigel)
**Author:** [Sabbir Ahmed](https://profiles.wordpress.org/sabbir1991)
**Tags:** poll, survey, vote, gutenberg, block
**Requires at least:** 6.2
**Tested up to:** 6.9.1
**Requires PHP:** 8.0
**Stable tag:** 1.0.12
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Create and manage polls natively inside the WordPress block editor.

## Description ##

Pollify lets you embed polls anywhere in WordPress using a native Gutenberg block. Votes are stored in a custom database table and results are accessible from a dedicated admin panel.

### Features ###

- **Gutenberg block** — drag and drop the Poll block into any post or page
- **Vote tracking** — stores votes with optional IP, user agent, and geolocation data
- **Duplicate prevention** — configurable per-IP and per-user vote limiting
- **Login restriction** — optionally require login to vote, with customisable message and redirect URL
- **Anonymous voting** — separate control over what voter data is stored vs. who can vote
- **Poll lifecycle** — publish, draft (closed), scheduled close by date, or trash
- **Results display** — show results after voting or after the poll closes
- **Admin list table** — paginated, sortable list of all polls with status counts and bulk actions
- **Vote log** — per-poll vote history with voter IP, location flag, and timestamp
- **REST API** — full CRUD endpoints under `/wp-json/pollify/v1/`
- **Extensible** — filter hooks for custom poll types, SQL clauses, and response data

### Settings per poll ###

| Setting | Description |
|---------|-------------|
| `requireLogin` | Restrict voting to logged-in users |
| `requireLoginAction` | `hide` (default) hides the block; `popup` shows block and prompts login on interact |
| `requireLoginMessage` | Custom message shown to guests |
| `anonymousVoting` | When enabled, voter IP and identity are not stored |
| `allowedPerComputerResponse` | Enable duplicate-vote prevention |
| `closePollState` | What to show after poll closes: `hide-poll`, `show-result`, or `show-message` |
| `submitButtonLabel` | Custom label for the vote button |

## Installation ##

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Open any post or page in the block editor and search for the **Poll** block
4. Configure the poll options and publish

## REST API ##

All endpoints are under `/wp-json/pollify/v1/` and require `edit_posts` capability.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/polls` | List polls (supports `status`, `type`, `search`, `per_page`, `page`) |
| POST | `/polls` | Create a poll |
| GET | `/polls/{client_id}` | Get a single poll |
| PUT/PATCH | `/polls/{client_id}` | Update a poll |
| DELETE | `/polls/{client_id}` | Trash a poll |
| DELETE | `/polls/{client_id}/permanent-delete` | Permanently delete a poll |
| GET | `/polls/{client_id}/stats` | Get poll vote stats |
| POST | `/vote/{client_id}` | Submit a vote (public, nonce: `pollify-vote`) |

## Frequently Asked Questions ##

**How do I prevent duplicate votes?**
Enable the **Allowed Per Computer Response** option on the poll block. For logged-in users, votes are tracked by user ID. For guests, votes are tracked by IP address (requires the `pollify_trust_proxy_headers` filter if behind a load balancer — see below).

**My site is behind Cloudflare or a load balancer. How do I enable real IP detection?**
By default Pollify uses `REMOTE_ADDR` for IP-based dedup. To trust proxy headers (`X-Forwarded-For`, `Client-IP`), add this to your theme's `functions.php`:

```php
add_filter( 'pollify_trust_proxy_headers', '__return_true' );
```

Only enable this if your server sits behind a trusted proxy you control.

**Can I add custom poll types?**
Yes. Hook into `pollify_map_feedback_classes` to register a new type that maps to your own model class extending `\wpRigel\Pollify\Model\Feedback`.

## Development ##

### Requirements ###

- Node.js 18+
- Composer 2+
- PHP 8.0+
- WordPress 6.2+ local environment

### Setup ###

```bash
composer install
npm install
```

### Build ###

```bash
npm run build          # Build poll block + admin JS (production)
npm run start          # Watch mode for development
npm run build:custom   # Build admin JS only (assets/build/)
npm run zip            # Package distributable plugin zip
```

### Linting ###

```bash
npm run lint:js        # ESLint (JS)
npm run lint:css       # Stylelint (CSS/SCSS)
composer run cs        # PHP CodeSniffer (WordPress coding standards)
composer run cs:fix    # PHP CodeSniffer auto-fix
```

### Testing ###

```bash
./vendor/bin/phpunit                          # Run all unit tests
./vendor/bin/phpunit --filter TestClassName   # Run a single test class
./vendor/bin/phpunit --testdox                # Verbose output
```

Tests live in `tests/Unit/` and use PHPUnit + Brain\Monkey for WordPress function stubs. No database or WordPress installation is required — the bootstrap at `tests/bootstrap.php` defines all necessary WP stubs.

### Branch strategy ###

This project follows [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/):

- `develop` — integration branch; all feature work targets here
- `feature/*` — branch off `develop`, merge back via PR
- `release/x.x.x` — branch off `develop` for release prep (version bump, changelog); merges into both `master` and `develop`
- `hotfix/*` — branch off `master` for emergency fixes; merges into both `master` and `develop`
- `master` — production-ready code only; every merge is tagged

### Pull requests ###

Target `develop` for all non-hotfix work. The PR template at `.github/PULL_REQUEST_TEMPLATE.md` lists the required checklist (tests, linting, manual testing).

## Contributing ##

Contributions, bug reports, and feature requests are welcome.

- **Bug reports / feature requests** — open an issue on GitHub
- **Pull requests** — fork the repo, branch off `develop`, submit a PR against `develop`
- **Author:** [Sabbir Ahmed](https://profiles.wordpress.org/sabbir1991) — WordPress developer and maintainer of Pollify

## Changelog ##

= 1.0.12 =
* Added login restriction feature

= 1.0.11 =
* Fixed anonymous voting issue
* Added trash support for all poll types

= 1.0.10 =
* Fixed number format localisation issue
* Fixed icon rendering issue

= 1.0.9 =
* Added single vote deletion to admin votes table
* Enhanced poll deletion and block filtering logic

= 1.0.8 =
* Added anonymous vote support

= 1.0.0 =
* Initial release

## Upgrade Notice ##

= 1.0.12 =
Added login restriction feature. No database changes required.
