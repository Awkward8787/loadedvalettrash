<p align="center">
  <img src="docs/banner.png" alt="Loaded Valet Trash" width="100%">
</p>

# Loaded Valet Trash

**Website and owner-only operations backend for a doorstep valet-trash service serving apartment communities in Valdosta and Lowndes County, Georgia.**

[![CI](https://github.com/Awkward8787/loadedvalettrash/actions/workflows/ci.yml/badge.svg)](https://github.com/Awkward8787/loadedvalettrash/actions/workflows/ci.yml)
[![License: Proprietary](https://img.shields.io/badge/license-proprietary-black)](LICENSE)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL / MariaDB](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)
![PHPUnit](https://img.shields.io/badge/tests-PHPUnit%2011-85e006)
![Hostinger](https://img.shields.io/badge/hosting-Hostinger-673DE6?logo=hostinger&logoColor=white)

Loaded Valet Trash is owned by Anthony Jaamal Jones (Dondon).

Residents who want the service opt in for $35 a month. The leasing office bills that amount as a rent add-on and keeps an agreed share (the starting point is 10%). Porters pick up bagged trash outside each door Monday through Friday evenings and take it to the property compactor or dumpster. Smaller sites can take a community-wide price instead. That price is quoted per property and is not published on the site.

## Features

Public site, written for property managers:

- Home, how it works, schedule and bag rules, who we serve, pricing, FAQ, about, privacy, and terms
- Revenue-share calculator (units × opt-in % × monthly price × property share)
- Proposal and walkthrough request, resident missed-pickup report, and review submission
- Reviews appear only after an owner or manager approves them. A new install has none
- Phone, email, address, and insurance text render only when an owner has saved them

Admin, built to be usable on a phone in the field:

- Owner, manager, and porter roles. A porter sees only their own route
- Hashed passwords, authenticator-app two-factor authentication, recovery codes, login rate limits, CSRF checks, and idle session timeout
- Lead pipeline, properties, roster, team, nightly routes, porter check-off, issues, monthly invoices, profit, review moderation, proposal inbox, settings, and an audit log
- Gate and access notes are visible to owners, managers, and the porter assigned to that property. They are left out of CSV exports and weekly reports

## Stack

PHP 8.3 and MySQL (or MariaDB), with server-rendered pages. There is no Node process to keep running.

Hostinger shared hosting, premium hosting, and the business hosting plans all run PHP and MySQL. Node.js on Hostinger is a separate runtime and is not available on every plan. This app is uploaded as PHP files, pointed at `public/` as the document root, and connected to a MySQL database created in hPanel. Composer is used on a laptop, or over SSH if the plan has it. The browser downloads no framework from a CDN. Fonts are self-hosted (Archivo for text, Anton for headings). The public mark is `public/assets/brand/loaded-valet-trash-logo.png`, cut from the official badge. Lime `#85e006` is sampled from that crown. Small text stays black on white or black on lime. Lime type is used on black.

Money is stored in integer cents. Dates and times use `America/New_York`.

## Local setup

Requirements: PHP 8.2 or newer with `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, and `json`; Composer; MySQL 8 or MariaDB 10.11.

```bash
composer install
cp .env.example .env
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Put that key in `APP_KEY`. Create a database and user, then set `DB_*`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` (at least 12 characters, with a letter and a number).

```bash
php bin/migrate.php
php bin/seed.php
php -S 127.0.0.1:8080 -t public public/router.php
```

Open http://127.0.0.1:8080. The first sign-in asks you to turn on an authenticator app before any other admin page will open. Save the recovery codes. They are shown once.

`php bin/seed.php` creates that one owner account and nothing else. It does not insert properties, residents, or reviews.

### Optional local demo data

```bash
# APP_ENV in .env must be local
php bin/demo-seed.php
```

This refuses to run unless `APP_ENV=local`. It inserts records whose names start with `DEMO`, writes passwords and authenticator secrets to `storage/demo-credentials.txt` (gitignored), and does not create reviews. Do not run it on Hostinger.

## Tests

```bash
php bin/lint.php
vendor/bin/phpunit
```

PHPUnit uses `.env.testing` and the `loaded_valet_test` database. Create that database and grant the user in `.env.testing`, or export `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` before `vendor/bin/phpunit`. Those four variables are not forced by `phpunit.xml`, so a CI service account wins when it is already set. `APP_KEY` in the test config is a disposable 32-byte value for the test database only.

The suite covers sign-in, two-factor recovery codes, CSRF, rate limiting, session timeout, role checks, billing math, route time estimates, holiday skipping, review moderation, the proposal and missed-pickup forms, and the public pages.

## Environment variables

See `.env.example`. Never commit `.env`.

| Variable | Purpose |
| --- | --- |
| `APP_ENV` | `local` on a laptop, `production` on Hostinger. Demo data loads only when this is `local`. |
| `APP_DEBUG` | `true` shows error detail locally. `false` in production. |
| `APP_URL` | Public origin, including `https://`. Used for links, the sitemap, and the secure session cookie. |
| `APP_TIMEZONE` | `America/New_York`. |
| `APP_KEY` | 32-byte key, stored as `base64:...`. Encrypts authenticator secrets. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL connection. |
| `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Used once by `bin/seed.php` to create the first owner. |
| `SESSION_NAME` | Session cookie name. Default `lvt_session`. |

After seeding, contact details and insurance wording are edited in **Admin → Settings**. They are not environment variables, and the public site hides any of them that are still empty.

## Deploying to Hostinger

The short version:

1. Create a MySQL database and user in hPanel. Note the host (often `localhost`), database name, user, and password.
2. Point the domain’s document root at the `public/` directory. If the plan only allows `public_html`, upload this project inside the account and set the domain folder to `public_html/public`, or upload the project files into `public_html` and keep the root `.htaccess`, which sends requests into `public/` and blocks `.env`, `src/`, `storage/`, and the other non-public paths.
3. Upload the project, including `vendor/` if the host has no Composer. Prefer `composer install --no-dev --optimize-autoloader` over SSH when it is available.
4. Create `.env` on the server from `.env.example`. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the real `https://` origin, a new `APP_KEY`, and the Hostinger database credentials. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD`, run the two commands below, then you can remove the admin password from `.env`.
5. Make `storage/` writable by PHP (`storage/logs`, `storage/sessions`, `storage/uploads`). It must not be inside the public document root. `storage/.htaccess` denies web access as a second line of defense.
6. Over SSH:

```bash
php bin/migrate.php
php bin/seed.php
```

7. Sign in, turn on the authenticator, and fill Settings with the phone, email, mailing address, and insurance text you actually want published. Turn on the free SSL certificate in hPanel and confirm `APP_URL` uses `https://` so the session cookie is marked Secure.

Do not run `bin/demo-seed.php` on the server. Do not commit `.env`, resident lists, or gate codes. Step-by-step notes, including what to do when there is no SSH, are in [docs/deployment.md](docs/deployment.md).

## What you still enter

The repository does not contain a phone number, public email, street address, or insurance limit. Until those are saved in Settings, the site simply omits them. It also does not claim a license, a year founded, or a coverage amount. Community-wide pricing stays “contact for a quote.” Connect the domain in Hostinger when you are ready for it to be public.

## Status

Version 0.1.1 (see [CHANGELOG.md](CHANGELOG.md)). The site and backend are built and covered by the test suite, but not deployed yet. The planned domain is loadedvalet.com, which is not connected to this app yet.

## Documentation

- [docs/admin-guide.md](docs/admin-guide.md) — owner and manager
- [docs/porter-guide.md](docs/porter-guide.md) — nightly route on a phone
- [docs/deployment.md](docs/deployment.md) — Hostinger
- [docs/data-model.md](docs/data-model.md) — tables and billing rules
- [docs/screenshots/](docs/screenshots/) — desktop and phone captures. Admin shots use local DEMO records. The public review pages are empty.

## License

Proprietary. All rights reserved. Copyright 2026 Anthony Jaamal Jones / Loaded Valet Trash. See [LICENSE](LICENSE).
