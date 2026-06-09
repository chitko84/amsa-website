# Phase 10.9A Long-Term Scalability Report

## Files Changed

- `config/database.php`
- `admin/members.php`
- `admin/contact_messages.php`
- `point/my_points.php`
- `point/leaderboard.php`
- `events.php`
- `achievements.php`
- `cme.php`
- `amsa_web.sql`

## Pagination Added

- `admin/members.php`
  - 10, 25, 50 rows per page.
- `admin/contact_messages.php`
  - 10, 25, 50 rows per page.
- `point/my_points.php`
  - 10, 25, 50 request rows per page.
- `events.php`
  - 9, 18, 27 posts per page.
- `achievements.php`
  - 9, 18, 27 achievements per page.
- `cme.php`
  - 9, 18, 27 community engagement posts per page.

## Search Added

- `admin/members.php`
  - Search by name or email.
- `admin/contact_messages.php`
  - Search by name, email, subject, or message.
- `point/leaderboard.php`
  - Admin-only search by user ID, name, or email.

## Filters Added

- `admin/members.php`
  - Role filter: all member/admin role values.
  - Status filter: all, active, inactive.
- `point/my_points.php`
  - Status filter: all, pending, approved, rejected.
- `events.php`
  - Server-side category filter for existing public content categories.

## Sorting Added

- `admin/members.php`
  - Newest first, oldest first, name A-Z, name Z-A, role, status.
- `admin/contact_messages.php`
  - Newest first, oldest first, subject A-Z, subject Z-A.
- `point/my_points.php`
  - Newest first, oldest first, points high-low, points low-high.

## Helper Functions Added

- `getUserPointRequestsPaginated($userId, $status, $sort, $page, $perPage)`
- `getPostsPaginated(array $categories, $page, $perPage)`

All filter and sort values are allowlisted. Pagination inputs are sanitized and constrained to approved page-size options.

## Database Indexes Added

Added only missing indexes in `amsa_web.sql`:

- `point_request`
  - `idx_point_request_user_status` on `(user_id, status)`
- `user`
  - `idx_user_role_status_created` on `(role, status, created_at)`

Already present and not duplicated:

- `point_request(status, request_date)`
- `point_request(point_category_id)`
- `post(category, upload_date)`
- `user(email)`
- `contact_messages(submission_date)`
- `point_transactions(user_id)`
- `point_transactions(point_request_id)`

## Preserved Behavior

- No CSS styling changes.
- No hero image changes.
- No navbar/footer/branding changes.
- No upload logic changes.
- No authentication logic changes.
- No leaderboard ranking logic changes.
- No point approval logic changes.
- No point calculation logic changes.
- Admin member actions, contact delete, request evidence links, and CSRF forms remain intact.
- Member leaderboard privacy remains protected; admin-only leaderboard search is not shown to normal members.

## Verification Results

PHP syntax checks passed:

- `config/database.php`
- `admin/members.php`
- `admin/contact_messages.php`
- `point/my_points.php`
- `point/leaderboard.php`
- `point/admin_points.php`
- `events.php`
- `achievements.php`
- `cme.php`

Source review verified:

- Members pagination/search/filters/sorting preserve query parameters.
- Contact message pagination/search/sorting preserve query parameters.
- My Points history pagination/filtering/sorting preserve query parameters.
- Public content pages query only the current page of records.
- Admin leaderboard search is only rendered in admin view.
- Normal member leaderboard identity remains anonymous.
- SQL sorting is allowlisted and not built from raw GET values.
