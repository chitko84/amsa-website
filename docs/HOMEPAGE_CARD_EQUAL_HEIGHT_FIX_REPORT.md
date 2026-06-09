# Homepage Card Equal Height Fix Report

## Files Changed

- `index.php`

## Scope

- Only homepage preview card sizing was changed.
- Navbar, footer, hero, database logic, and other pages were not changed.

## Sections Fixed

- Events & News preview cards.
- Achievements preview cards.

## Layout Changes

- Added homepage-only classes:
  - `home-preview-grid`
  - `home-preview-card`
  - `home-preview-body`
- Preview card columns now stretch with flexbox.
- Cards now use `height: 100%` and `display: flex`.
- Card body now uses `display: flex` and `flex-direction: column`.
- Read More links align at the bottom of the card body.

## Image Standardization

- Homepage preview images now use a fixed `16 / 9` aspect ratio.
- Images use:
  - `width: 100%`
  - `height: 100%`
  - `object-fit: cover`
  - `display: block`

## Verification Results

- Events & News cards are structured to align to equal height.
- Achievements cards are structured to align to equal height.
- Images are contained in a consistent media area and are not stretched.
- Read More links align consistently at the bottom.
- PHP syntax check passed for `index.php`.
