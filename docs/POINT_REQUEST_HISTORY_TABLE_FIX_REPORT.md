# Point Request History Table Fix Report

## File Changed

- `point/point_request.php`

## Layout Change

- Replaced the nested request-history card layout with a clean responsive table.
- Added columns:
  - Activity
  - Description
  - Requested Date
  - Evidence
  - Reviewed Date
  - Remarks
  - Status
- Kept evidence links routed through `evidence.php?id=...`.
- Kept status display as existing AMSA badges.
- Preserved the existing empty state when there are no requests.
- Business logic, submission logic, database logic, and approval logic were not changed.

## Verification

- PHP syntax check passed:
  - `C:\xampp\php\php.exe -l point\point_request.php`
