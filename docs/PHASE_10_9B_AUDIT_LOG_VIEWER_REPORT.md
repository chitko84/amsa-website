# Phase 10.9B Audit Log Viewer Report

## Files Created

- `admin/audit_logs.php`

## Files Changed

- `admin/includes/sidebar.php`
- `amsa_web.sql`

## Access Control

- `admin/audit_logs.php` uses `requireSystemAdmin('login.php')`.
- Members cannot access the audit log viewer.
- Executive admin roles cannot access the audit log viewer.
- Sidebar link is inside the existing system-admin-only System section.
- No audit log deletion feature was added.

## Audit Log Table

Displayed fields:

- Date/Time
- Admin/User
- Action
- Entity Type
- Entity ID
- IP Address
- User Agent, shortened
- Old Values preview
- New Values preview
- View Details action

All output is escaped with `htmlspecialchars()`.

## Search, Filters, Sorting, Pagination

Search supports:

- Admin/user name
- Admin/user email
- Action
- Entity type
- Entity ID
- IP address

Filters added:

- Action type
- Entity type
- From date
- To date

Sorting added:

- Newest first
- Oldest first
- Action A-Z
- Entity Type A-Z

Pagination added:

- 10 rows
- 25 rows
- 50 rows

Default:

- Newest first
- 25 rows per page

All filter and sort values are allowlisted. Search and date filters use prepared statements.

## Details View

- Each audit row has a Bootstrap `View Details` modal.
- The modal shows:
  - Full date/time
  - Admin/user details
  - Action
  - Entity type
  - Entity ID
  - IP address
  - Full user agent
  - Full old values
  - Full new values
- JSON values are formatted with `JSON_PRETTY_PRINT` when valid.
- Raw values are escaped before display.

## Database Indexes Added

Added to `amsa_web.sql`:

- `idx_audit_logs_action` on `audit_logs(action)`

Already present and not duplicated:

- `fk_audit_logs_user` on `audit_logs(user_id)`
- `idx_audit_logs_entity` on `audit_logs(entity_type, entity_id)`
- `idx_audit_logs_created_at` on `audit_logs(created_at)`

## Verification Results

- PHP syntax checks passed:
  - `admin/audit_logs.php`
  - `admin/includes/sidebar.php`
- Source review verified:
  - `requireSystemAdmin('login.php')` is used.
  - Audit Logs sidebar link appears only inside the system-admin-only section.
  - Search/filter/sort/pagination preserve query parameters.
  - Details modal exists for each row.
  - No `DELETE FROM audit_logs` path exists.
  - Displayed values are escaped.
