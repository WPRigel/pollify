<!-- Target branch: develop (use master only for hotfixes) -->

## Closes
Closes #

## Summary

<!-- What was changed and why -->

## Test in WP Playground

<!-- Replace BRANCH_NAME with your branch name -->
[▶ Test in WP Playground](https://playground.wordpress.net/#%7B%22steps%22%3A%5B%7B%22step%22%3A%22installPlugin%22%2C%22pluginData%22%3A%7B%22resource%22%3A%22url%22%2C%22url%22%3A%22https%3A%2F%2Fgithub.com%2FWPRigel%2Fpollify%2Farchive%2Frefs%2Fheads%2FBRANCH_NAME.zip%22%7D%2C%22options%22%3A%7B%22activate%22%3Atrue%7D%7D%5D%7D)

## How to Test

1.
2.
3.

## WordPress Security Checklist

- [ ] Nonces verified (`wp_verify_nonce`)
- [ ] Input sanitized (`sanitize_text_field`, `absint`, etc.)
- [ ] Output escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- [ ] Capability checks (`current_user_can`)
- [ ] DB queries use `$wpdb->prepare()`

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Refactor / code cleanup

## Risks / Limitations

## Checklist

- [ ] All CI checks pass (lint, PHPCS, PHPUnit)
- [ ] Tested via WP Playground or locally
- [ ] `CHANGELOG.md` / version updated if needed
