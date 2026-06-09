# Phase 8G Leaderboard Privacy Patch Report

## Scope

Phase 8G was limited to member privacy on the AMSA Points leaderboard.

No ranking logic, database structure, points approval logic, or helper query behavior was changed.

## Files Changed

- `point/leaderboard.php`

## What Member Users Can See

Member users now see anonymized leaderboard identities only:

- `Member #USER_ID`
- Current logged-in member shown as `You — Member #USER_ID`
- Rank
- Total points
- Approved request count
- Latest approved activity date

Member users do not see:

- Full member names
- Member emails
- Separate user ID column

## What Admins Can See

Admin users can still view the full leaderboard details:

- Full member name
- Email
- User ID
- Rank
- Total points
- Approved request count
- Latest approved activity date

## How Names and Emails Were Protected

- Added a display-layer role check in `point/leaderboard.php`.
- Added `leaderboardIdentity()` to render either:
  - Full name for admin users.
  - `Member #USER_ID` for member users.
  - `You — Member #USER_ID` for the current member.
- Email and User ID table columns are rendered only when the current viewer is an admin.
- Top-three podium cards hide email and full name for member users.
- Current-user summary hides name/email for member users.

The existing `getLeaderboard()` and `getUserRank()` helpers still return the same data internally so ranking behavior remains unchanged.

## Verification Results

PHP syntax check passed:

- `point/leaderboard.php`

Static verification completed:

- Member-facing display uses `Member #ID`.
- Current member display uses `You — Member #ID`.
- Member-facing table does not render the Email column.
- Member-facing table does not render full names.
- Current member row highlighting remains unchanged through `current-user-row`.
- Admin-only branches still render full name, user ID, and email.
- Ranking still uses the existing helper output and ordering logic.

## Remaining Notes

- Live browser verification should be done during the next full QA pass with both a member login and an admin login.
