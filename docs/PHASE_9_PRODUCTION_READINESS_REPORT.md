# Phase 9 Production Readiness Report

## Profile Image Management

### Files Changed

- `config/database.php`
- `amsa_web.sql`
- `point/profile.php`
- `point/points-style.css`
- `point/my_points.php`
- `point/point_request.php`
- `point/leaderboard.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`
- `admin/members.php`
- `admin/dashboard.php`
- `admin/admin-style.css`
- `uploads/profiles/.htaccess`

### Database Changes

- Added `profile_image varchar(255) DEFAULT NULL` to the `user` table in `amsa_web.sql`.
- Updated the sample `INSERT INTO user` column list to include `profile_image`.
- Added `ensureProfileImageColumn()` in `config/database.php` so an existing local database can be upgraded safely before profile-image-aware queries run.

### Default Avatar

- Existing default avatar used: `img/user.jpg`.
- No new default avatar file was needed.
- `profileImageUrl()` falls back to `img/user.jpg` when a member has no uploaded image or the stored file is missing.

### Profile Page

- Created `point/profile.php`.
- Member/admin points navigation now includes:
  - Dashboard
  - Submit Activity
  - Leaderboard
  - Profile
  - Logout
- Profile page displays:
  - Name
  - Email
  - User ID
  - Status
  - Total Points
  - Profile image

### Cropper.js Integration

- Added Cropper.js by CDN:
  - `https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css`
  - `https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js`
- Implemented:
  - Live preview
  - Crop
  - Zoom in
  - Zoom out
  - Rotate left
  - Rotate right
  - Reset
  - Required preview before saving
- The save button stays disabled until a cropped preview is generated.
- Crop changes invalidate the saved preview so users must preview again before saving.

### Upload Security

- Storage folder: `uploads/profiles/`.
- Added `uploads/profiles/.htaccess` to block executable/script file access.
- Server-side validation includes:
  - CSRF validation
  - Data URL image validation
  - MIME validation with `finfo`
  - Valid image validation with GD `imagecreatefromstring`
  - Max cropped image size: 3MB
  - Allowed image types: JPG, JPEG, PNG, WEBP
  - Random filenames
  - Old uploaded profile image deletion on replacement/removal
- Uploaded profile images are stored as relative paths such as `uploads/profiles/random-name.jpg`.

### Admin Visibility

Admins can now see profile images in:

- `admin/members.php`
- `point/admin_points.php`
- `admin/dashboard.php` pending request previews
- `point/leaderboard.php` admin view

Admin-visible member information includes image, name, email, status, and points where the page already supports those fields.

### Privacy Handling

- Member leaderboard remains anonymous.
- Normal members do not see:
  - Full names
  - Emails
  - Profile images
- Member leaderboard still displays:
  - `Member #ID`
  - `You - Member #ID` for the current member
- Admin leaderboard still displays full member details and profile images.
- Ranking logic was not changed.

## Backup Center

### Files Created

- `admin/database_backup.php`

### Files Changed

- `admin/includes/sidebar.php`
- `admin/database_backup.php`

### Export Types

- Full database SQL export:
  - Structure and data
  - Filename: `amsa_backup_YYYY_MM_DD_HHMMSS.sql`
- Contact messages CSV:
  - Name, Email, WhatsApp Number, Subject, Message, Submission Date
- Points report CSV:
  - User ID, Name, Email, Total Points, Approved Requests, Pending Requests, Rejected Requests
- Leaderboard CSV:
  - Rank, User ID, Name, Email, Total Points
- Members CSV:
  - User ID, Name, Email, Role, Status, Registration Date

### Security Measures

- Backup page uses `requireAdmin('login.php')`.
- Export actions are POST-only.
- Every export form includes `csrfInput()`.
- Export dispatch validates `verifyCsrfToken()`.
- No `mysqldump`, `shell_exec`, or shell export command is used.
- Export actions are logged through `logAuditAction()`.
- Sidebar System section includes:
  - Settings
  - Contact Messages
  - Database Backup

## Production Review

### Risks Found

- `config/database.php` still uses local XAMPP defaults:
  - Host: `localhost`
  - User: `root`
  - Blank password
- Database connection failure currently uses `die()` with raw connection text.
- CDN dependencies are used for Bootstrap/Cropper on some pages; production availability depends on external CDN access.
- Existing `uploads/` contains public content images and should remain writable but monitored.
- `uploads/profiles/` now exists and has a script-blocking `.htaccess`, but production hosting should still restrict executable handling at the server level.
- Live browser/session verification was not performed in this environment.

### Recommendations

- Move database credentials to environment-specific configuration before hosting migration.
- Replace raw connection failure output with a friendly error page and server-side logging.
- Confirm production PHP has `fileinfo` and GD enabled.
- Confirm `uploads/`, `uploads/profiles/`, and `point/uploads/eop/` are writable by PHP but not executable.
- Test all export downloads from a real authenticated admin session.
- Test Cropper.js profile upload on desktop and mobile browsers.
- Keep database backups outside the web root after downloading.

## Verification

PHP syntax checks passed:

- `config/database.php`
- `point/profile.php`
- `point/my_points.php`
- `point/point_request.php`
- `point/leaderboard.php`
- `point/admin_points.php`
- `point/point_categories_admin.php`
- `admin/members.php`
- `admin/dashboard.php`
- `admin/database_backup.php`
- `admin/includes/sidebar.php`

Static verification completed:

- `profile_image` exists in `amsa_web.sql`.
- Profile page requires authenticated member/admin access.
- Profile image upload and removal use CSRF validation.
- Cropper.js CDN links are present.
- Zoom, rotate, reset, preview, and save controls are present.
- `uploads/profiles/.htaccess` exists.
- Admin pages resolve profile images with `profileImageUrl()`.
- Member leaderboard profile images are inside admin-only branches.
- Member leaderboard still uses anonymized identity labels.
- Backup center uses `requireAdmin()`.
- Backup exports use CSRF-protected POST forms.
- Backup center does not use `mysqldump`, `shell_exec`, or shell export commands.

Live verification still recommended:

- Register/login as member.
- Open `point/profile.php`.
- Upload, crop, zoom, rotate, preview, save, replace, and remove a profile image.
- Confirm admin pages display member images.
- Confirm member leaderboard remains anonymous.
- Download each backup/export file from an authenticated admin session.
