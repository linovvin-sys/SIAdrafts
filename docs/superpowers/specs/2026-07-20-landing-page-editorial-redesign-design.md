# Landing Page Editorial/Elegant Redesign — Design Spec

**Date:** 2026-07-20
**Status:** Approved

## Context

The landing page (`SIAdrafts/Frontend/View/index.php`) has gone through a glassmorphism pass (shipped) and a "futuristic terminal" pass (built, then explicitly discarded by the user as "too portfolio"). The user now wants an elegant direction — restrained, editorial, closer to a university prospectus than a product landing page or a tech demo. This is a full replace of the glass theme on this page only, not a toned-down variant of it.

## Goals

1. Full-replace `index.php`'s visual identity with an editorial/elegant concept: quiet, whitespace-driven, printed-matter inspired.
2. New token system — parchment background, deep pine ink, antique gold accent (deliberately distinct from both the discarded navy/amber glass theme and the discarded cyan/violet terminal theme).
3. Fraunces (display serif, restrained) + Inter (body) type pairing — no third utility face needed.
4. Two consistent, quiet signature devices: oversized thin-serif step numerals, and a self-drawing underline rule beneath section headings on scroll.
5. Hover states reduced to single-property transitions (underline or opacity) — no scale, glow, blur, or sweep effects.
6. Content substance stays the same (same programs data query, same 4 steps, same FAQ answers, same Apply Now → course-lock flow) — this is a visual/interaction redesign only.

## Non-Goals

- Any other page — untouched.
- Reusing any part of `glass-theme.css` or the discarded terminal files — clean new CSS file, old glass-theme.css left in place unused (per earlier conversation, in case it's wanted elsewhere later).
- New animation libraries (AOS, GSAP) — the two signature devices are implemented with plain `IntersectionObserver`, no new dependency.
- Testimonials/stats/FAQ as separate flashy sections — content is preserved but presented more quietly (e.g. testimonials as a simple pull-quote style, not glowing cards).

## Design

### 1. Token System

**Color:**
| Token | Hex | Use |
|---|---|---|
| `--paper` | `#F7F4EC` | page background |
| `--ink` | `#1F2E28` | primary text, deep pine — replaces navy |
| `--ink-soft` | `rgba(31,46,40,0.62)` | secondary text |
| `--accent` | `#A47B3F` | antique gold — sparing use: one CTA, link underlines, numerals |
| `--line` | `rgba(31,46,40,0.12)` | hairline borders, dividers |
| `--surface` | `#FFFFFF` | rare card backgrounds where separation is needed |

**Type:**
- Display: Fraunces (headlines only, weight 400-500, italic used for single emphasized words within a headline)
- Body: Inter (unchanged from prior passes)
- Both loaded via Google Fonts CDN.

### 2. Signature Devices

- **Step numerals**: each of the 4 "how it works" steps gets an oversized (roughly 64-80px) thin-weight Fraunces numeral (01-04) set in the antique-gold accent color, positioned as a margin marker beside the step content — evokes a prospectus/annual-report chapter marker rather than a UI badge.
- **Self-drawing heading rule**: every major section heading gets a thin (1.5px) horizontal rule beneath it that animates from 0 to full width via `IntersectionObserver` + CSS `transform: scaleX()` transition, firing once when the heading enters the viewport. This is the only motion device on the page, applied identically to every section so it reads as a consistent system, not decoration.

### 3. Section-by-Section

- **Hero**: large Fraunces headline with one italicized word for emphasis, thin rule under a small uppercase-tracked eyebrow label (no pill badge), generous vertical spacing, single Apply Now button (solid ink background, no glow/gradient).
- **Programs**: presented as a simple bordered list/table-like arrangement rather than a card grid — course name, unit count, a text "Apply →" link (underline-on-hover only).
- **Steps**: numeral-marker layout described above, single column on mobile.
- **Testimonials**: reduced to a simple pull-quote treatment — large italic Fraunces quote, small attribution line, no card chrome/avatar graphics.
- **FAQ**: plain accordion — hairline divider between items, `+`/`−` mark instead of a chevron icon, single-property (opacity + max-height) expand.
- **CTA**: a simple full-width band with a rule above/below, no glow.
- **Footer**: minimal, small type.

## Data Flow

Unchanged — `$courses` query and the Apply Now → course-lock modal → `online_admission.php?course_id=X` flow stay exactly as implemented.

## Error Handling

- `IntersectionObserver`-driven heading rules default to fully visible (not hidden-by-default) if `IntersectionObserver` is unavailable, so a script failure never hides content — same progressive-enhancement principle used in prior passes.

## Testing

Manual verification only (no automated test suite in this codebase):
- Load in a browser (flagged for the user): confirm the heading rules draw in once per heading on scroll, step numerals render correctly at all breakpoints, FAQ still expands/collapses, Apply Now flow (including course-locked modal) works unchanged.
- Confirm text contrast (ink on parchment) remains high — this palette is inherently higher-contrast than the prior glass/dark themes, so this is a lower-risk area, but verify the antique-gold accent text (used for the step numerals and link states) stays legible against parchment.
- `prefers-reduced-motion`: heading rules should render in their final (fully drawn) state immediately rather than animating.
