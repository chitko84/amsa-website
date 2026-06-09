# Public Back To Top Gold Button Report

## Files Changed

- `css/style.css`

## Button Color Standardization

- Public `.back-to-top` button now uses AMSA gold/yellow background:
  - `linear-gradient(135deg, var(--amsa-gold, #c6b511), #b59e0e)`
- Hover state uses a slightly darker gold:
  - `linear-gradient(135deg, #b59e0e, #9f8c0c)`
- Border color now matches the gold button style.

## Icon Color

- Back-to-top arrow icon is now explicitly white:
  - `.back-to-top i { color: #ffffff !important; }`
- This overrides the general arrow-standardization rule only for the back-to-top button.

## Scope Control

- Navbar was not changed.
- Footer layout was not changed.
- Hero sections were not changed.
- Cards were not changed.
- Admin pages and points pages were not changed.
- Only the public back-to-top button color was updated.

## Verification Results

- Source review confirms every listed public page uses the shared `.back-to-top` button class.
- Source review confirms `.back-to-top` no longer uses the maroon primary gradient.
- Source review confirms the back-to-top arrow icon remains white.
- Existing scroll-to-top JavaScript remains unchanged.
