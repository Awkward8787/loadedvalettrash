# Security

Loaded Valet Trash keeps resident rosters, gate notes, and staff sign-in on one server. Treat the production database and `.env` as private.

## How sign-in works

- Passwords are stored with `password_hash` (bcrypt). The sign-in message is the same for an unknown email, a wrong password, and an inactive account.
- Every staff account must turn on a time-based authenticator (TOTP) before using the admin. Recovery codes are hashed, shown once, and each code works a single time.
- Authenticator secrets are encrypted with AES-256-GCM under `APP_KEY`.
- Five failed attempts for one email, or eight from one IP, inside 15 minutes lock sign-in for that window. The lock applies even if the next password is correct.
- Forms require a per-session CSRF token. A failed check returns HTTP 419.
- Sessions are server-side rows tied to a random token. Idle time defaults to 30 minutes and is editable in Settings. The cookie is HttpOnly and SameSite=Lax, and it is Secure when `APP_URL` starts with `https://`.
- Porters can open only their own route. Managers cannot change Settings or create an owner. The last active owner cannot be demoted or deactivated.

## Data that stays off the public site

- Phone, email, street address, and insurance wording come from one settings row. Empty fields are omitted. Do not type placeholders such as a bracketed phone number.
- Gate and access notes are shown to owners, managers, and the porter assigned to that property. They are excluded from roster CSV exports, weekly issue reports, and audit-log text. The property form asks you not to paste a code into an ordinary note.
- Uploaded issue photos are stored under `storage/uploads` with random names and are served only through a signed-in admin URL.
- Review text is stored as pending and is rendered publicly only after approval.

## Reporting a problem

If you find a vulnerability, contact the owner directly once a private email is in use. Do not open a public GitHub issue that includes resident data, a gate code, a session cookie, or an exploit. Give the page, the account role, and what you were able to see.

## Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, and HTTPS.
- `.env` is not in git and is not downloadable. The root `.htaccess` denies it when the project sits above the document root.
- `storage/` is writable by PHP and is not the document root.
- `bin/demo-seed.php` has not been run.
- The first owner has completed authenticator enrollment, and the admin password is not left sitting in a shared note.
