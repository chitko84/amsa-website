# Phase 6.6 Contact System Enhancement Report

## Files Changed
- `contact.html`
- `contact_submit.php`
- `css/style.css`
- `amsa_web.sql`
- Project-wide text/code/report files containing the old AMSA email address

## Email Standardization
- Replaced the previous AMSA email address with `amsa@student.aiu.edu.my`.
- Verified the old address no longer appears in the project search results.
- Updated admin settings, public pages, SQL/report references, and contact error messaging.

## Contact Form Upgrade
Updated `contact.html` form fields:
- Name: required
- Email: required
- WhatsApp Number: required
- Subject: required
- Message: optional

Preserved:
- CSRF token loading
- Honeypot spam field
- Existing `contact_submit.php` POST action
- Existing redirect-based success/error messaging

## Contact Form UI Upgrade
Added a dark AMSA-styled contact card using maroon/gold branding:
- Dark maroon gradient card
- Large rounded inputs
- Gold submit button
- Mobile-responsive spacing
- Clear required-field placeholders

CSS added in `css/style.css`:
- `.contact-form-card`
- `.contact-form-kicker`
- `.contact-input`
- `.contact-textarea`
- `.contact-submit-btn`

## Database Changes
Updated `contact_messages` in `amsa_web.sql`:
- Added `whatsapp_number varchar(30) NOT NULL`
- Changed `message` to `text DEFAULT NULL` so the message field can be optional

Runtime compatibility:
- `contact_submit.php` checks whether `contact_messages.whatsapp_number` exists.
- If missing, it safely runs an `ALTER TABLE` to add the column before inserting the submission.

## Contact Submission Storage
Updated `contact_submit.php` to save:
- Name
- Email
- WhatsApp Number
- Subject
- Message
- Submission Date

Validation added:
- Required WhatsApp field
- WhatsApp format whitelist for numbers, spaces, plus, dash, parentheses, and periods
- Existing email validation
- Existing length limits
- Optional message with max length

## Email Notification
Notification recipient:
- `amsa@student.aiu.edu.my`

Email subject format:
- `[AMSA Website Contact Form] {Subject}`

Email body includes:
- Name
- Email
- WhatsApp Number
- Subject
- Message
- Submission Date

Mailer behavior:
- Uses PHPMailer automatically if `vendor/autoload.php` and PHPMailer are available.
- Falls back to PHP `mail()` if PHPMailer is not installed.
- Database submission is not blocked if email delivery fails; failures are logged with `error_log()`.

## Verification Results
- Old AMSA email search: no remaining old-address results.
- New AMSA email appears in contact page, admin settings, and contact submit handler.
- `amsa_web.sql` includes `contact_messages.whatsapp_number`.
- `contact_submit.php` inserts `whatsapp_number`.
- Contact form includes all required fields and keeps CSRF/honeypot protection.
- PHP syntax checks completed for edited PHP files.

## Remaining Notes
- PHPMailer is not currently present in the project.
- For reliable production email delivery, install/configure PHPMailer with SMTP credentials in a later deployment/configuration phase.
