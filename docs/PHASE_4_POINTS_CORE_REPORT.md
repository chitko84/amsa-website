# Phase 4 AMSA Points Core Report

Date: 2026-06-04  
Scope: AMSA Points System core functionality only.

## Files Changed

- `config/database.php`
- `admin/login.php`
- `admin/dashboard.php`
- `admin/add_content.php`
- `admin/add_event.php`
- `admin/add_news.php`
- `admin/delete_content.php`
- `admin/edit_event.php`
- `admin/edit_news.php`
- `point/point_request.php`
- `point/my_points.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`
- `point/register.php`
- `point/login.php`
- `point/logout.php`
- `PHASE_4_POINTS_CORE_REPORT.md`

## New Files Created

- `point/register.php`
- `point/login.php`
- `point/logout.php`

## Authentication Implemented

Member registration now exists at `point/register.php`.

Registration collects:

- Full name
- Email
- Password
- Confirm password

Registration uses:

- Existing `user` table
- `role = member`
- `status = active`
- `password_hash()`

Member login now exists at `point/login.php`.

Login uses:

- Existing `user` table
- `password_verify()`
- Active user status check
- Session fields:
  - `user_id`
  - `user_name`
  - `user_email`
  - `user_role`

Admin login was updated to also set shared session fields while keeping existing admin session fields.

Logout now exists at `point/logout.php` and destroys the active session.

## Session And Role Helpers

Added reusable helpers in `config/database.php`:

- `requireLogin()`
- `requireMember()`
- `requireAdmin()`
- `currentUserId()`
- `currentUserRole()`

Rules now enforced:

- `point/point_request.php` requires member or admin.
- `point/my_points.php` requires member or admin.
- `point/admin_points.php` requires admin.
- `point/point_categories_admin.php` requires admin.
- Existing admin content pages now use `requireAdmin('login.php')` where their old admin session guard existed.

## Fake User Fallback Removed

Removed fallback logic that treated unauthenticated users as user ID `1`.

Point pages now use:

- `requireMember()` or `requireAdmin()`
- `currentUserId()`

Unauthenticated member pages redirect to:

- `point/login.php`

Admin point pages redirect unauthenticated users to:

- `admin/login.php`

## Activity Submission Fixed

`point/point_request.php` now supports:

- Real logged-in user submission.
- Active point category selection.
- Activity description.
- Evidence upload.
- Pending request creation through `createPointRequest()`.
- Success/error messages.
- Member navigation links.

Submitted requests start as `pending`.

## Upload Security Implemented

Evidence uploads now enforce server-side validation:

- Allowed extensions:
  - PDF
  - JPG
  - JPEG
  - PNG
- MIME validation with `finfo_file()`.
- Maximum size: 5MB.
- Random safe filename using `random_bytes()`.
- Upload destination: `point/uploads/eop/`.
- Executable extensions are rejected by whitelist.

Stored database path format:

- `uploads/eop/<random-file-name>`

## Member Dashboard Fixed

`point/my_points.php` now shows:

- Total points.
- Approved request count.
- Pending request count.
- Rejected request count.
- Recent submissions.
- Evidence links.
- Status badges.
- Admin remarks.

## Admin Approval Flow Fixed

`point/admin_points.php` now:

- Requires admin role.
- Shows all requests.
- Shows member name/email.
- Shows category and points.
- Shows description.
- Shows evidence preview/link.
- Shows status.
- Supports approve/reject actions.
- Supports admin remarks.

Approval uses the Phase 3 safe `updatePointRequestStatus()` function.

Double-award prevention remains active through:

- Status transition check from `pending` to `approved`.
- `point_transactions.point_request_id` unique key.
- Transaction-backed approval logic.

## Point Category Admin Fixed

`point/point_categories_admin.php` now:

- Requires admin role.
- Allows category creation.
- Allows category edit.
- Disables active categories instead of hard-deleting them.
- Allows inactive categories to be re-enabled.

Existing request history is preserved because categories are no longer deleted from this UI.

## Database Changes Made

No additional `amsa_web.sql` structural changes were required in Phase 4.

The Phase 3 schema already had the required fields:

- `user.role`
- `user.status`
- `point_category.status`
- `point_transactions`

## Verification

PHP syntax checks passed for:

- `config/database.php`
- `admin/login.php`
- `admin/dashboard.php`
- `admin/add_content.php`
- `admin/add_event.php`
- `admin/add_news.php`
- `admin/delete_content.php`
- `admin/edit_event.php`
- `admin/edit_news.php`
- `point/register.php`
- `point/login.php`
- `point/logout.php`
- `point/point_request.php`
- `point/my_points.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`

Search checks:

- No fallback user ID `1` remains in the point pages.
- `point/admin_points.php` requires `requireAdmin()`.
- `point/point_categories_admin.php` requires `requireAdmin()`.
- Point member pages use `requireMember()`.

Manual code flow review:

1. Register: `point/register.php` creates an active member with hashed password.
2. Login: `point/login.php` verifies password and sets session.
3. Submit Activity: `point/point_request.php` validates category, description, and evidence, then creates a pending request.
4. Admin Approve: `point/admin_points.php` calls `updatePointRequestStatus()`.
5. Points Updated: `updatePointRequestStatus()` writes a ledger transaction and updates `user_points`.
6. Member Dashboard Updated: `point/my_points.php` reads `user_points` and request history for the logged-in user.

## Remaining Issues For Phase 5

- UI is still basic and not yet consistent with the full AMSA site.
- CSRF protection is still needed for production hardening.
- Evidence files are still stored under a web-accessible folder.
- Admin review can be improved with filters/search/pagination.
- Member dashboard can be improved with cleaner timeline/history UI.
- Leaderboard and ranking are not implemented yet.
- Email verification and password reset are not implemented yet.

## Next Recommended Phase

Phase 5 - UI/UX Consistency.

Recommended focus:

- Bring points pages into the AMSA visual layout.
- Add consistent nav/sidebar patterns.
- Improve dashboard and admin review usability.
- Keep advanced analytics/leaderboard for a later dedicated phase if needed.

