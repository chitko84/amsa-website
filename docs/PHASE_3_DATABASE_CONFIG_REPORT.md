# Phase 3 Database And Config Consolidation Report

Date: 2026-06-04  
Scope: Database configuration consolidation and schema preparation only.

## Files Changed

- `config/database.php`
- `configdatabase.php`
- `amsa_web.sql`
- `PHASE_3_DATABASE_CONFIG_REPORT.md`

## Functions Added Or Moved

Moved/reimplemented points helpers from the obsolete root `configdatabase.php` into canonical `config/database.php` using MySQLi:

- `getAllPointCategories()`
- `getUserPoints($userId)`
- `getUserPointRequests($userId)`
- `getAllPointRequests()`
- `createPointRequest($userId, $pointCategoryId, $description, $filePath)`
- `updatePointRequestStatus($requestId, $status, $adminId, $remarks = null)`
- `getPointStatistics()`

Added missing public content helper:

- `getAllNewsAndEvents()`

This helper retrieves posts in these categories:

- `news`
- `announcement`
- `workshop`
- `volunteer`
- `community_engagement`

## Config Changes

- `config/database.php` is now the canonical database configuration file.
- Active database name remains `amsa_web`.
- Points helpers now use the existing `$conn` MySQLi connection.
- `configdatabase.php` no longer creates a PDO connection and no longer points to `amsa_website`.
- `configdatabase.php` is now a compatibility shim:
  - It documents that it is obsolete.
  - It safely includes `config/database.php`.
  - It was not deleted.

## Database Changes Made

Updated `amsa_web.sql` with production-oriented structural additions:

- Added `user.role` enum: `admin`, `member`.
- Added `user.status` enum: `active`, `inactive`, `suspended`.
- Added `user.created_at`.
- Added `user.updated_at`.
- Added `point_transactions` table for an idempotent approved-points ledger.
- Added `audit_logs` table for future admin action logging.

Added useful indexes:

- `post(category, upload_date)` for public content queries.
- `point_request(status, request_date)` for admin review queues.
- `user(role, status)` for future auth/authorization filtering.
- `user_points(total_points)` for leaderboard/ranking queries.
- `point_transactions(user_id, created_at)` for point history.
- `audit_logs(entity_type, entity_id)` and `audit_logs(created_at)` for future audit views.

Added foreign keys:

- `point_transactions.user_id -> user.id`
- `point_transactions.point_request_id -> point_request.id`
- `point_transactions.created_by -> user.id`
- `audit_logs.user_id -> user.id`

## SQL File Changes Made

`amsa_web.sql` now includes:

- Updated `CREATE TABLE user`.
- Updated `INSERT INTO user` seed row with `role`, `status`, `created_at`, and `updated_at`.
- New `CREATE TABLE point_transactions`.
- Seed transactions for the two existing approved point requests.
- New `CREATE TABLE audit_logs`.
- Index definitions for the new and existing tables.
- Auto-increment setup for new tables.
- Constraint definitions for new tables.

Existing data was preserved. The existing approved point total of `2000` is now represented by two seeded ledger rows:

- Request `1`: `1000` points.
- Request `2`: `1000` points.

## Double-Award Fix

`updatePointRequestStatus()` now prevents duplicate point awarding by:

- Running inside a MySQLi transaction.
- Locking the selected point request with `FOR UPDATE`.
- Checking the previous request status.
- Awarding points only when the request changes from `pending` to `approved`.
- Writing an `INSERT IGNORE` ledger row into `point_transactions`.
- Enforcing a unique key on `point_transactions.point_request_id`.
- Updating `user_points.total_points` only when the ledger insert is new.

If an already approved request is approved again, no additional transaction is inserted and no extra points are added.

## Verification

PHP syntax checks passed:

- `config/database.php`
- `configdatabase.php`
- `events.php`
- `point/point_request.php`
- `point/my_points.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`

Search verification:

- No PHP file actively requires `configdatabase.php`.
- No `PDO` usage remains in PHP files.
- No `amsa_website` database name remains in PHP files.
- Point pages already include `../config/database.php`, so they now load the migrated helpers.

## Remaining Issues For Phase 4

- Point pages still use fallback user ID `1` when no member session exists.
- Member login/register UI is still missing.
- Admin role enforcement is still missing on points admin pages.
- Evidence upload validation is still weak.
- Leaderboard and ranking pages are still missing.
- Points UI has not been redesigned or integrated into the main public layout.
- Existing production database must be re-imported or migrated so `point_transactions` and `audit_logs` exist before approval actions are used.

## Next Recommended Phase

Phase 4 - AMSA Points System Fixes.

Recommended first tasks:

- Implement member registration/login/session handling.
- Remove fallback user ID `1`.
- Add role checks for admin points pages.
- Build leaderboard/ranking views using `user_points` and `point_transactions`.
- Harden evidence upload validation.

