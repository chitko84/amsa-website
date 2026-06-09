# scalarCount Helper Bug Fix Report

## Root Cause

`events.php` called the missing `scalarCount` helper, but no global helper with that name exists in the shared application helpers. `admin/dashboard.php` had a page-local helper with that name, but the project-wide search still left missing-helper call references and the public events page could fatal with:

`Fatal error: Call to undefined function scalarCount`

## Files Fixed

- `events.php`
- `admin/dashboard.php`

## What Replaced scalarCount

### events.php

Replaced the two public page count calls with a local prepared helper:

`countPostsByCategory($category)`

The helper runs:

`SELECT COUNT(*) AS total FROM post WHERE category = ?`

and binds the category with `bind_param()`.

### admin/dashboard.php

Renamed and adjusted the repeated count helper to:

`getDashboardCount($conn, $sql)` style behavior via the existing global connection:

`getDashboardCount($sql)`

It runs the dashboard's static count queries, returns the first selected scalar value, and falls back to `0` on failure.

## GET Default Fixes

`events.php` now uses safe defaults:

- `$category = $_GET['category'] ?? 'all';`
- `$contentPage = max(1, (int) ($_GET['page'] ?? 1));`
- `$contentPerPage = (int) ($_GET['per_page'] ?? 9);`
- Invalid `per_page` values fall back to `9`.

Allowed `per_page` values:

- `9`
- `18`
- `27`

Allowed category values:

- `all`
- `news`
- `announcement`
- `workshop`
- `volunteer`
- `community_engagement`

Invalid categories fall back to `all`. Pagination URLs now use the sanitized current category, page, and per-page values rather than raw `$_GET` values.

## Verification Results

Search verification:

- `rg "scalarCount\("`
- Result: no matches.

PHP syntax checks:

- `C:\xampp\php\php.exe -l events.php`
- Result: `No syntax errors detected in events.php`

- `C:\xampp\php\php.exe -l admin\dashboard.php`
- Result: `No syntax errors detected in admin\dashboard.php`

Runtime safety checks:

- `http://localhost/amsa-website%20-%20Copy/events.php`
- Result: HTTP `200`, no `Fatal error`, `Warning`, `Notice`, `undefined function`, or `Undefined array key` labels found.

- `http://localhost/amsa-website%20-%20Copy/events.php?category=all`
- Result: HTTP `200`, no `Fatal error`, `Warning`, `Notice`, `undefined function`, or `Undefined array key` labels found.

- `http://localhost/amsa-website%20-%20Copy/events.php?category=news&page=1&per_page=9`
- Result: HTTP `200`, no `Fatal error`, `Warning`, `Notice`, `undefined function`, or `Undefined array key` labels found.

- `http://localhost/amsa-website%20-%20Copy/admin/dashboard.php`
- Result: HTTP `302` to the admin login flow without a fatal error in the unauthenticated request context. Authenticated admin rendering was not verified because no admin browser session was available to this command-line check.
