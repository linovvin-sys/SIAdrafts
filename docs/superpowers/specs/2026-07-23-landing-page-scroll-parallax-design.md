# Landing Page Scroll Parallax — Design

Date: 2026-07-23

## Goal

Add a scroll-linked moving background to `SIAdrafts/Frontend/View/index.php` so the page feels less static, in keeping with the existing parchment/pine/gold editorial theme.

## Approach

A fixed-position layer of soft, blurred organic blobs (pine-green and gold, matching the theme's accent colors) sits behind all page content, spanning the full page. A vanilla-JS scroll listener, throttled via `requestAnimationFrame`, moves each blob at its own speed as the user scrolls, so slower blobs read as "further back" — the classic parallax depth effect. No external libraries; this matches the existing hand-rolled `IntersectionObserver` reveal/rule logic already in `index.php`.

Rejected alternatives:
- **CSS `animation-timeline: scroll()`** — more elegant, but browser support isn't reliable enough yet for this project's needs.
- **GSAP ScrollTrigger** — would add an external dependency; the project currently has none.

Effect applies across the entire page (not just the hero), on all screen sizes including mobile (no reduced-effort mobile fallback beyond the existing `prefers-reduced-motion` handling).

## Components

### CSS (`editorial-theme.css`)
- `.e-parallax-layer`: `position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;` — sits behind all content.
- `.e-blob` (3–4 instances): large blurred circles (`filter: blur(...)`), sized and positioned to spread across the full viewport height, colored from the existing `--accent` (gold) and pine-green theme variables at low opacity.
- Section background colors (`--paper`, `--surface`, etc.) get a translucency pass (similar to the nav's existing `rgba(247,244,236,0.92)`) so blobs are visible glowing through the parchment texture rather than fully hidden behind opaque section blocks.

### JS (inline `<script>` block in `index.php`, alongside existing reveal/rule logic)
- On scroll, throttled via `requestAnimationFrame`, read `window.scrollY` and apply `transform: translateY(scrollY * speed)` to each `.e-blob`, where `speed` (e.g. 0.05–0.2) comes from a `data-speed` attribute per blob.
- Respects `prefers-reduced-motion: reduce`: if set, blobs render in their static CSS position with no scroll-linked transform (checked once on load, same pattern as the existing reveal/rule observers).
- No `IntersectionObserver` needed here — the scroll listener runs continuously while the page is visible.

## Data Flow

1. Page loads → blobs render at default static positions (CSS).
2. `scroll` event fires → rAF flag set (only one pending frame at a time) → on next frame, compute `scrollY` and update each blob's `transform`.
3. `prefers-reduced-motion: reduce` → skip attaching the scroll listener entirely; blobs stay static.

## Out of Scope

- No changes to Admin/Registrar/Admission pages — this is `index.php` only.
- No new build tooling or JS dependencies.
