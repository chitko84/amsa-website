# Phase 9 Backup & Export Center Report

## Scope

Added an admin-only Backup & Export Center for AMSA data exports.

This implementation uses PHP/MySQLi only and does not call `mysqldump` or any shell command, so it remains suitable for XAMPP, shared hosting, and cPanel-style hosting.

## Files Created

- `admin/database_backup.php`

## Files Changed

- `admin/includes/sidebar.php`

## Export Types Added

### Full Database SQL Backup

- Exports current database structure.
- Exports current database data.
- Streams a timestamped `.sql` file.
- Filename format:
  - `amsa_backup_YYYY_MM_DD_HHMMSS.sql`

### Contact Message Export

- Exports `contact_messages` as CSV.
- Includes:
  - Name
  - Email
  - WhatsApp Number
  - Subject
  - Message
  - Submission Date
- Filename format:
  - `contact_messages_YYYY_MM_DD.csv`

### Points System Export

- Exports member points data as CSV.
- Includes:
  - User ID
  - Member Name
  - Email
  - Total Points
  - Approved Requests
  - Pending Requests
  - Rejected Requests
- Filename format:
  - `points_report_YYYY_MM_DD.csv`

### Leaderboard Export

- Exports admin-visible leaderboard rankings as CSV.
- Includes:
  - Rank
  - User ID
  - Name
  - Email
  - Total Points
- Filename format:
  - `leaderboard_YYYY_MM_DD.csv`

### Member Export

- Exports member list as CSV.
- Includes:
  - User ID
  - Name
  - Email
  - Status
  - Role
  - Registration Date
- Filename format:
  - `members_YYYY_MM_DD.csv`

## UI Added

- Added `admin/database_backup.php`.
- Added one `.amsa-card` per export type.
- Used existing AMSA design system classes:
  - `.amsa-card`
  - `.amsa-btn`
  - `.amsa-alert`
  - `.amsa-page-header`
- Added a backup handling note reminding admins that exports may contain personal information.

## Navigation

Updated `admin/includes/sidebar.php`.

System section now includes:

- Settings
- Contact Messages
- Database Backup
- Logout

## Security Measures

- `admin/database_backup.php` requires `requireAdmin('login.php')`.
- All export actions use POST.
- All export forms include `csrfInput()`.
- Export requests are rejected if `verifyCsrfToken()` fails.
- Members cannot access the page directly because admin role protection runs before export handling.
- No shell commands are used.
- Export actions are logged through `logAuditAction()`.

## Verification Results

PHP syntax checks passed:

- `admin/database_backup.php`
- `admin/includes/sidebar.php`

Static verification completed:

- `requireAdmin()` exists on the backup page.
- CSRF validation exists before export dispatch.
- Every export form includes a CSRF token.
- Sidebar links to `database_backup.php`.
- `mysqldump` is not used.
- Export filenames are timestamped.

## Remaining Notes

- Live download testing should be done from an authenticated admin session in XAMPP.
- Large production databases may take time to stream through PHP depending on hosting memory/time limits.
