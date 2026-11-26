# Tenant migrations

Plain `.sql` files, applied to **every** tenant database exactly once, in file-name order.

- `database/tenant/schema.sql` is the frozen baseline. Do not edit it for new changes.
- A schema change = a new file here, e.g. `2026_11_20_000001_add_slug_to_news_common_info.sql`.
- New tenants get the baseline and then all files here (during provisioning).
- Existing tenants get them with `php artisan tenants:migrate` (this also refreshes the views).
- Each file may hold several statements separated by `;`.
- `SELECT *` views are frozen at creation time, so after adding a column to a table the view must
  be recreated: `tenants:migrate` does that, because it re-runs `database/tenant/views.sql`.

## Category module

`2026_11_18_000001_create_categories_table.sql` replaces `category_info` + `sub_category_info` with one
`categories` table (`parent_id` + `level`, 3 levels max). It copies the old rows (top-level categories keep
their ids, sub-categories get new ids and `news_common_info.sub_cat_id` / `temp_news_info.sub_category` are
updated to match) and then drops the two old tables. Run `php artisan tenants:migrate` once after deploying.

`2026_11_18_000002_add_image_path_to_categories.sql` adds the optional category image path. Run
`php artisan tenants:migrate` after deploying so existing tenant databases can accept category images.
