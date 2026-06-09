# Arrow Color Standardization Report

## Files Changed

- `css/style.css`

## Standard Color

- Standardized public arrow color to the CME gold value:
  - `#c6b511`
- Added shared variables:
  - `--amsa-gold`
  - `--amsa-arrow-gold`

## Arrow Types Covered

- Read More arrow icons.
- Footer link arrow icons.
- Directional Bootstrap icon arrows:
  - `bi-arrow-right`
  - `bi-arrow-left`
  - `bi-arrow-up`
  - `bi-arrow-down`
- Directional Font Awesome arrow icons:
  - `fa-arrow-right`
  - `fa-arrow-left`
  - `fa-arrow-up`
  - `fa-arrow-down`
- Breadcrumb separators.
- Hero breadcrumb separators.
- Dropdown navigation carets.
- Back-to-top arrow icon.

## Scope Control

- No buttons were redesigned.
- No headings were changed.
- No hero sections were changed.
- No logo colors were changed.
- No chatbot colors were changed.
- No admin pages or points system pages were changed.
- Only shared public arrow/directional icon styling was updated.

## Verification Results

- Source review confirms public arrow selectors now use `--amsa-arrow-gold`.
- Source review confirms the standard color matches CME gold `#c6b511`.
- Public pages using `css/style.css` inherit the standardized arrow color.
