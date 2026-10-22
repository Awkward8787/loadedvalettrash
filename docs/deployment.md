# Deploying on Hostinger

Loaded Valet Trash is a PHP 8 application with a MySQL database. It does not need Node, Redis, or a build step on the server. Hostinger’s PHP hosting (shared, premium, or business) can run it. Use a plan that includes MySQL and a current PHP 8.2 or 8.3.

## 1. Database

In hPanel, open **Databases → MySQL Databases**. Create a database and a user, and grant that user all privileges on that database. Write down:

- host, usually `localhost`
- database name
- user
- password
- port `3306` unless hPanel shows another

## 2. Files

On your own computer:

```bash
composer install --no-dev --optimize-autoloader
```

Upload the project with SFTP, or with Git if the hosting account has it. The upload includes `vendor/`. It must not include `.env`, `storage/logs/*` contents, `storage/sessions/*`, or `storage/uploads/*`. Keep the `.gitkeep` files so those directories exist.

### Document root

Preferred: set the domain’s document root to the `public` directory inside the project.

If hPanel only serves `public_html`, either:

- put the project in the account home and set the domain folder to `public_html/public` after uploading there, or
- upload the project so that `public_html` contains `public/`, `src/`, `.htaccess`, and the rest. The `.htaccess` in the project root rewrites visitors into `public/` and denies direct access to `.env`, `src/`, `storage/`, `migrations/`, `tests/`, `vendor/`, and the docs.

Confirm `https://your-domain.example/assets/brand/loaded-valet-trash-logo.png` loads, and that `https://your-domain.example/.env` does not.

## 3. Environment file

Copy `.env.example` to `.env` on the server. Set:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_TIMEZONE=America/New_York
APP_KEY=base64:...
DB_HOST=localhost
DB_PORT=3306
DB_NAME=the_hpanel_database
DB_USER=the_hpanel_user
DB_PASS=the_hpanel_password
ADMIN_NAME=Anthony Jaamal Jones
ADMIN_EMAIL=you@your-domain.example
ADMIN_PASSWORD=a-long-password-with-a-number
```

Generate `APP_KEY` on your computer:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Use a new key for production. The key in `phpunit.xml` is for the test suite only.

Turn on the free SSL certificate for the domain in hPanel before you rely on `APP_URL` starting with `https://`. That prefix marks the session cookie Secure.

## 4. Migrate and create the owner

SSH into the account, `cd` to the project, and run:

```bash
php bin/migrate.php
php bin/seed.php
```

`seed` creates one owner from `ADMIN_EMAIL` and `ADMIN_PASSWORD` and then does nothing if an owner already exists. It does not insert properties or reviews.

If the plan has no SSH, open **Advanced → PHP Configuration** or the cron screen is not a substitute. Use hPanel’s **SSH Access** switch (available on many current plans) or run the same two commands from a local PHP that can reach the Hostinger MySQL host if remote MySQL is enabled. Remote MySQL is optional and should be turned back off afterward. Do not leave `APP_DEBUG=true` to “see if it connected.”

After a successful seed, delete `ADMIN_PASSWORD` from the server `.env`. The password is already hashed in the database.

## 5. Permissions

PHP must be able to write:

- `storage/logs`
- `storage/sessions`
- `storage/uploads`

`storage/.htaccess` denies HTTP access. Photos attached to issues are not placed under `public/`.

## 6. First sign-in

Open `https://your-domain.example/login`. Enter the owner email and password. The next screen is authenticator enrollment. Add the key to Google Authenticator, 1Password, Authy, or a similar app, confirm the 6-digit code, and save the eight recovery codes somewhere offline. Admin pages stay closed until this is done.

Then open **Settings** and enter only the contact details and insurance wording you want the public site to show. Leave a field blank to keep it off the site.

## 7. What not to run

- `php bin/demo-seed.php` exits immediately unless `APP_ENV=local`. Production must stay `production`.
- Do not import a real roster CSV until the property record is the live one, and do not put gate codes in that CSV. The importer has no column for them.
- Do not commit a copy of the server `.env` back to GitHub. This repository is public.

## 8. Updates

Upload the new files, then run `php bin/migrate.php` again. Migrations that are already recorded in `schema_migrations` are skipped. Sign-in sessions survive a file update. Changing `APP_KEY` makes existing authenticator secrets unreadable, so every staff member would have to enroll again. Keep the same key.

## 9. Still yours to decide

- The domain name and DNS (Hostinger, or an outside registrar pointed at Hostinger)
- The public phone, email, and mailing address
- The insurance sentence, if you want one on the site and on proposals
- The first owner’s real email address
