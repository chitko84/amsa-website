# Phase 10 Final QA & Deployment Readiness Report

## Executive Summary

Final QA was completed across the AMSA public website, admin panel, and AMSA Points System.

Overall result: the project is functionally ready for AMSA production preparation, with no critical code blockers found by static QA and PHP syntax checks. Final deployment still requires environment configuration, real server testing, and live end-to-end testing with the production database.

Final readiness score: **88 / 100**

Score rationale:

- Core public/admin/points routes exist.
- Database schema includes required public, points, contact, audit, and profile-image structures.
- Admin and member access controls are in place.
- CSRF coverage exists for state-changing forms reviewed.
- Upload folders and evidence/profile protections exist.
- Backup/export center is admin-only and PHP-based.
- Remaining risk is mostly deployment configuration and live browser/server verification.

## Broken Link Audit

### Active Pages Checked

Public:

- `index.html`
- `about.html`
- `committee.html`
- `contact.html`
- `devteam.html`
- `events.php`
- `achievements.php`
- `cme.php`
- `fundraising.php`

Admin:

- `admin/login.php`
- `admin/dashboard.php`
- `admin/contact_messages.php`
- `admin/members.php`
- `admin/settings.php`
- `admin/database_backup.php`
- `admin/add_news.php`
- `admin/edit_news.php`
- `admin/add_event.php`
- `admin/edit_event.php`
- `admin/add_content.php`
- `admin/edit_content.php`

Points:

- `point/login.php`
- `point/register.php`
- `point/my_points.php`
- `point/point_request.php`
- `point/leaderboard.php`
- `point/profile.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`

### Results

- Active page static route/asset scan checked 29 core pages.
- No missing local links or assets found in active core pages.
- No obsolete `events.html`, `achievements.html`, `cme.html`, or `fundrasing.html` links found in active core pages.

### Notes

- A full all-file scan reports paths inside `admin/includes/header.php` and `admin/includes/sidebar.php` as broken when resolved from the include folder. These are false positives because those includes are loaded by admin pages and their relative paths resolve from the runtime admin page URL.
- The root-level `sidebar.php` is obsolete and not included by active admin pages, but its paths were cleaned up to avoid dead-route confusion.

## Database Audit

Required tables confirmed in `amsa_web.sql`:

- `user`
- `user_points`
- `point_category`
- `point_request`
- `point_transactions`
- `post`
- `image`
- `contact_messages`
- `audit_logs`

Required columns confirmed:

- `user.profile_image`
- `user.role`
- `user.status`
- `user.created_at`
- `contact_messages.whatsapp_number`
- `point_request.status`
- `point_request.reviewed_by`
- `point_transactions.point_request_id`

Foreign keys confirmed:

- `point_request.user_id -> user.id`
- `point_request.point_category_id -> point_category.id`
- `point_request.reviewed_by -> user.id`
- `point_transactions.user_id -> user.id`
- `point_transactions.point_request_id -> point_request.id`
- `user_points.user_id -> user.id`
- `image.post_id -> post.id`
- `audit_logs.user_id -> user.id`

Database consistency notes:

- `point_transactions.point_request_id` has a unique key to prevent double-award transactions.
- Seed data still includes a default admin account and sample point data. Replace or review seed data before production.

## User Workflow Audit

Member flow reviewed:

1. Register: `point/register.php`
2. Login: `point/login.php`
3. Upload/crop profile image: `point/profile.php`
4. Submit activity/evidence: `point/point_request.php`
5. View dashboard/history: `point/my_points.php`
6. View leaderboard: `point/leaderboard.php`
7. Logout: `point/logout.php`

Findings:

- Required routes exist.
- Forms include CSRF protection.
- Profile image workflow includes Cropper.js preview, crop, zoom, rotate, reset, and remove.
- Evidence upload is routed through `uploadEvidenceSecure()`.
- Evidence viewing uses `point/evidence.php`, not raw public file links.
- Member leaderboard remains anonymous.

Live browser testing is still required to confirm Cropper.js behavior and real uploads in the target hosting environment.

## Admin Workflow Audit

Admin flow reviewed:

1. Login: `admin/login.php`
2. Dashboard: `admin/dashboard.php`
3. Contact messages: `admin/contact_messages.php`
4. Members: `admin/members.php`
5. Approve/reject points: `point/admin_points.php`
6. Create/edit news: `admin/add_news.php`, `admin/edit_news.php`
7. Create/edit events: `admin/add_event.php`, `admin/edit_event.php`
8. Create/edit achievements/testimonials: `admin/add_content.php`, `admin/edit_content.php`
9. Export backups: `admin/database_backup.php`
10. Logout: `admin/logout.php`

Findings:

- Admin-only pages use `requireAdmin()`.
- State-changing admin forms reviewed include CSRF protection.
- Admin can view member profile images where appropriate.
- Backup/export center is admin-only and POST/CSRF protected.
- Deletes use POST where reviewed.

## Contact System Audit

Verified:

- `contact.html` posts to `contact_submit.php`.
- CSRF token is loaded from `contact_token.php`.
- Honeypot field exists.
- Required fields exist:
  - Name
  - Email
  - WhatsApp Number
  - Subject
- Optional message field exists.
- `contact_submit.php` saves `whatsapp_number`.
- Email notification logic exists:
  - PHPMailer if available.
  - `mail()` fallback.
- Official email is `amsa@student.aiu.edu.my`.

Deployment note:

- SMTP/PHPMailer configuration should be finalized on hosting. The fallback `mail()` function may not work reliably on all shared hosts.

## Security Recheck

Confirmed:

- Admin pages reviewed use `requireAdmin()`.
- Member points pages reviewed use `requireMember()`.
- Login/session helpers exist in `config/database.php`.
- CSRF helper and validation are present.
- Backup center uses admin-only access and CSRF.
- Evidence access uses `point/evidence.php` with authorization checks.
- Profile uploads validate MIME/content and size.
- `uploads/.htaccess`, `uploads/profiles/.htaccess`, and `point/uploads/eop/.htaccess` protect upload folders on Apache-compatible servers.
- Member leaderboard does not expose names, emails, or profile images.

Remaining security recommendations:

- Move database credentials out of source or into environment-specific config before production.
- Replace raw database connection failure output with friendly error handling.
- Confirm HTTPS in production so secure session cookies activate.
- Confirm server honors `.htaccess`; if using Nginx, add equivalent deny rules.

## Files & Folders Audit

Required folders confirmed:

- `uploads/`
- `uploads/profiles/`
- `point/uploads/eop/`

Protection files confirmed:

- `uploads/.htaccess`
- `uploads/profiles/.htaccess`
- `point/uploads/eop/.htaccess`

Notes:

- Existing uploaded content files are present in `uploads/`.
- Existing evidence files are present in `point/uploads/eop/`.
- No temporary build artifacts were found during file discovery.

## Production Configuration Review

Current `config/database.php` uses local XAMPP defaults:

- `DB_HOST = localhost`
- `DB_USER = root`
- `DB_PASS = ''`
- `DB_NAME = amsa_web`

These are acceptable for local XAMPP but must be changed for hosting.

Production recommendations:

- Create a production database user with a strong password and limited privileges.
- Update database credentials on the server.
- Disable raw SQL/connection error display to users.
- Confirm PHP extensions:
  - `mysqli`
  - `fileinfo`
  - `gd`
  - `json`
  - `mbstring` recommended
- Configure SMTP/PHPMailer for contact notifications.
- Enable HTTPS.
- Set correct write permissions for upload folders only.
- Remove or replace default/sample admin credentials and seed data.

## Bug Fixes Applied

Fixed during Phase 10:

- Updated missing image references in `achievements.html`.
- Added descriptive alt text for fixed achievement/testimonial images.
- Added `uploads/.htaccess` to block executable files in the main public upload folder.
- Cleaned obsolete root `sidebar.php` links so they point to real project routes instead of broken paths or `#`.

No ranking logic, points approval logic, or business workflows were changed.

## Deployment Checklist

1. Database import
   - Import updated `amsa_web.sql` into the production database.
   - Confirm all tables and foreign keys import successfully.

2. Database credentials
   - Update `config/database.php` for production host, database, user, and password.
   - Use a non-root database user.

3. Writable folders
   - Ensure PHP can write to:
     - `uploads/`
     - `uploads/profiles/`
     - `point/uploads/eop/`
   - Ensure these folders are not executable.

4. PHP extensions
   - Enable `mysqli`.
   - Enable `fileinfo`.
   - Enable `gd`.
   - Confirm `json` support.

5. SMTP/email
   - Configure PHPMailer/SMTP for `amsa@student.aiu.edu.my`.
   - Test contact form email delivery.

6. HTTPS/session security
   - Install SSL certificate.
   - Force HTTPS.
   - Confirm secure cookies are active under HTTPS.

7. Admin account
   - Create a real AMSA admin account.
   - Change or remove sample/default admin credentials.
   - Store credentials securely.

8. Backup procedures
   - Test `admin/database_backup.php`.
   - Download and restore-test a SQL backup.
   - Store backups outside the web root.

9. Final live testing
   - Test public contact form.
   - Test member register/login/profile/activity submission.
   - Test admin approval/rejection.
   - Test leaderboard member/admin views.
   - Test all CSV exports.

## Outstanding Issues

- Live browser testing was not performed from this environment.
- Live database import/run was not performed from this environment.
- CDN availability should be verified for Bootstrap, Font Awesome, and Cropper.js.
- Production credentials and SMTP settings are still local/development defaults.
- Default/sample admin seed data should be replaced before real deployment.

## Verification

Completed:

- Static active-page link scan: passed.
- Obsolete route scan: passed for active core pages.
- Full PHP syntax sweep: passed for 37 PHP files.
- Database schema marker scan: passed for required tables/columns.
- Security marker scan: completed.
- Upload folder protection check: completed.
- Backup center marker scan: confirmed no `mysqldump`, `shell_exec`, or shell export usage.

Final conclusion:

The AMSA Website and AMSA Points System are ready for deployment preparation. Before opening to real users, complete the deployment checklist and perform live browser/database testing on the actual hosting environment.
