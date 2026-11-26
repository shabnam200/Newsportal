# Main database (MySQL)

The main database is the one imported from `sample.sql`. sqlite is no longer used.

## Set up (once)
1. Import `sample.sql` into the main database (for example `sample`).
2. Import `main_db_additions.sql` into the same database. It only creates 11 new tables
   (portals, admins, packages, subscriptions, payments, portal_charges, portal_activity_logs,
   grace_requests, purchase_requests, portal_support_sessions, portal_support_events).
   No table from `sample.sql` is changed.
3. Optional: import `central_data_from_sqlite.sql` to copy the old sqlite data (portals, admin, packages, subscriptions, ...).
4. In `.env` set `DB_CONNECTION=mysql` and `DB_DATABASE` to that database. Keep the `TENANT_DB_*` settings.

## Rules
- Do NOT run `php artisan migrate`. It would create Laravel's extra `migrations` table in the main database.
  The old PHP migrations are kept in `legacy_sqlite_migrations_do_not_run/` for reference only.
- Tenant databases are still created and changed by `database/tenant/schema.sql` and `database/tenant/migrations/`
  (`php artisan tenants:migrate`).
- A change to a main-database table = a new `.sql` file in this folder.

## Public API token (added later)
Import `2026_11_23_000001_add_api_token_to_portals.sql` into the main database once (safe to repeat).
It adds the token columns to `portals`. Then create a token in the admin panel (portal page > "Public API token")
or with `php artisan portal:api-token <portal id>`. See `docs/PORTAL_API.md`.
