# Phase 11.5 Local AI Assistants Report

## Files Created

- `assets/css/amsa-chatbot.css`
- `assets/js/amsa-chatbot.js`
- `PHASE_11_5_LOCAL_AI_ASSISTANTS_REPORT.md`

## Files Changed

- `index.php`
- `point/my_points.php`

## Public Assistant

- Added to `index.php`.
- Name: AMSA Assistant.
- Purpose: Help public visitors understand AMSA AIU and navigate the website.
- Uses local keyword/rule matching only.
- Includes more than 50 public question patterns covering:
  - What AMSA is
  - What AMSA does
  - Location
  - Contact email
  - Registration
  - Events & News
  - Achievements
  - Community Engagement
  - Fundraising
  - Committee
  - Developer Team
  - Website pages
  - AMSA Points
  - Contact message submission

## Points Assistant

- Added to `point/my_points.php`.
- Name: AMSA Points Assistant.
- Purpose: Help logged-in members understand the points system.
- Uses local keyword/rule matching only.
- Includes more than 70 points-system question patterns covering:
  - What AMSA Points is
  - Earning points
  - Submitting activities
  - Evidence uploads
  - Accepted profile photo types
  - Pending, approved, rejected statuses
  - Leaderboard ranking
  - Not ranked yet behavior
  - Member #ID privacy
  - Profile photo upload and crop guidance
  - Admin submission restriction
  - Admin review
  - Rejection handling
  - Approval timing
  - Request history
  - Member privacy

## Q&A Categories

- Public website navigation.
- AMSA organization information.
- Contact and registration help.
- Public content discovery.
- Points system guidance.
- Leaderboard and privacy explanation.
- Profile image guidance.

## Privacy Approach

- No OpenAI API.
- No external AI API.
- No paid service.
- No internet-dependent AI.
- No `fetch()`.
- No `XMLHttpRequest`.
- No local storage or session storage.
- No database writes.
- User messages remain only in the current browser DOM during the page session.
- The assistant does not expose private member data or admin-only data.

## Mobile Behavior

- Floating bottom-right chat button.
- Chat panel uses responsive width: `calc(100vw - 32px)` on small screens.
- Panel max height is limited so it does not awkwardly cover the whole page.
- Quick question chips wrap on mobile.

## Verification Results

- `index.php` loads the public assistant assets and initializes the `public` preset.
- `point/my_points.php` loads the shared assistant assets and initializes the `points` preset.
- Public assistant has fallback response with `amsa@student.aiu.edu.my`.
- Points assistant has fallback response with Dashboard, Submit Activity, Leaderboard, and AMSA email guidance.
- PHP syntax check passed for `index.php`.
- PHP syntax check passed for `point/my_points.php`.
- JavaScript syntax check passed for `assets/js/amsa-chatbot.js`.
- Source audit confirms no external API calls and no message storage.
