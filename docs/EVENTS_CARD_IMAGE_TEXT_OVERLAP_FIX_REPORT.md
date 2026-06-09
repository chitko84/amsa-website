# Events Card Image Text Overlap Fix Report

## Files Changed

- `events.php`

## Issue Fixed

- Event/news cards with multiple uploaded images could let the image gallery overflow the reserved media area.
- Overflow happened because shared gallery styles used image `min-height` values that could exceed the card media container, especially with 3 images.

## Layout Changes

- Event cards now keep this clean visual order:
  - Image/Gallery Area
  - Category Badge
  - Title
  - Excerpt
  - Date
  - Read More Button
- The event card media area now reserves a fixed `16 / 9` aspect ratio.
- Gallery content is clipped inside the media area with `overflow: hidden`.
- Images use:
  - `width: 100%`
  - `height: 100%`
  - `object-fit: cover`
  - `display: block`

## Multi-Image Handling

- 1 image: full-width image inside the 16:9 area.
- 2 images: two equal columns inside the 16:9 area.
- 3 images: large image on the left, two stacked images on the right.

## Text Spacing

- Category badge remains below the image area.
- Title and excerpt now wrap naturally.
- Long titles/excerpts use `overflow-wrap` to prevent layout collision.

## Verification Results

- Source review confirms card text is outside the image/gallery container.
- Source review confirms gallery images are constrained to the reserved media area.
- PHP syntax check passed for `events.php`.
- Database logic, pagination logic, search/filter logic, upload logic, navbar, footer, and hero were not changed.
