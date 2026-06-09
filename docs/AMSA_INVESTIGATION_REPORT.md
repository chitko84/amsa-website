# AMSA Website Investigation Report

Investigation date: 2026-06-04  
Scope: Full source audit of `C:\xampp\htdocs\amsa-website - Copy` and database schema audit of `amsa_web.sql`.  
Phase: Phase 1 - Investigation only. No source code was modified.

## Executive Summary

- Total Pages / Web Files: 28
- Total PHP Files: 20
- Total HTML Files: 8
- Total Database Tables: 6
- Total Critical Issues: 5
- Total High Issues: 8
- Total Medium Issues: 10
- Total Low Issues: 5

The project is not production-ready yet. The public website is partly static and partly database-driven, with duplicate `.html` and `.php` pages, inconsistent navigation, missing pages/assets, placeholder data, and several broken runtime paths. The AMSA Points System is currently not functional because the point pages include the wrong database/helper file and fall back to user ID `1` when no session exists. Admin workflows are partially implemented but lack role separation, CSRF protection, strict upload validation, and complete edit routes.

## A. Website Structure

### Folders

- `.git/`
- `.vscode/`
- `admin/`
- `config/`
- `css/`
- `img/`
- `js/`
- `lib/animate/`
- `lib/counterup/`
- `lib/easing/`
- `lib/owlcarousel/`
- `lib/owlcarousel/assets/`
- `lib/waypoints/`
- `lib/wow/`
- `point/`
- `point/uploads/eop/`
- `uploads/`

### Pages And Web Files

| File | Type | Status |
| --- | --- | --- |
| `index.html` | Public HTML | Static public home page |
| `about.html` | Public HTML | Static public page |
| `events.html` | Public HTML | Static duplicate/obsolete candidate |
| `events.php` | Public PHP | Intended DB-driven events/news page, currently fatal due missing function |
| `achievements.html` | Public HTML | Static duplicate/obsolete candidate with missing assets |
| `achievements.php` | Public PHP | DB-driven achievements/testimonials page |
| `cme.html` | Public HTML | Empty/obsolete page |
| `cme.php` | Public PHP | DB-driven community engagement page |
| `committee.html` | Public HTML | Static committee page |
| `contact.html` | Public HTML | Static contact page, form not wired |
| `devteam.html` | Public HTML | Static developer/team page |
| `admin/login.php` | Admin PHP | Admin login |
| `admin/logout.php` | Admin PHP | Logout |
| `admin/dashboard.php` | Admin PHP | Admin content dashboard |
| `admin/add_event.php` | Admin PHP | Add community engagement post |
| `admin/edit_event.php` | Admin PHP | Edit community engagement post |
| `admin/add_news.php` | Admin PHP | Add news/announcement/etc. |
| `admin/edit_news.php` | Admin PHP | Edit news/announcement/etc. |
| `admin/add_content.php` | Admin PHP | Add achievement/testimonial |
| `admin/delete_content.php` | Admin PHP | Delete achievement/testimonial |
| `point/point_request.php` | Points PHP | Member request form, currently fatal |
| `point/my_points.php` | Points PHP | Member dashboard, currently fatal |
| `point/admin_points.php` | Points PHP | Admin review page, currently fatal |
| `point/point_categories_admin.php` | Points PHP | Category admin page |
| `config/database.php` | Include PHP | MySQLi connection and public/admin helpers |
| `configdatabase.php` | Include PHP | Unused PDO connection and points helpers |
| `sidebar.php` | Include PHP/HTML | Admin sidebar include candidate, not used consistently |
| `lib/waypoints/links.php` | Library PHP | Third-party/library file |

### Duplicate / Obsolete / Unfinished Pages

- `events.html` duplicates the intended purpose of `events.php`; navigation points to both.
- `achievements.html` duplicates the intended purpose of `achievements.php`; navigation points to both.
- `cme.html` is empty and should be treated as obsolete unless intentionally reserved.
- `configdatabase.php` duplicates database connection responsibility and contains point helpers, but point pages include `config/database.php`.
- `sidebar.php` says it should be included in all admin pages, but admin pages define their own sidebars instead.

## B. Navigation, Footer, Sidebar, And Link Audit

### Issue 1: Database-driven Events Page Calls Missing Function

- File Path: `events.php:4`
- Severity: Critical
- Description: `events.php` calls `getAllNewsAndEvents()`, but no such function exists in any PHP file.
- Root Cause: The page was converted to database-driven content without adding the helper to `config/database.php`.
- Recommended Fix: Add a single canonical helper for news/events posts or update `events.php` to use existing helpers/queries.

### Issue 2: Points Pages Include Wrong Helper File

- File Path: `point/point_request.php:3`, `point/my_points.php:3`, `point/admin_points.php:3`
- Severity: Critical
- Description: These pages require `../config/database.php`, but call `getAllPointCategories()`, `getUserPoints()`, `getUserPointRequests()`, `getAllPointRequests()`, `getPointStatistics()`, `createPointRequest()`, and `updatePointRequestStatus()`. These functions exist only in `configdatabase.php`.
- Root Cause: Two competing database config files exist, and the point-system helper functions were placed in the unused root config file.
- Recommended Fix: Consolidate database configuration and helper functions into one canonical include, preferably `config/database.php`, then update all point pages consistently.

### Issue 3: Public Navigation Points To Static Pages Instead Of PHP Pages

- File Path: `achievements.php:312`, `cme.php:251`, `contact.html:77`, `index.html`, `events.html`, `about.html`, `committee.html`, `devteam.html`
- Severity: High
- Description: Navigation uses `events.html`, `achievements.html`, and `cme.html` in multiple places even though `events.php`, `achievements.php`, and `cme.php` are the intended database-driven pages.
- Root Cause: Static template pages and PHP pages coexist without a canonical routing decision.
- Recommended Fix: Decide canonical public routes, then update every navbar/footer to point to the canonical PHP pages or convert all pages to PHP consistently.

### Issue 4: Missing Fundraising Page Linked Across Site

- File Path: Multiple nav/footer files, including `index.html`, `about.html`, `events.php`, `cme.php`, `achievements.php`, `committee.html`, `contact.html`, `devteam.html`
- Severity: High
- Description: `fundrasing.html` is linked repeatedly, but the file does not exist. The label is also misspelled as "Fundrasing".
- Root Cause: Placeholder project navigation was added before the page was created.
- Recommended Fix: Create the real fundraising page or remove the route. Use a consistent filename such as `fundraising.php`.

### Issue 5: Register Button Is A Dead Link

- File Path: Public navbars, including `events.php:420`, `cme.php:270`, `achievements.php:331`, `contact.html:96`
- Severity: High
- Description: The primary `Register` CTA links to `#` and does not open a real registration form.
- Root Cause: Registration workflow has not been implemented or connected.
- Recommended Fix: Build a database-backed registration workflow and point every CTA to it.

### Issue 6: Footer Links Are Placeholder Or Wrong Destinations

- File Path: `contact.html:244-262`, `events.html:256-274`, `cme.php:466-484`, `achievements.php:676-694`
- Severity: Medium
- Description: Footers contain `#` placeholders and generic labels like "Our Services" and "Latest Blog" that do not match the AMSA site structure.
- Root Cause: Template footer content was copied between pages without final routing.
- Recommended Fix: Create a shared footer include and define one production navigation map.

### Issue 7: Admin Sidebar Include Is Not Integrated

- File Path: `sidebar.php:1`, `admin/*.php`
- Severity: Medium
- Description: `sidebar.php` says it should be included in all admin pages, but admin pages maintain duplicate hardcoded sidebars.
- Root Cause: Shared layout extraction was started but not completed.
- Recommended Fix: Refactor admin pages to use a single sidebar include after fixing its relative paths.

### Issue 8: Sidebar Relative Paths Are Wrong When Used From Root

- File Path: `sidebar.php:223-257`
- Severity: Medium
- Description: `sidebar.php` uses paths such as `../img/logo.png`, `dashboard.php`, and `../point/...`; these are only correct from some include locations and wrong from others.
- Root Cause: The include was written without a base path or route helper.
- Recommended Fix: Use a base URL constant or pass active section data into the include.

## C. Database Analysis

### Tables

| Table | Primary Key | Foreign Keys / Relationships |
| --- | --- | --- |
| `user` | `id` | Referenced by `post.uploaded_by`, `post.edited_by`, `point_request.user_id`, `point_request.reviewed_by`, `user_points.user_id` |
| `post` | `id` | `uploaded_by -> user.id`, `edited_by -> user.id` |
| `image` | `id` | `post_id -> post.id ON DELETE CASCADE` |
| `point_category` | `id` | Referenced by `point_request.point_category_id` |
| `point_request` | `id` | `user_id -> user.id`, `point_category_id -> point_category.id`, `reviewed_by -> user.id` |
| `user_points` | `id` | `user_id -> user.id`, unique `user_id` |

### Issue 9: Wrong Database Name In Unused Config File

- File Path: `configdatabase.php:3`
- Severity: Critical
- Description: `configdatabase.php` connects to database `amsa_website`, while the provided schema is `amsa_web`.
- Root Cause: Old database naming remained after schema export or project copy.
- Recommended Fix: Remove the duplicate config or align it to `amsa_web` before using it.

### Issue 10: User Table Mixes Admin And Member Concepts

- File Path: `amsa_web.sql:151-156`
- Severity: High
- Description: The `user` table has no `role`, `status`, `created_at`, or member profile fields, yet it is used for admin login and points membership.
- Root Cause: Minimal admin account schema was reused as member schema.
- Recommended Fix: Add roles and member profile columns, or split `admin_users` and `members` depending on required access rules.

### Issue 11: No Database-Level Post Category Constraint

- File Path: `amsa_web.sql:121`
- Severity: Medium
- Description: `post.category` is a free-form `varchar(100)`, while code depends on exact categories like `community_engagement`, `achievement`, `testimonial`, `news`, `announcement`, `workshop`, and `volunteer`.
- Root Cause: Category values are controlled only by application code.
- Recommended Fix: Add a category lookup table or an enum/check constraint and normalize admin form options.

### Issue 12: Images Can Exist Without Required Names Or Post IDs

- File Path: `amsa_web.sql:31-33`
- Severity: Medium
- Description: `image.post_id` and `image.img_name` are nullable, but the application assumes an image belongs to a post and has a filename.
- Root Cause: Schema permits incomplete image rows.
- Recommended Fix: Make `post_id` and `img_name` `NOT NULL` and keep `ON DELETE CASCADE`.

### Issue 13: Point Category Delete Cascades Historical Requests

- File Path: `amsa_web.sql:287-289`, `point/point_categories_admin.php:40`
- Severity: High
- Description: Deleting a point category cascades and deletes all related point requests, removing member history and audit records.
- Root Cause: Hard delete is exposed in admin UI while FK uses `ON DELETE CASCADE`.
- Recommended Fix: Use soft deactivation only, or restrict deletion when requests exist.

### Issue 14: Seed Data Contains Demo And Invalid Production Content

- File Path: `amsa_web.sql:74-82`, `amsa_web.sql:133-143`, `amsa_web.sql:107-110`
- Severity: High
- Description: Seeded categories include `mkm` and `web dev` with 1000 points, posts include lorem ipsum and test text, and point requests include casual/dummy descriptions.
- Root Cause: Development/test data was exported into the production schema file.
- Recommended Fix: Separate schema migrations from seed/demo data and clean production seed content.

### Issue 15: Points Are Stored As A Single Aggregate Without Ledger

- File Path: `amsa_web.sql:172-176`, `configdatabase.php:108-113`
- Severity: High
- Description: `user_points.total_points` is incremented directly, but there is no immutable points transaction/ledger table.
- Root Cause: The system stores current totals only.
- Recommended Fix: Add a `point_transactions` table tied to approved requests, then calculate or reconcile totals from the ledger.

### Issue 16: Approval Can Double-Award Points

- File Path: `configdatabase.php:77-114`
- Severity: Critical
- Description: `updatePointRequestStatus()` increments user points whenever status is submitted as `approved`, without checking previous status. Re-approving an already approved request can add points again.
- Root Cause: Approval logic does not enforce a valid state transition from `pending` to `approved`.
- Recommended Fix: Update only pending requests, enforce idempotency, and record transactions with a unique `point_request_id`.

## D. PHP Query And Database Compatibility Audit

### Issue 17: Point Helper Functions Are In PDO File But Pages Use MySQLi Config

- File Path: `configdatabase.php:7-185`, `point/*.php`
- Severity: Critical
- Description: The point workflow cannot run because the required functions are not loaded. If loaded, it would also use PDO while the rest of the app uses MySQLi.
- Root Cause: Two database abstraction styles were mixed.
- Recommended Fix: Standardize on one database layer and move point functions into the active config/service layer.

### Issue 18: Admin Dashboard Links To Missing `edit_content.php`

- File Path: `admin/dashboard.php:497`, `admin/dashboard.php:542`
- Severity: High
- Description: Achievement and testimonial rows link to `edit_content.php`, but no such file exists.
- Root Cause: Add/delete content routes were implemented, but edit route was not.
- Recommended Fix: Add `admin/edit_content.php` or route these records through existing edit screens.

### Issue 19: News/Event Category Usage Is Inconsistent

- File Path: `admin/add_news.php:249`, `admin/edit_news.php:20`, `events.php:6-7`
- Severity: Medium
- Description: The event/news page filters only `news` and `community_engagement` for counts, while admin news supports `announcement`, `workshop`, `volunteer`, and `community_engagement`.
- Root Cause: Content category taxonomy is not centralized.
- Recommended Fix: Define one category map and use it across admin forms, public filters, reports, and database constraints.

### Issue 20: `getAllTestimonials()` Uses Non-Portable Grouping

- File Path: `config/database.php:91-95`
- Severity: Medium
- Description: The query selects `p.*` while grouping by only `p.title, p.content`. This can fail under stricter SQL modes such as `ONLY_FULL_GROUP_BY`.
- Root Cause: Duplicate prevention was implemented through SQL grouping instead of a deterministic unique rule.
- Recommended Fix: Use `SELECT MIN(id)` subquery or enforce uniqueness at insert/update time.

### Issue 21: Public Content Is Not Fully Database-Driven

- File Path: `index.html`, `about.html`, `committee.html`, `contact.html`, `devteam.html`
- Severity: Medium
- Description: Major public pages remain static HTML and are not connected to database content or shared includes.
- Root Cause: Only some sections were converted to PHP.
- Recommended Fix: Convert public pages that need dynamic content to PHP and use shared nav/footer/components.

## D. AMSA Points System Workflow Audit

Expected workflow:

Member -> Submit Activity -> Upload Evidence -> Admin Review -> Admin Approve/Reject -> Points Awarded -> Leaderboard Updated

Actual status:

- Member login: Missing. Points pages silently use user ID `1`.
- Submit activity: Form exists but page currently fatal due missing helper include.
- Upload evidence: Basic upload exists but validation is weak and UI accepts only `.pdf` while existing data contains images.
- Admin review: Page exists but currently fatal due missing helper include and no admin authorization.
- Approve/reject: Helper exists in wrong file and double-award risk exists.
- Points awarded: Aggregate update exists in wrong file; no ledger.
- Leaderboard: Not implemented as a member-facing page.
- Ranking: Not implemented.
- Member dashboard: Exists but currently fatal due missing helper include and fake user fallback.
- Admin dashboard: Separate from main admin dashboard and not protected by role checks.

### Issue 22: Points Pages Use Fake Logged-In User Fallback

- File Path: `point/point_request.php:6`, `point/my_points.php:5`, `point/admin_points.php:6`, `point/point_categories_admin.php:6`
- Severity: Critical
- Description: If no session exists, pages default to user ID `1`, which is the admin account in the seed data.
- Root Cause: Login enforcement was bypassed during development.
- Recommended Fix: Require authenticated sessions and redirect unauthenticated users to login. Never infer a user ID.

### Issue 23: No Member Login Or Registration Workflow

- File Path: `point/*.php`, `admin/login.php`
- Severity: High
- Description: Only admin login exists. Members cannot register or log in separately, yet the points system is member-based.
- Root Cause: Authentication scope only covered admin.
- Recommended Fix: Implement member registration/login/logout/session handling and role-aware authorization.

### Issue 24: No Leaderboard Or Ranking Page

- File Path: `point/`
- Severity: High
- Description: The project goal includes leaderboard and member ranking, but no leaderboard/ranking page exists.
- Root Cause: Points MVP stopped at request history/admin approval.
- Recommended Fix: Add a `leaderboard.php` or equivalent route using `user_points` or a transaction-derived total.

### Issue 25: Evidence Upload Rules Are Contradictory

- File Path: `point/point_request.php:128`, `point/admin_points.php:199-204`, `amsa_web.sql:107-110`
- Severity: High
- Description: The member form accepts only `.pdf`, admin preview supports images, and seed data contains PNG evidence.
- Root Cause: Upload requirements were not finalized.
- Recommended Fix: Define allowed evidence types and enforce them server-side and client-side consistently.

### Issue 26: No Server-Side File Type, Size, Or MIME Validation

- File Path: `point/point_request.php:16-25`, `admin/add_event.php:22-35`, `admin/add_news.php:33-49`, `admin/add_content.php:42-52`
- Severity: High
- Description: Upload code trusts filenames and browser `accept` attributes, with no MIME sniffing, extension whitelist, file size limit, or randomized safe names.
- Root Cause: Upload handling is minimal development code.
- Recommended Fix: Validate extension, MIME, size, and upload errors server-side. Store randomized filenames outside executable paths when possible.

### Issue 27: Points Admin Pages Lack Admin Authorization

- File Path: `point/admin_points.php:5-6`, `point/point_categories_admin.php:5-6`
- Severity: High
- Description: Admin points pages do not verify that the session user is an admin.
- Root Cause: Roles do not exist in schema or session checks.
- Recommended Fix: Add roles and require admin role before loading review/category management pages.

## E. Page Content Audit

### Issue 28: Empty CME HTML Page

- File Path: `cme.html`
- Severity: Medium
- Description: The file is empty but linked from some public navigation.
- Root Cause: The page was likely replaced by `cme.php` but not removed from navigation.
- Recommended Fix: Stop linking to `cme.html`; either remove it later or redirect to `cme.php`.

### Issue 29: Placeholder Public Forms Are Not Functional

- File Path: `contact.html:161-170`, newsletter forms in public footers
- Severity: Medium
- Description: Contact and newsletter forms have no method/action processing, database table, validation, or email delivery.
- Root Cause: Template forms were left as static UI.
- Recommended Fix: Implement contact/subscription endpoints, persistence, validation, spam protection, and user feedback.

### Issue 30: Placeholder Social Links

- File Path: Multiple public pages, including `contact.html:53-57`, `cme.php:228-232`, `events.php:380-384`
- Severity: Low
- Description: Social icons link to `#` or empty strings.
- Root Cause: Real AMSA social URLs were not configured.
- Recommended Fix: Add real URLs or remove the icons until official links are available.

### Issue 31: Missing Achievement Assets

- File Path: `achievements.html`, `achievements.php:621`
- Severity: Medium
- Description: References to `img/achievement-1.jpg`, `img/achievement-2.jpg`, `img/achievement-3.jpg`, and `img/cta-achievements.png` do not exist.
- Root Cause: Template/image names do not match actual assets.
- Recommended Fix: Replace with real assets or database images.

### Issue 32: External Placeholder Images Used As Fallbacks

- File Path: `cme.php:318`, `cme.php:380`, `achievements.php:442`
- Severity: Low
- Description: Fallbacks use `https://picsum.photos/...`, which is generic placeholder media.
- Root Cause: Production fallback assets were not provided.
- Recommended Fix: Use branded local fallback images or require uploaded images for published posts.

## F. File Dependency Audit

### Issue 33: Duplicate SQL Dump Present

- File Path: `amsa_web.sql`, `amsa_website (2).sql`
- Severity: Low
- Description: Two SQL dumps exist in the project root, which can cause confusion about the canonical schema.
- Root Cause: Historical export was kept beside the intended schema.
- Recommended Fix: Keep one canonical schema/migration source and archive/remove obsolete dumps after review.

### Issue 34: Uploaded Files Are Stored Under Web-Accessible Paths

- File Path: `uploads/`, `point/uploads/eop/`
- Severity: High
- Description: User/admin uploads are directly web-accessible and may be served without access checks.
- Root Cause: Upload storage is under public web root.
- Recommended Fix: Store private evidence outside public root or serve through an authorization-checking download controller.

### Issue 35: No Central Shared Layout For Public Pages

- File Path: Public `.html`/`.php` pages
- Severity: Medium
- Description: Navbars and footers are duplicated across pages, causing inconsistent routes and labels.
- Root Cause: Static template code was copied page by page.
- Recommended Fix: Convert to PHP includes/components for header, navbar, footer, scripts, and shared metadata.

## G. Production Readiness Audit

### Issue 36: No CSRF Protection On State-Changing Forms

- File Path: `admin/add_event.php`, `admin/add_news.php`, `admin/edit_event.php`, `admin/edit_news.php`, `admin/add_content.php`, `admin/delete_content.php`, `point/admin_points.php`, `point/point_categories_admin.php`, `point/point_request.php`
- Severity: High
- Description: Forms that create, edit, delete, approve, or upload data do not use CSRF tokens.
- Root Cause: Session security and form security were not implemented.
- Recommended Fix: Add CSRF token generation/validation to all POST actions and avoid GET deletes.

### Issue 37: GET-Based Deletes Are Used

- File Path: `admin/dashboard.php:500`, `admin/dashboard.php:545`, `admin/delete_content.php`, `admin/dashboard.php:29`
- Severity: High
- Description: Delete operations can be triggered through links/GET routes.
- Root Cause: Admin actions were implemented as direct links for convenience.
- Recommended Fix: Use POST-only delete forms with CSRF tokens and confirmation.

### Issue 38: Session Hardening Is Missing

- File Path: `admin/login.php`, `config/database.php:19-22`
- Severity: Medium
- Description: Login does not call `session_regenerate_id(true)`, and session cookie flags are not configured.
- Root Cause: Basic PHP session defaults are used.
- Recommended Fix: Regenerate session ID after login and configure secure, httponly, samesite cookies for production HTTPS.

### Issue 39: Database Credentials Are Hardcoded

- File Path: `config/database.php:3-6`, `configdatabase.php:2-5`
- Severity: Medium
- Description: Database host/user/password/name are hardcoded in tracked PHP files.
- Root Cause: No environment-specific configuration layer exists.
- Recommended Fix: Move secrets/configuration to environment variables or a non-public deployment config.

### Issue 40: Error Handling Leaks Connection Failure Details

- File Path: `config/database.php:12-13`, `configdatabase.php:11-12`
- Severity: Medium
- Description: Connection failures call `die()` with raw error text.
- Root Cause: Development-style error handling remains.
- Recommended Fix: Log detailed errors server-side and show a generic production error page.

### Issue 41: Admin/User Authorization Model Is Missing

- File Path: `amsa_web.sql:151-156`, `admin/*.php`, `point/*.php`
- Severity: High
- Description: The app cannot distinguish admins from members in the database or consistently in sessions.
- Root Cause: No role/status model exists.
- Recommended Fix: Add roles/permissions and enforce them in middleware/includes before page logic.

### Issue 42: No Production Database Migration Strategy

- File Path: `amsa_web.sql`
- Severity: Medium
- Description: The schema is a phpMyAdmin dump with development seed data, not a versioned migration set.
- Root Cause: Database lifecycle is dump-based.
- Recommended Fix: Create versioned migrations and separate seeders for local/demo/production data.

### Issue 43: No Audit Trail For Admin Actions

- File Path: `admin/*.php`, `point/admin_points.php`
- Severity: Medium
- Description: Admin content changes, deletes, approvals, and category edits are not recorded in an audit log.
- Root Cause: Schema lacks audit/event tables.
- Recommended Fix: Add audit logging for sensitive admin actions.

### Issue 44: No Database Performance Indexes For Common Public Queries

- File Path: `amsa_web.sql`
- Severity: Low
- Description: `post.category`, `post.upload_date`, `point_request.status`, and `point_request.request_date` are not indexed for common filtering/sorting patterns.
- Root Cause: Only FK indexes were exported.
- Recommended Fix: Add composite indexes such as `(category, upload_date)`, `(status, request_date)`, and leaderboard-oriented indexes.

### Issue 45: HTML/PHP Output Escaping Is Inconsistent

- File Path: `admin/edit_event.php:246`, `point/admin_points.php:152`, `point/admin_points.php:201-203`
- Severity: Medium
- Description: Some dynamic filenames/emails/URLs are output without `htmlspecialchars()`.
- Root Cause: Escaping is applied manually and inconsistently.
- Recommended Fix: Use consistent output helper functions for HTML text, attributes, and URLs.

### Issue 46: Content Admin Allows Publishing Without Moderation State

- File Path: `amsa_web.sql:118-126`, `admin/add_event.php`, `admin/add_news.php`, `admin/add_content.php`
- Severity: Medium
- Description: Posts have no `status` or publish scheduling fields; all inserted content is immediately public.
- Root Cause: `post` schema only stores category/content/title/dates.
- Recommended Fix: Add `status`, `published_at`, and optional draft/review flow.

### Issue 47: Contact Page Has Invalid Closing Anchor Markup

- File Path: `contact.html:275`
- Severity: Low
- Description: The copyright paragraph contains an extra closing `</a>`.
- Root Cause: Template markup was copied with malformed closing tags.
- Recommended Fix: Validate HTML and fix markup across pages.

### Issue 48: Admin Dashboard Contains Leftover Developer Comments/Fragments

- File Path: `admin/dashboard.php:555-579`
- Severity: Low
- Description: The file contains visible developer comments/fragments such as "Add this button..." and a duplicate testimonial title cell fragment after the main content.
- Root Cause: Development notes were left in the PHP template.
- Recommended Fix: Remove stray comments/fragments during cleanup.

## Broken/Missing Local References Summary

Confirmed missing local static references include:

- `fundrasing.html` linked across public nav/footer, missing.
- `img/achievement-1.jpg`, `img/achievement-2.jpg`, `img/achievement-3.jpg`, missing.
- `img/cta-achievements.png`, missing.
- `psk.jpg.jpeg` and `knz(1).jpg.jpeg` referenced without the `img/` prefix in `achievements.html`, missing at that path.
- `admin/edit_content.php`, missing.

## Database/PHP Query Cross-Check Summary

- No PHP query was found referencing a non-existing table from the provided schema.
- No PHP query was found referencing a clearly non-existing column from the provided schema.
- The main query failures are missing helper functions, wrong config inclusion, inconsistent database name, and incomplete routes.
- FK relationships exist for current tables, but production-grade constraints are incomplete for post categories, image required fields, roles, audit trails, point transactions, and publication status.

## AMSA Points System Functional Gap Summary

| Workflow Step | Status |
| --- | --- |
| Member login | Missing |
| Member registration | Missing |
| Submit activity | UI exists, currently broken by missing helpers |
| Upload evidence | Basic upload exists, insecure/inconsistent |
| Admin review | UI exists, currently broken by missing helpers |
| Approve/reject | Helper exists in wrong file, double-award risk |
| Points awarded | Aggregate exists, no ledger/idempotency |
| Leaderboard | Missing |
| Ranking | Missing |
| Activity history | Basic history exists, currently broken by missing helpers |

## Production Roadmap

### Phase 2 - Navigation Fixes

1. Decide canonical public routes (`.php` vs `.html`).
2. Replace all `events.html`, `achievements.html`, and `cme.html` links with canonical database-driven pages.
3. Resolve or remove `fundrasing.html`.
4. Create shared navbar/footer includes.
5. Connect Register, Contact, newsletter, and social links to real routes.

### Phase 3 - Database Fixes

1. Consolidate `config/database.php` and `configdatabase.php`.
2. Align database name to `amsa_web`.
3. Add roles/member fields or split admin/member tables.
4. Add post status/category constraints.
5. Add points transaction ledger and audit tables.
6. Clean demo seed data and move to separate seed files.

### Phase 4 - AMSA Points System Fixes

1. Implement member registration/login/logout.
2. Enforce authenticated member sessions on member pages.
3. Enforce admin role on points admin pages.
4. Fix helper loading and database layer consistency.
5. Make approval idempotent and ledger-backed.
6. Build leaderboard/ranking pages.
7. Standardize evidence upload rules.

### Phase 5 - UI/UX Consistency

1. Convert repeated layout to shared components.
2. Remove static duplicate/obsolete pages.
3. Replace placeholder copy/images with real AMSA content.
4. Standardize admin and points module styling.
5. Add empty states, success/error states, and responsive table behavior.

### Phase 6 - Security Hardening

1. Add CSRF tokens to all state-changing forms.
2. Replace GET deletes with POST deletes.
3. Harden sessions and regenerate session ID on login.
4. Validate uploads by MIME, extension, size, and random filename.
5. Store private evidence outside public web root or serve through controller checks.
6. Centralize output escaping.
7. Move config/secrets out of tracked PHP files.

### Phase 7 - Production Readiness

1. Add environment configuration and deployment checklist.
2. Add database migrations and production seed process.
3. Add error logging and generic production error pages.
4. Add automated smoke tests for public/admin/points workflows.
5. Add backup/restore process for database and uploads.
6. Add performance indexes and test with realistic data volumes.
7. Final QA pass for broken links, missing assets, and role-based access.

