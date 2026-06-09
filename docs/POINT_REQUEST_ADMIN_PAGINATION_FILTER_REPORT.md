# Point Request Admin Pagination Filter Report

## Files Changed

- `config/database.php`
- `point/admin_points.php`

## Filter Options Added

- `status=all`
- `status=pending`
- `status=approved`
- `status=rejected`

Default: `all`

## Sort Options Added

- `sort=newest`
- `sort=oldest`
- `sort=points_desc`
- `sort=points_asc`
- `sort=status`

Default: `newest`

## Pagination Behavior

- Default page size: `10`
- Page size options: `10`, `25`, `50`
- GET parameters:
  - `page`
  - `per_page`
- Pagination displays:
  - Previous
  - Page numbers
  - Next
  - `Showing X-Y of Z requests`
- Pagination links preserve:
  - status filter
  - sort option
  - per-page option

## Helper Functions Added

- `getPointRequestsPaginated($status, $sort, $page, $perPage)`

Security notes:

- Status values are allowlisted.
- Sort values are allowlisted and mapped to fixed SQL fragments.
- Pagination values are normalized to approved numeric options.
- No raw GET sort/status values are injected into SQL.

## UI Changes

- Added an `.amsa-card` filter/sort panel above the point request table.
- Added controls for Status Filter, Sort By, Rows Per Page, Apply, and Reset.
- Empty filtered result state now says: `No point requests found for this filter.`

## Business Logic

- Approval logic was not changed.
- Rejection logic was not changed.
- Revert-to-pending logic was not changed.
- Delete logic was not changed.
- Point calculation was not changed.
- CSRF status-change forms remain POST-based.

## Verification Results

- PHP syntax checks passed:
  - `config/database.php`
  - `point/admin_points.php`
- Source review verified:
  - all requests show by default with `status=all`
  - pending/approved/rejected filters are allowlisted
  - newest/oldest/points/status sorting use safe SQL mappings
  - pagination preserves filter state
  - admin action forms still use POST + CSRF

## LONG_TERM_TABLE_SCALABILITY

### Pages Updated

- `point/admin_points.php`
  - Pagination added.
  - Status filter added.
  - Sort options added.
  - Query-state preservation added.

### Existing Controls Found

- `point/leaderboard.php`
  - Already supports Top 10, Top 25, and All.
  - Now benefits from zero-point eligibility filtering.
- `events.php`
  - Already has client-side content category filtering and search.
- `admin/dashboard.php`
  - Uses fixed preview limits for dashboard widgets, so it is not an unbounded full-table page.

### Pages Recommended For Next Scoped Scalability Pass

- `admin/members.php`
  - Add member search by name/email, role filter, status filter, and pagination.
- `admin/contact_messages.php`
  - Add search by name/email/subject, date sorting, and pagination.
- `admin/admin_users.php`
  - Add role/status filters and pagination for admin and member promotion lists.
- `point/my_points.php`
  - Add request history pagination and status filter for members with long histories.
- `point/point_categories_admin.php`
  - Current category list is normally small; add sorting/filtering only if categories become numerous.
- `achievements.php` and `cme.php`
  - Dynamic public lists can grow; add server-side pagination when public content volume is large.
- `fundraising.php`
  - Currently not a dynamic growing content table.
- `admin/database_backup.php`
  - Export actions are operational controls, not a growing on-page table in current source.

### Rationale

The practical high-impact change in this pass was the unbounded Admin Review request table, because it combines long history, modals, forms, evidence links, and status actions. Other pages were audited and documented for follow-up so unrelated member/content/admin-user workflows are not changed in the same pass.
