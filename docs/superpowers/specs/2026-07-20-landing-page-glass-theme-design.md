# Landing Page Glassmorphism Theme — Design Spec

**Date:** 2026-07-20
**Status:** Approved

## Context

`SIAdrafts/Frontend/View/index.php` is the public landing page — currently a self-contained page with inline `<style>` using a warm ink/amber/sage palette, a hero, a 4-step "how it works" section, and a dynamic course showcase (added in the Admission sub-project). The user wants a modern glassmorphism visual upgrade with AOS scroll animations, scoped to this page only (not the internal admin/staff pages, which stay as-is — glass/blur would hurt readability on data-dense DataTables screens, and this is a school system where the internal tooling should stay utilitarian).

## Goals

1. Add a reusable, Bootstrap-layered glass theme (`glass-theme.css`) — new file, no edits to Bootstrap's own CSS.
2. Apply it to index.php: navbar, hero, feature/stat/testimonial/FAQ cards, buttons, form-adjacent elements.
3. Add a fixed animated mesh-gradient/orb background (new accent palette: indigo/cyan/rose) so the blur has something to show through.
4. Integrate AOS (CDN) with fade-up/zoom-in/fade-right entrance animations, staggered for grouped elements.
5. Expand index.php's content — stats strip, "Why EduSchool" features, testimonials (placeholder content, clearly marked), FAQ accordion, closing CTA banner — so the page has enough sections for the animations to feel purposeful rather than sparse.

## Non-Goals

- Login page, or any internal Admin/Registrar/HeadRegistrar/Admission page — explicitly out of scope for this pass.
- Editing Bootstrap's own distributed CSS files.
- Real testimonial content — placeholders only, flagged for the user to replace with real quotes/names later.

## Design

### 1. `glass-theme.css` (new file, `Frontend/Css/glass-theme.css`)

- CSS custom properties for the glass system: `--glass-blur: 14px`, `--glass-border: rgba(255,255,255,0.2)`, `--glass-radius: 16px`, `--glass-shadow: 0 8px 32px rgba(31,38,135,0.15), 0 2px 8px rgba(0,0,0,0.06)`.
- `.glass-nav`: applied alongside Bootstrap's `.navbar` — `background: rgba(255,255,255,0.6)` (light section), `backdrop-filter: blur(var(--glass-blur))`, `-webkit-backdrop-filter`, border-bottom `1px solid var(--glass-border)`.
- `.glass-card`: layered on `.card`/custom card divs — same blur/border/radius/shadow recipe, `background: rgba(255,255,255,0.55)` on light sections.
- `.glass-btn`: layered on `.btn` — transparent/translucent background, blur, hover state adds a soft glow (`box-shadow: 0 0 20px rgba(99,102,241,0.35)`) — Bootstrap's own `.btn`/`.btn-lg` sizing classes untouched.
- `.glass-orb`: absolutely-positioned blurred circles (indigo/cyan/rose, `filter: blur(60px)`, low opacity), animated with a slow `@keyframes drift` (transform translate, ~20s loop) — respects `prefers-reduced-motion` (animation disabled).
- All new classes are additive (no Bootstrap variable overrides needed here since this page doesn't currently use Bootstrap's own `.card`/`.btn` classes much — it's custom CSS-in-`<style>` — but `.glass-*` classes are written to compose cleanly with Bootstrap classes if any are introduced in the new sections, e.g. the FAQ accordion).

### 2. Background

- Fixed `<div class="glass-bg-orbs">` containing 3 `.glass-orb` divs (indigo/cyan/rose), `position: fixed; inset: 0; z-index: -1; overflow: hidden;` behind all content, visible through every glass panel on the page.

### 3. New Sections (in order, after hero, before existing "How it works" steps)

- **Stats strip**: 3-4 glass stat cards (e.g. "4+ Programs Open", "4 Simple Steps", "~10 Min Application") — `data-aos="zoom-in"`, staggered.
- **"Why EduSchool" features**: 3-4 glass feature cards (Fast Online Application, Transparent Fees, Dedicated Support, Track Your Status) — `data-aos="fade-up"`, staggered 100ms increments.
- **Testimonials**: 2-3 glass cards with placeholder quotes, clearly commented as placeholder in the HTML (`<!-- PLACEHOLDER: replace with real testimonials -->`) — `data-aos="fade-right"`, staggered.
- **FAQ**: Bootstrap accordion (`.accordion`) styled with `.glass-card`, 4-5 common questions — `data-aos="fade-up"`.
- **Closing CTA banner**: full-width glass panel, "Ready to start?" + Apply Now button — `data-aos="zoom-in"`.

Existing sections (hero, steps, dynamic programs) also get `data-aos` attributes retrofitted (hero: `fade-up` on headline/subhead, `fade-in` staggered on CTA buttons; steps: `fade-up` staggered; program cards: `fade-up` staggered, matching the existing per-card loop).

### 4. AOS Integration

- `<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>` + matching CSS link, CDN — consistent with every other library already loaded on this page (iconify, sweetalert2-adjacent conventions elsewhere in the app).
- Init: `AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true });`

## Contrast/Readability Flags (to call out inline once built, per the user's request)

- White/light text over the light-cream base with glass panels needs verifying — this page currently uses dark ink text on a light `--paper` background, so `.glass-card`'s `rgba(255,255,255,0.55)` background should keep dark text readable as long as we don't drop the base page background too much (no dark hero section on this page currently, unlike the internal dashboards).
- The `.glass-btn` hover glow must stay subtle enough not to wash out button label text — verify against both the amber `.btn-apply` and outline `.btn-login` variants.
- FAQ accordion glass background must stay opaque enough that expanded answer text doesn't fight with the drifting orbs underneath.

## Testing

Manual verification only (no automated test suite in this codebase):
- Load index.php in a browser (can't be done from this environment directly — flag for the user), confirm orbs render and drift subtly, glass panels blur correctly, AOS animations fire once per element on scroll with the specified easing/duration, and no contrast issues per the flags above.
- `prefers-reduced-motion` respected (orb drift and AOS both disabled/instant).
- Confirm the existing "Apply Now" → course-locked application flow (Admission sub-project) still works unchanged — this pass only restyles/adds content, doesn't touch that logic.
