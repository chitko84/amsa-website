# Phase 11 Homepage Summary Hub Report

## Files Changed

- `index.php`

## Sections Added

- Hero copy updated to present AMSA AIU as the main homepage entry point.
- About AMSA preview with mission, vision, and student support summary.
- Top Management preview with President, Vice President, Secretary, and Treasurer cards.
- Events & News preview showing the latest 3 database records.
- Achievements preview showing the latest 3 achievement records.
- Community Engagement preview.
- Fundraising preview.
- Developer Team preview.
- AMSA Impact statistics section.
- Join AMSA AIU call-to-action section.

## Data Sources Used

- Active member count from `user`.
- Events/news count from `post` categories:
  - `news`
  - `announcement`
  - `workshop`
  - `volunteer`
  - `community_engagement`
- Community program count from `post` category `community_engagement`.
- Achievement count and latest achievements from `post` category `achievement`.
- Images from the existing `image` table through `getEventImages()`.

## Links Connected

- Explore AMSA: `about.html`
- Meet Our Committee: `committee.html`
- Learn More: `about.html`
- View Full Committee: `committee.html`
- View All Events & News: `events.php`
- View Achievements: `achievements.php`
- Explore Community Engagement: `cme.php`
- Learn About Fundraising: `fundraising.php`
- Meet The Developers: `devteam.html`
- Register: `point/register.php`
- Contact Us: `contact.html`

## Mobile Verification

- Sections use the existing Bootstrap responsive grid.
- Preview cards stack on mobile through existing `col-lg-*` and `col-md-*` classes.
- No navbar, footer, theme, or branding changes were made.

## Performance Verification

- Homepage fetches only the latest 3 events/news records.
- Homepage fetches only the latest 3 achievement records.
- Count queries use aggregate `COUNT(*)` queries.
- No full public content tables are loaded for homepage previews.

## Notes

- The existing project file is `devteam.html`, so the Developer Team button links to that file rather than `dev_team.html`.
- `img/devteam-hero.jpg` was not present locally, so the Developer Team preview uses existing `img/team-1.jpg`.
