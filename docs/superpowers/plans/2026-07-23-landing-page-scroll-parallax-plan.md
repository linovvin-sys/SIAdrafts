# Landing Page Scroll Parallax Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a scroll-linked parallax background of soft blurred pine/gold blobs behind `index.php`, spanning the full page, using vanilla JS (no dependencies).

**Architecture:** A fixed-position `.e-parallax-layer` div holds 4 blurred `.e-blob` divs, injected once near the top of `<body>` in `index.php`. CSS positions them statically as a default. An inline script (added to the existing `<script>` block at the bottom of `index.php`) attaches a `scroll` listener, throttled with `requestAnimationFrame`, that applies `translateY(scrollY * speed)` per blob using each blob's `data-speed` attribute. `body.editorial-body`'s background becomes translucent so blobs are visible through it (there's no per-section background in this theme — see `editorial-theme.css:24` — so a single body-level translucency change is sufficient for whole-page coverage). `prefers-reduced-motion: reduce` skips attaching the listener entirely, same pattern as the existing reveal/rule scripts in `index.php:224-283`.

**Tech Stack:** Plain CSS + vanilla JS (matches existing `nav-scroll.js` / inline reveal-observer pattern in `index.php`). No build step, no new dependencies.

## Global Constraints

- No external JS libraries or new npm/CDN dependencies (spec: "Rejected alternatives" — no GSAP).
- Must respect `prefers-reduced-motion: reduce` — blobs render static, no scroll listener attached (spec: "Data Flow" step 3).
- Effect applies across the entire page, all screen sizes including mobile — no breakpoint-based disabling (spec: "Approach").
- Scoped to `SIAdrafts/Frontend/View/index.php` and `SIAdrafts/Frontend/Css/editorial-theme.css` only — no other pages touched (spec: "Out of Scope").
- This codebase has no automated test runner for frontend code — verification steps below are manual (browser + devtools), not `pytest`/`jest` style.

---

### Task 1: Parallax layer CSS — blob layer, positioning, and translucent body background

**Files:**
- Modify: `SIAdrafts/Frontend/Css/editorial-theme.css` (add new rules after the `body.editorial-body` block, currently at lines 22-26)

**Interfaces:**
- Produces: CSS classes `.e-parallax-layer` (container) and `.e-blob` (individual blob, expects a `data-speed` attribute set in HTML/JS but CSS itself doesn't reference it), plus a `.e-blob--1` … `.e-blob--4` modifier per blob for position/size/color. Task 2 will add the matching HTML markup using these exact class names.

- [ ] **Step 1: Change `body.editorial-body` background to translucent paper**

In `SIAdrafts/Frontend/Css/editorial-theme.css`, find:

```css
body.editorial-body{
  background: var(--paper);
  color: var(--ink);
  font-family: var(--font-body);
}
```

Replace with:

```css
html{ background: var(--paper); }

body.editorial-body{
  background: rgba(247, 244, 236, 0.86);
  color: var(--ink);
  font-family: var(--font-body);
}
```

(The `html` rule keeps a solid fallback behind everything; the body's own background becomes translucent so the fixed blob layer shows through.)

- [ ] **Step 2: Add the parallax layer and blob rules**

Immediately after the block from Step 1, add:

```css
/* ---------- scroll parallax background ---------- */

.e-parallax-layer{
  position: fixed;
  inset: 0;
  z-index: -1;
  overflow: hidden;
  pointer-events: none;
}

.e-blob{
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.28;
  will-change: transform;
}

.e-blob--1{
  width: 420px; height: 420px;
  top: -120px; left: -80px;
  background: var(--accent);
}
.e-blob--2{
  width: 520px; height: 520px;
  top: 620px; right: -160px;
  background: var(--ink);
  opacity: 0.16;
}
.e-blob--3{
  width: 380px; height: 380px;
  top: 1400px; left: 8%;
  background: var(--accent);
  opacity: 0.2;
}
.e-blob--4{
  width: 460px; height: 460px;
  top: 2200px; right: 6%;
  background: var(--ink);
  opacity: 0.14;
}

@media (prefers-reduced-motion: reduce){
  .e-blob{ transition: none; }
}
```

- [ ] **Step 3: Manual verification — layer renders and sits behind content**

Open `http://localhost:8888/SIAdrafts/Frontend/View/index.php` (adjust host/port to your MAMP setup) in a browser. The page should render exactly as before (no `.e-parallax-layer` element exists in the HTML yet, so this step only confirms the CSS didn't break anything). Run:

```bash
grep -n "e-parallax-layer\|e-blob" /Applications/MAMP/htdocs/SIAdrafts/Frontend/Css/editorial-theme.css
```

Expected: the rules from Step 1 and 2 are present, no CSS syntax errors (check browser devtools Console for "Failed to parse" warnings — should be none).

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Frontend/Css/editorial-theme.css
git commit -m "Add parallax blob layer CSS and translucent body background for landing page"
```

---

### Task 2: Blob markup + scroll parallax script

**Files:**
- Modify: `SIAdrafts/Frontend/View/index.php` (insert blob markup right after `<body class="editorial-body">`, currently `index.php:32`; add script to the existing `<script>` block near the bottom, currently `index.php:215-283`)

**Interfaces:**
- Consumes: `.e-parallax-layer`, `.e-blob`, `.e-blob--1..4` CSS classes from Task 1.
- Produces: none consumed by later tasks (this is the last task).

- [ ] **Step 1: Insert blob markup**

In `SIAdrafts/Frontend/View/index.php`, find:

```php
<body class="editorial-body">

  <div class="e-nav-wrap">
```

Replace with:

```php
<body class="editorial-body">

  <div class="e-parallax-layer" aria-hidden="true">
    <div class="e-blob e-blob--1" data-speed="0.08"></div>
    <div class="e-blob e-blob--2" data-speed="0.14"></div>
    <div class="e-blob e-blob--3" data-speed="0.05"></div>
    <div class="e-blob e-blob--4" data-speed="0.18"></div>
  </div>

  <div class="e-nav-wrap">
```

- [ ] **Step 2: Add the scroll parallax script**

In `SIAdrafts/Frontend/View/index.php`, find the closing of the existing scroll-reveal IIFE (the last script block before `</script>`):

```javascript
      items.forEach(function (el) { observer.observe(el); });
    })();
  </script>
```

Replace with:

```javascript
      items.forEach(function (el) { observer.observe(el); });
    })();

    // Scroll parallax — fixed blob layer drifts at a fraction of scroll
    // speed per blob (via each blob's data-speed), giving a sense of
    // depth behind the content. Skipped entirely under
    // prefers-reduced-motion, matching the reveal/rule scripts above.
    (function () {
      var blobs = document.querySelectorAll('.e-blob');
      if (!blobs.length) return;

      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      var ticking = false;

      function applyParallax() {
        var scrollY = window.scrollY;
        blobs.forEach(function (blob) {
          var speed = parseFloat(blob.getAttribute('data-speed')) || 0;
          blob.style.transform = 'translateY(' + (scrollY * speed) + 'px)';
        });
        ticking = false;
      }

      window.addEventListener('scroll', function () {
        if (!ticking) {
          window.requestAnimationFrame(applyParallax);
          ticking = true;
        }
      }, { passive: true });
    })();
  </script>
```

- [ ] **Step 3: Manual verification — blobs render and move on scroll**

With the MAMP server running, open `http://localhost:8888/SIAdrafts/Frontend/View/index.php` in a browser.

1. Open devtools Console and run:
   ```js
   document.querySelectorAll('.e-blob').length
   ```
   Expected: `4`

2. Scroll the page down. In the Console, run:
   ```js
   document.querySelector('.e-blob--1').style.transform
   ```
   Expected: a `translateY(...)` string with a non-zero pixel value (confirms the listener is applying transforms).

3. Visually confirm: soft blurred gold/dark-green circles are faintly visible behind the hero, steps, programs, and FAQ sections, and drift at different rates relative to the content as you scroll (blobs with smaller `data-speed` move less than the content scroll, larger `data-speed` blobs move more).

4. In devtools, toggle "Emulate CSS prefers-reduced-motion: reduce" (Rendering tab), reload, and confirm scrolling no longer changes `document.querySelector('.e-blob--1').style.transform` (should remain empty string).

- [ ] **Step 4: Commit**

```bash
git add SIAdrafts/Frontend/View/index.php
git commit -m "Add scroll parallax blob markup and script to landing page"
```

---

## Post-Implementation Check

- [ ] Confirm no other page includes `editorial-theme.css` (so the body-translucency change is scoped to the landing page only):

```bash
grep -rl "editorial-theme.css" /Applications/MAMP/htdocs/SIAdrafts
```

Expected: only `SIAdrafts/Frontend/View/index.php`.
