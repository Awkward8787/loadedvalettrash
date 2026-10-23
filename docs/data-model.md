# Data model

MySQL, InnoDB, `utf8mb4`. The schema is `migrations/001_init.sql`. `php bin/migrate.php` applies each file once and records it in `schema_migrations`. The application does not create that tracking table inside the SQL file. The migrator does.

Money columns are integer cents. Shares and payroll multipliers are decimals. Clock times are stored in `America/New_York` as `YYYY-MM-DD HH:MM:SS` from PHP, not from `NOW()` inside a query, so the app and the database agree on the timezone.

## People and sign-in

- `users` — staff. `role` is `owner`, `manager`, or `porter`. `password_hash` is bcrypt. `totp_secret` is AES-256-GCM ciphertext. `pay_rate_cents` is the wage used when that person’s hours are costed.
- `recovery_codes` — SHA-256 of a one-time recovery code.
- `login_attempts` — success and failure rows used for the 15-minute lock.
- `user_sessions` — SHA-256 of the session token, last activity, and expiry.
- `audit_log` — who did what. Details are short and do not include gate-note text.

## Settings

- `settings` — one row, `id = 1`, inserted by the migration. Contact fields, `insurance_text`, and `porter_job_details` start null. `porter_job_details` is the only job-details copy on `/apply`; blank means that block is omitted. Operating defaults match the service: opt-in 3500 cents, share 10%, 1 minute per door, 45 minutes per site, 120-minute window, set-out 18:00 to 20:00, pickup 20:00 to 22:00, 2 bags (4 after a holiday), 25 lb, wage 1500 cents, payroll 1.10, supplies 4000, gas 9000, vehicle reserve 2500, fixed costs 34900, remittance day 10, session timeout 30 minutes.
- `holidays` — `service_off = 1` means no route. The 2026–2027 list from the operating calendar is inserted here, including Martin Luther King Jr. Day 2027 with `service_off = 0`.

## Pipeline and properties

- `management_companies` — optional group for several properties.
- `properties` — `stage` is `lead`, `contacted`, `walkthrough`, `proposal`, `signed`, `live`, or `lost`. `pricing_model` is `opt_in`, `per_door`, or `flat`. Null price and share columns mean “use settings.” `gate_access_notes` is the restricted access field. `planned_doors` is the route estimate before a roster exists.
- `property_contacts`, `property_notes`, `buildings`.

## Roster and routes

- `residents` — `pending`, `active`, or `cancelled`, with start and cancel dates. Billable for a month when status is active or cancelled, the start date is on or before month-end, and the cancel date is null or on or after the 1st (active) or on or after the 1st (cancelled).
- `route_templates` and `route_template_stops` — weekday (1–5), porter, ordered properties.
- `route_runs` and `route_stops` — a dated route. A stop is a building, or the whole property when no buildings exist.
- `door_checks` — one row per active resident on that stop, status `pending`, `picked_up`, `missed`, or `violation`.
- `time_entries` — clock in and clock out for a user, optionally tied to a run.

Route minutes for a property are `doors × minutes_per_door + minutes_fixed`. Doors are active residents, else `planned_doors`, else `units_count` for `per_door` and `flat`, else 0. Porters needed is `ceil(minutes / pickup_window)`, at least 1.

## Issues, reviews, inquiries, invoices

- `issues` — `missed_pickup`, `violation`, or `other`. `source` is `public`, `porter`, or `staff`. `photo_path` is a filename under `storage/uploads`, not a public URL. `external_property_name` keeps the name a resident typed when it did not match a property.
- `reviews` — default `pending`. Public pages read `approved` only.
- `inquiries` — public proposal, walkthrough, event cleanup, or move-out requests (`kind` is `proposal`, `walkthrough`, `event_cleanup`, `move_out`, or legacy `other`), linked to a property when one was created or matched.
- `applications` — public porter applications (`migrations/004_applications.sql`). `nights` is a comma list of `mon` through `fri`. `status` is `new`, `contacted`, `hired`, or `declined`. No foreign keys. `form_attempts` counts public apply posts for the same 15-minute caps as login, and is not the login table.
- `invoices` — one per property per month. Unique on `(property_id, period_year, period_month)`. Status `draft`, `sent`, `paid`, or `void`. Late is not a stored status. A draft or sent invoice with `due_on` before today is late. `due_on` is the remittance day of that month, clamped to 1–28.

### Invoice math

- Opt-in: `doors × rate`, share = round(gross × share percent), due = gross − share. Doors are the billable roster.
- Per door: `units × per-door rate`, share 0.
- Flat: the flat amount, share 0.

### Profit

`due − labor − supplies − gas − vehicle reserve − fixed share`.

Labor is closed `time_entries` in that month. Each entry’s hours are multiplied by the porter’s pay rate and `payroll_multiplier` (1.10). If the entry is tied to a route, hours are split across that route’s properties by estimated minutes. Fixed share is `monthly_fixed_costs_cents / count of non-void invoices that month`.

The planning example used to check the formula, with hours supplied explicitly rather than invented by the app: 60 doors, $35, 10% share → gross $2,100, share $210, due $1,890. At 1.75 hours × 21.67 nights, $15/hour, and 1.10 payroll, labor is $625.72. Supplies $40, gas $90, reserve $25, and fixed $349 bring other costs to $504. Profit is $760.28. The application records profit from clocked hours, so a month with no clock records shows labor at zero.

## What is intentionally absent

No review rows, no sample properties, and no staff users are in the migration. The owner row is created by `bin/seed.php`. DEMO rows exist only after `bin/demo-seed.php` on a machine whose `APP_ENV` is `local`.
