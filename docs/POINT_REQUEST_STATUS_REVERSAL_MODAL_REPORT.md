# Point Request Status Reversal Modal Report

## Files Changed

- `config/database.php`
- `point/admin_points.php`

## Status Transitions Supported

`updatePointRequestStatus($requestId, $newStatus, $adminId, $remarks = null)` now supports:

- `pending` -> `approved`
- `pending` -> `rejected`
- `approved` -> `rejected`
- `approved` -> `pending`
- `rejected` -> `approved`
- `rejected` -> `pending`
- Same-status updates for remarks without extra point changes.

## Point Reversal Logic

- Approval uses the existing unique `point_request_id` transaction protection.
- Moving to `approved` inserts one `award` transaction with `INSERT IGNORE`.
- User points are increased only when the award transaction is newly inserted.
- Moving away from `approved` locks related transaction rows, subtracts awarded points, deletes the related transaction, and updates `user_points`.
- Point reversal uses `max(0, current - awarded)` so `total_points` cannot become negative.
- Approved request deletion now also locks transaction rows before subtracting/deleting.
- All status updates run inside a database transaction and write audit logs.

## Confirmation Modals Added

`point/admin_points.php` now uses Bootstrap confirmation modals before status changes:

- Approve:
  - Title: `Confirm Approval`
  - Message: `Are you sure you want to approve this point request and award points?`
- Reject:
  - Title: `Confirm Rejection`
  - Message: `Are you sure you want to reject this point request?`
- Revert to Pending:
  - Title: `Confirm Revert to Pending`
  - Message: `Are you sure you want to move this request back to pending? If points were already awarded, they will be removed.`
- Delete Approved Request:
  - Title: `Confirm Delete Approved Request`
  - Message: `This action will delete the request and remove awarded points from the member. This cannot be undone.`

Each modal submits through POST with CSRF and includes an optional remarks textarea for status changes.

## Admin Review UI

- Pending requests show:
  - Approve
  - Reject
- Approved requests show:
  - Revert to Pending
  - Mark Rejected
  - Delete
- Rejected requests show:
  - Revert to Pending
  - Approve
  - Delete
- Approved request actions show:
  - `Changing this approved request will adjust the member's total points.`

## Verification Results

- PHP syntax checks passed:
  - `config/database.php`
  - `point/admin_points.php`
- Source review verified:
  - `pending` -> `approved` adds points once.
  - `approved` -> `rejected` subtracts awarded points and deletes the transaction.
  - `approved` -> `pending` subtracts awarded points and deletes the transaction.
  - `rejected` -> `approved` can award points once.
  - `approved` -> `approved` does not double-add points because of `INSERT IGNORE` and the unique transaction key.
  - `total_points` cannot become negative.
  - Status and delete actions use Bootstrap modals.
  - Status and delete actions use POST + CSRF.
  - Leaderboard member-only filter remains unchanged.
- Local authenticated HTTP check confirmed `point/admin_points.php` still loads with status `200`.

No leaderboard ranking logic, member submission logic, or database schema was changed.
