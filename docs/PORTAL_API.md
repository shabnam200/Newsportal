# Public read-only API (one token = one tenant database)

## One-time setup
1. Import `database/main/2026_11_23_000001_add_api_token_to_portals.sql` into the MAIN database (phpMyAdmin > Import). Do not run `artisan migrate`.
2. Create a token for a portal, either
   - Admin panel > Portals > (portal) > **Public API token** > *Create API token*, or
   - `php artisan portal:api-token <portal id>` (`--revoke` removes it).
3. Copy the token (`npk_...`). Only its SHA-256 hash is stored, so it is shown once. *Regenerate* replaces it; the old one stops working immediately.

## Categories (token in the URL)
```
GET http://localhost:8000/api/get_categories/{token}
```
Returns the portal's categories as a tree (`children` inside each category). Options: `?flat=1` (plain list), `?status=1` (active only).
Note: a token in the URL is stored in server/proxy access logs; prefer the header form below for anything public.

## Any table / the whole database (token in the URL)
| URL | Returns |
| --- | --- |
| `GET /api/get_tables/{token}` | list of readable tables with their columns |
| `GET /api/get_table/{table}/{token}` | rows of one table, paginated (`page`, `per_page` max 200, `order_by`, `direction`, `fields=a,b`, `filter[col]=value`) |
| `GET /api/get_all_data/{token}` | first rows of EVERY readable table in one JSON (`limit` per table, default 100, max 1000; `tables=a,b` to pick) |

Example: `GET /api/get_table/news_common_info/{token}?per_page=50&page=2`.
Tokens are `npk_` + 20 random characters (`PORTAL_API_TOKEN_LENGTH`, minimum 16). Older, longer tokens keep working until regenerated.

## Calling it
Send the token in a header (never in the URL):

```bash
curl -H "Authorization: Bearer npk_xxxxxxxx" https://YOUR-DOMAIN/api/v1/tables
```
(`X-API-Token: npk_xxxxxxxx` works too.)

| Endpoint | What it returns |
| --- | --- |
| `GET /api/v1` | portal name + endpoint list |
| `GET /api/v1/tables` | every readable table with its columns (`rows_estimate` is approximate) |
| `GET /api/v1/tables/{table}` | rows, paginated |
| `GET /api/v1/tables/{table}/{id}` | one row by primary key |

List options: `page`, `per_page` (default 50, max 200), `order_by`, `direction=asc|desc` (default: primary key, desc),
`fields=a,b,c`, `filter[column]=value` (exact match).

```bash
curl -H "Authorization: Bearer $TOKEN" \
  "https://YOUR-DOMAIN/api/v1/tables/news_common_info?per_page=20&filter[cat_id]=3&fields=news_id,news_headline"
```

Response: `{ "data": [...], "meta": { "table", "page", "per_page", "total", "last_page" } }`.
Errors are JSON: `{ "error": "<code>", "message": "..." }` (401 bad/missing token, 403 portal suspended or subscription expired,
404 unknown or hidden table, 422 bad parameter, 429 rate limit).

## What is NOT exposed
Configured in `config/portal_api.php`:
- tables `users, user_profiles, user_autologin, login_attempts, ci_sessions, app_setting_info, app_user_info, email_subscriber_info, subscription_tbl` (they answer 404);
- columns such as `password`, `*_key`, `app_token`, `gcmID`, `last_ip`, `member_email`, `member_phone` in every table.

Remove an entry from those lists only if you really want it public.

## Limits and safety
- Read-only (GET only). Only the database of the token's portal is reachable.
- 120 requests/minute per token (`PORTAL_API_RATE_LIMIT`); 20 wrong tokens/minute per IP (`PORTAL_API_FAILED_PER_MINUTE`).
- Suspended portals and expired subscriptions (grace period still allowed) are refused, same as the dashboard.
- Every call is logged in the portal's activity log (path, status, IP, duration).
- Keep the token on a server. A token placed in browser JavaScript can be read by every visitor.
- Production: `APP_DEBUG=false`, HTTPS only.
