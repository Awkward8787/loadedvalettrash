# Contributing

This repository is the private business system for Loaded Valet Trash. Copyright 2026 Anthony Jaamal Jones / Loaded Valet Trash. See [LICENSE](LICENSE). Changes are reviewed by [@Awkward8787](https://github.com/Awkward8787).

## Bugs and ideas

Open a GitHub issue with what you expected, what happened, and the role you were signed in as (owner, manager, or porter). Leave out resident names, phone numbers, and anything from a gate or access note.

## Changes

1. Branch from `main`.
2. Keep PHP 8.2-compatible, with `declare(strict_types=1);` on new PHP files.
3. Run `php bin/lint.php` and `vendor/bin/phpunit` before you push. GitHub Actions runs the same checks.
4. Do not commit `.env`, `storage/demo-credentials.txt`, real rosters, gate codes, or production uploads.
5. Do not add sample reviews, fake customer logos, or invented statistics to the public site or to a migration.
6. Use a conventional commit subject: `feat:`, `fix:`, `docs:`, `test:`, or `chore:`.

`bin/seed.php` may create only the first owner from environment variables. Local-only sample records belong in `bin/demo-seed.php`, labeled DEMO, and that script must keep refusing to run unless `APP_ENV=local`.

Pull requests stay open until a reviewer merges them. Do not force-push `main`.
