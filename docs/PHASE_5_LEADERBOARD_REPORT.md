# Phase 5 Leaderboard And Ranking Report

Date: 2026-06-04  
Scope: AMSA Points leaderboard and ranking system only.

## Files Changed

- `config/database.php`
- `point/my_points.php`
- `point/point_request.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`
- `point/leaderboard.php`
- `PHASE_5_LEADERBOARD_REPORT.md`

## New Files Created

- `point/leaderboard.php`

## Helper Functions Added

Added to `config/database.php`:

- `getLeaderboard($limit = 25)`
- `getUserRank($userId)`

## Ranking Logic Used

Leaderboard rows are limited to:

- `user.role = 'member'`
- `user.status = 'active'`

Ranking order:

1. `total_points` descending.
2. Approved request count descending.
3. Latest approved activity date descending.

The query uses:

- `user`
- `user_points`
- `point_request`

`point_transactions` remains the source of safe point-award ledger writes from Phase 3/4. The leaderboard reads the current totals from `user_points`, which are updated only through safe approval logic.

## Leaderboard Page

Created `point/leaderboard.php` with:

- Rank number.
- Member name.
- Email.
- Total points.
- Approved request count.
- Latest approved activity date.
- Current logged-in user row highlight.
- Current logged-in user rank summary at the top.
- Top 10 / Top 25 / All filters.
- Member and admin access through existing auth helpers.

## Navigation Links Added

Added `Leaderboard` links to:

- `point/my_points.php`
- `point/point_request.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`

The points navigation now includes:

- My Points
- Submit Activity
- Leaderboard
- Admin Review, for admin users
- Categories, for admin users
- Logout

## Safety

- Leaderboard output uses `htmlspecialchars()` for member names and emails.
- Password is not selected or displayed by the leaderboard.
- Only active member users are shown.
- Admin users can view the leaderboard but are not ranked as members.

## Verification Completed

PHP syntax checks passed:

- `config/database.php`
- `point/leaderboard.php`
- `point/my_points.php`
- `point/point_request.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`

Search checks completed:

- No `password` reference appears in `point/leaderboard.php` or the leaderboard helper query.
- Ranking query includes `total_points DESC`.
- Ranking query includes `approved_request_count DESC`.
- Ranking query includes `latest_approved_activity_date DESC`.
- Leaderboard page uses `requireMember('login.php')`, allowing member and admin access through Phase 4 role rules.
- Leaderboard page conditionally exposes admin links only when `currentUserRole() === 'admin'`.

Manual code flow review:

1. Member or admin logs in.
2. User opens `point/leaderboard.php`.
3. Page loads active member rankings using `getLeaderboard()`.
4. Current user's rank summary is loaded through `getUserRank(currentUserId())`.
5. Current user's row is highlighted when present in the selected filter result.
6. Top 10, Top 25, and All filters adjust the row limit.

## Remaining Issues For Phase 6

- CSRF protection is still needed across state-changing forms.
- Evidence files are still web-accessible under `point/uploads/eop/`.
- Leaderboard currently has no pagination for very large member lists.
- Ranking does not yet include semester/session filters.
- No export/download feature for admins.
- UI consistency with the main AMSA site is still pending.

## Next Recommended Phase

Phase 6 - Security Hardening.

Recommended focus:

- CSRF tokens for POST forms.
- Stronger session cookie settings.
- Evidence download controller with access checks.
- Upload storage and serving hardening.
- Admin action audit logging.

