# Index Assistant UI Fix Report

## Files Changed

- `assets/css/amsa-chatbot.css`

## Header Contrast Fix

- `AMSA Assistant` title now explicitly uses white text.
- `Website navigation help` subtitle now uses soft white text.
- Maroon chatbot header background remains unchanged.

## Floating Button Position Fix

- Chatbot button moved above the back-to-top button:
  - Desktop: `bottom: 95px; right: 24px`
  - Mobile: `bottom: 86px; right: 14px`
- Back-to-top button was not modified.
- Chatbot logic, questions, answers, and homepage layout were not changed.

## Verification Results

- Source review confirms chatbot header title/subtitle are readable white/soft white.
- Source review confirms chatbot button no longer shares the same bottom-right position as the back-to-top button.
- JavaScript logic was not changed.
