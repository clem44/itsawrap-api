---
name: It's A Wrap Admin CMS
description: A warm, earthy back-office system for running a single wrap shop from a distance.
colors:
  deep-forest: "#1a3a2f"
  forest-dark: "#152b21"
  forest-light: "#2d5a47"
  sage-leaf: "#7c9a8a"
  sage-light: "#a8c5b5"
  burnt-terracotta: "#c4704f"
  terracotta-light: "#e89a7a"
  warm-cream: "#faf8f5"
  cream-dark: "#f0ebe3"
  ink: "#2c2c2c"
  ink-light: "#6b6b6b"
  error: "#b91c1c"
  error-light: "#fef2f2"
typography:
  display:
    fontFamily: "Fraunces, serif"
    fontSize: "1.5rem"
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: "normal"
  headline:
    fontFamily: "Fraunces, serif"
    fontSize: "1.125rem"
    fontWeight: 500
    lineHeight: 1.3
    letterSpacing: "normal"
  body:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "0.9375rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "DM Sans, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.05em"
rounded:
  xs: "0.5rem"
  sm: "0.625rem"
  md: "0.75rem"
  lg: "0.875rem"
  xl: "1rem"
  2xl: "1.25rem"
  3xl: "1.5rem"
  pill: "2rem"
  circle: "50%"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
  2xl: "2.5rem"
components:
  button-primary:
    backgroundColor: "{colors.burnt-terracotta}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "0.875rem 1.75rem"
  button-primary-hover:
    backgroundColor: "{colors.terracotta-light}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
  button-forest:
    backgroundColor: "{colors.deep-forest}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "0.875rem 1.75rem"
  button-secondary:
    backgroundColor: "{colors.warm-cream}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "0.875rem 1.75rem"
  badge-admin:
    backgroundColor: "{colors.deep-forest}"
    textColor: "#ffffff"
    rounded: "{rounded.pill}"
    padding: "0.375rem 0.875rem"
  badge-user:
    backgroundColor: "{colors.warm-cream}"
    textColor: "{colors.ink-light}"
    rounded: "{rounded.pill}"
    padding: "0.375rem 0.875rem"
---

# Design System: It's A Wrap Admin CMS

## Overview

**Creative North Star: "The Harvest Table"**

The admin CMS reads like a farmhouse ledger, not a generic SaaS dashboard: deep forest green anchors the frame, sage sits quietly in the middle distance, and burnt terracotta is the one warm spark reserved for the action that matters on a given screen. Cream is the resting canvas everything else sits on. The voice is warm and grounded — unhurried, tactile, earthy — built for a single remote owner checking in on their shop, not for a crowded operations floor.

Fraunces (serif, optical sizing on) carries every title, stat value, and modal heading; DM Sans carries everything the owner actually reads and scans — table rows, labels, form fields, meta text. A subtle fractal-noise texture sits under every gradient header block at very low opacity, giving the forest-gradient panels a papery, non-digital grain instead of a flat corporate wash.

**Key Characteristics:**
- Deep forest green as the trusted, structural anchor color (headers, sidebar, admin badges)
- Sage as the quiet secondary — icon fills, hover accents, meta text on dark grounds
- Burnt terracotta rationed to the single primary action per view
- Cream as the near-universal background; white reserved for cards and focused inputs
- Fraunces for display/title moments only; DM Sans owns all reading and data
- A faint fractal-noise grain (0.04–0.08 opacity) on every gradient header block

## Colors

Four hues do all the work: a dark structural green, a muted sage midtone, one warm terracotta accent, and a cream neutral family. Nothing else appears except semantic red for errors.

### Primary
- **Deep Forest** (`#1a3a2f`): the structural anchor. Sidebar background, page/profile/user header gradients, `.btn-forest`, admin role badge, active nav states, pagination "active" page.
- **Forest Light** (`#2d5a47`): the light end of every forest gradient (headers, sidebar footer, admin badges) — never used as a flat fill on its own.

### Secondary
- **Burnt Terracotta** (`#c4704f`): the one warm spark. `.btn-primary` (the default, most-used primary action), "Add User", edit action icons, `view-all-link`, the accent bar on `.section-title::before`, quick-action icon fills. Its colored drop-shadow (`rgba(196,112,79,0.25–0.35)`) is part of its identity — the shadow itself reads as warmth, not just depth.
- **Terracotta Light** (`#e89a7a`): the light end of every terracotta gradient; also the standalone hover-state color for `view-all-link` text.

### Tertiary
- **Sage Leaf** (`#7c9a8a`): the quiet secondary. Icon fills on cream (view actions, token icons, device icons), user-avatar gradient, focus-ring color (`rgba(124,154,138,0.15)`), role-card selected accents.
- **Sage Light** (`#a8c5b5`): text-on-dark (sidebar labels, header meta text, stat labels inside gradient headers) and the light end of sage gradients.

### Neutral
- **Warm Cream** (`#faf8f5`): the dominant background — page background, form inputs at rest, table header rows, sidebar-adjacent card fills.
- **Cream Dark** (`#f0ebe3`): borders and dividers throughout — table rows, card borders, section dividers. Never a fill color on its own.
- **Ink** (`#2c2c2c`): primary text.
- **Ink Light** (`#6b6b6b`): secondary/meta text — table meta, hints, placeholders, uppercase table headers.
- **Error** (`#b91c1c`) / **Error Light** (`#fef2f2`): destructive actions and validation states only.

### Named Rules
**The One Spark Rule.** Burnt terracotta is the primary-action color for exactly one thing per screen — the single most important button or link. It never becomes a background fill or a secondary accent; when a screen needs two levels of emphasis, the second one is forest green (`.btn-primary.btn-forest`), not a second use of terracotta.

## Typography

**Display/Headline Font:** Fraunces (serif, optical sizing auto), weights 300/500/600, with a serif system fallback.
**Body Font:** DM Sans, weights 400/500/600, with a sans-serif system fallback.

**Character:** Fraunces gives every title and number a quiet, editorial confidence; DM Sans stays completely out of the way for anything the owner is actually scanning or filling in. The pairing is deliberately asymmetric — expressive serif moments are rare and always mean "this is a heading or a headline number," never body text.

### Hierarchy
- **Display** (Fraunces, 600, ~1.5rem): dashboard stat values, avatar initials in large headers. The largest number or word on a screen.
- **Headline** (Fraunces, 500–600, ~1.125–1.25rem): section titles, modal titles, empty-state titles, page `<h1>`s.
- **Title/Meta** (DM Sans, 500, 0.9375rem): sidebar nav labels, table user names, admin header page title.
- **Body** (DM Sans, 400, 0.9375rem): table cell content, form input text.
- **Small/Meta** (DM Sans, 400, 0.875rem): back-links, user meta, admin username, form hints.
- **Label** (DM Sans, 600, 0.75rem, uppercase, 0.05em tracking): table column headers, stat labels.

### Named Rules
**The Serif-Is-a-Signal Rule.** Fraunces only ever marks a title, a heading, or a headline number. If DM Sans would read fine there, it belongs in DM Sans — Fraunces is reserved for the handful of moments per screen that deserve the extra weight.

## Layout

Two-column app shell: a fixed forest-gradient sidebar (16rem expanded, 5rem collapsed, state persisted to `localStorage`) and a scrollable cream main area. Content stacks in cards with generous internal padding (1.25–2.5rem) rather than a dense table-only layout. Grids collapse from wide multi-column stat/form layouts down to single-column below `768px`/`640px` breakpoints (`.stats-grid`, `.form-grid`, `.profile-stats`). Forms cap at `42rem` max-width and center themselves rather than stretching full-bleed.

## Elevation & Depth

Shadow strength is a deliberate hierarchy signal, not ambient decoration — it escalates with the stakes of the surface. Resting content (tables, section cards, stat cards, form cards) carries only the faintest lift (`rgba(0,0,0,0.04)`), so lists and record views stay visually quiet. The moment a decision is required, shadow strength jumps: the modal backdrop overlay separates a blocking dialog from the page with a much stronger shadow (`rgba(0,0,0,0.2)`), and the single primary action on a screen carries its own colored glow shadow (terracotta or forest at 0.25 alpha, brightening to 0.35 on hover) so the one button that matters visibly lifts off the page.

### Shadow Vocabulary
- **Resting card** (`0 4px 20px rgba(0,0,0,0.04)`): section cards, users table container.
- **Resting card, small** (`0 2px 8px rgba(0,0,0,0.04)`, hover `0 4px 16px rgba(0,0,0,0.08)`): dashboard stat cards.
- **Form card** (`0 4px 24px rgba(0,0,0,0.04)`): form containers.
- **Modal** (`0 20px 40px rgba(0,0,0,0.2)`): the strongest shadow in the system — reserved for the modal card floating over its backdrop.
- **Primary action glow — terracotta** (`0 4px 12px rgba(196,112,79,0.25)`, hover `0 6px 20px rgba(196,112,79,0.35)`): `.btn-primary`, `.btn-add-user`.
- **Primary action glow — forest** (`0 4px 12px rgba(26,58,47,0.25)`, hover `0 6px 20px rgba(26,58,47,0.35)`): `.btn-primary.btn-forest`.

### Named Rules
**The Escalating Shadow Rule.** Shadow intensity tracks how much a surface is asking of the owner: near-flat for anything they're just reading, strongest for anything blocking (a modal) or anything they're being asked to click (the one primary action).

## Shapes

A consistent, fairly generous radius scale — nothing on this system is sharp-cornered. Small interactive controls (action icon buttons, modal close) use `0.5rem`; buttons, inputs, and selects use `0.75rem`; cards escalate from `0.875rem` (role-select cards) through `1rem` (stat cards) to `1.25rem` (section/table/modal cards) up to `1.5rem` for the large header blocks (page header, profile header, form card). Badges and pills use a full `2rem` pill radius; avatars are perfect circles (`50%`). Borders, where present, are a single `1px` line in `cream-dark` — never used for emphasis, only for quiet separation.

## Components

### Buttons
- **Shape:** `0.75rem` radius, `0.875rem 1.75rem` padding for standard buttons.
- **Primary (`.btn-primary`):** terracotta→terracotta-light gradient, white text, colored glow shadow (see Elevation). Lifts `-2px` on hover with the shadow brightening.
- **Primary/Forest (`.btn-primary.btn-forest`):** same shape and lift behavior, forest→forest-light gradient and forest-colored glow — the system's second-priority action color.
- **Secondary (`.btn-secondary`):** flat cream fill, ink text, `cream-dark` 1px border, no shadow; darkens to `cream-dark` on hover.
- **Revoke/destructive (`.btn-revoke`):** transparent with an error-red outline that fills solid red on hover.

### Badges
- **Role badge:** pill-shaped (`2rem` radius). Admin = forest→forest-light gradient, white text. User = flat cream with `ink-light` text and a `cream-dark` border. On dark (gradient header) backgrounds, admin flips to a translucent white pill and user flips to sage-light-on-forest.

### Cards / Containers
- **Corner style:** see Shapes scale by card type (`1rem`–`1.5rem`).
- **Background:** white for content cards (tables, forms, sections); the forest gradient (with noise grain) for header blocks.
- **Shadow strategy:** resting tier by default; see Elevation.
- **Border:** single `1px` `cream-dark` line on white cards.
- **Internal padding:** `1.25rem–1.5rem` for section cards, `2.5rem` for form cards and page/profile headers.

### Inputs / Fields
- **Style:** cream fill, `2px` transparent border, `0.75rem` radius, `0.875rem 1rem` padding.
- **Focus:** background shifts to white, border turns sage, and a soft sage focus ring appears (`0 0 0 3px rgba(124,154,138,0.15)`).
- **Error:** background shifts to `error-light`, border turns `error`, focus ring turns red-tinted.

### Navigation (Sidebar)
- Forest-gradient background with the same low-opacity noise grain as headers. Nav links: sage-light text at rest, white on hover/active, active state adding a translucent white pill background. Collapses to an icon-only `5rem` rail with logo swapping to a compact "IAW" mark; state persists in `localStorage`.

### Tables
- Cream header row with uppercase `ink-light` labels; white body rows separated by `1px` `cream-dark` dividers; row hover tints to cream. No vertical borders — separation is entirely horizontal and color-based.

## Do's and Don'ts

### Do:
- **Do** reserve burnt terracotta for the single primary action per screen (The One Spark Rule); use the forest variant for a second-priority action instead of a second terracotta button.
- **Do** use Fraunces exclusively for titles, headings, and headline-scale numbers; keep every table cell, form field, label, and meta string in DM Sans.
- **Do** keep the fractal-noise overlay under gradient header blocks at 0.04–0.08 opacity — it should read as grain, not texture.
- **Do** let shadow strength communicate stakes: near-flat for resting content, strongest for modals, colored-glow for the one primary action (The Escalating Shadow Rule).
- **Do** stay within the established radius scale (`0.5rem` through `1.5rem`, plus pill and circle) rather than introducing new corner values.

### Don't:
- **Don't** use burnt terracotta as a background fill or for more than one control per view.
- **Don't** add heavy shadows to resting/list surfaces (tables, list rows) — that strength is reserved for the modal tier.
- **Don't** use the error red palette for anything other than destructive actions and validation states.
- **Don't** introduce a vertical table border or heavier row dividers — separation stays horizontal and low-contrast.
