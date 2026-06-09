# Hero Image Visibility Fix Report

## Files Changed

- `css/style.css`

## Fix Summary

- Reduced hero overlay strength from dark/red-tinted overlay to a black transparent overlay:
  - `linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45))`
- Removed the old AMSA watermark-only hero background from the shared `.bg-header.amsa-page-header` rule.
- Ensured hero backgrounds use:
  - `background-size: cover`
  - `background-position: center center`
  - `background-repeat: no-repeat`
- Dev Team no longer uses the cropped `team-1.jpg` person image.

## Final Hero Mappings

- `about.html`: `img/Culture Night.jpeg`
- `committee.html`: `img/Mystery_of_Burma.jpeg`
- `achievements.php`: `img/Culture_Night.jpeg`
- `contact.html`: `img/Culture Night.jpeg`
- `devteam.html`: `img/Culture Night.jpeg`
- `cme.php`: `img/pj_hope_4.JPG`
- `fundraising.php`: `img/pj_hope.JPG`

## Missing Requested Files

- `img/about-hero.jpg` does not exist.
- `img/devteam-hero.jpg` does not exist.
- Per instruction, both About and Dev Team temporarily use `img/Culture Night.jpeg` instead of a random placeholder/person image.

## Verification Results

- Source review verified all hero overlays are black transparent, not red/maroon.
- Source review verified the old watermark-only background no longer applies to restored hero classes.
- Source review verified exact local filenames are used, including the space/underscore distinction:
  - `Culture Night.jpeg`
  - `Culture_Night.jpeg`
- PHP syntax checks passed:
  - `achievements.php`
  - `cme.php`
  - `fundraising.php`

## Browser Cache Note

- A browser hard refresh may be needed to see the updated CSS immediately: `Ctrl + F5`.
