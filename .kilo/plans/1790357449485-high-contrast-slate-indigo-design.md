# High-Contrast Slate & Indigo Design System Implementation Plan

## Goal
Replace the current Aurora/silver-gray theme with a clean, modern enterprise SaaS design using:
- **Primary**: Indigo-600 (#2563EB) for buttons, links, focus rings
- **Cards**: Glassmorphism — `rgba(255,255,255,0.75)` + `backdrop-filter: blur(12px)` with subtle border
- **Text**: Charcoal (#0F172A / slate-950) headings, Slate Gray (#475569 / slate-600) subtext
- **Background**: Solid `bg-slate-50` (no animation)

---

## Scope

### In Scope
1. **Tailwind v4 theme** — define custom colors in `@theme`
2. **Global layout** (`layouts/app.blade.php`) — background, header, card utilities
3. **Login page** (`auth/login.blade.php`) — remove Aurora, apply glass cards
4. **Admin dashboard** (`admin/dashboard.blade.php`) — card grid, buttons
5. **Field Staff home** (`field/home.blade.php`) — card grid
5. **School forms** (`schools/form.blade.php`, `schools/show.blade.php`, `schools/index.blade.php`)
6. **Delivery forms** (`deliveries/form.blade.php`, `deliveries/index.blade.php`)
7. **Shared components** — buttons, inputs, badges, tables, alerts
8. **Aurora removal** — delete CSS animation, component, references

### Out of Scope
- React Aurora component (unused in Blade views)
- PDF/print styles (keep existing)
- Demo/pending pages (`admin/pending.blade.php`, `field/pending.blade.php`)
- JavaScript logic (only visual changes)

---

## Technical Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Card style | Glassmorphism | Matches "silver-gray sheen" context; modern depth |
| Primary color | `#2563EB` (indigo-600) | "Deep Electric Blue" per spec; accessible on white |
| Text heading | `#0F172A` (slate-950) | "Charcoal" — highest contrast |
| Text subtext | `#475569` (slate-600) | "Slate Gray" — readable secondary |
| Background | `bg-slate-50` | Clean, no animation, enterprise feel |
| Color definition | `@theme` in `app.css` | Tailwind v4 native; single source of truth |
| Border color | `slate-200` (#E2E8F0) | Subtle, matches spec |

---

## File Changes

### 1. `resources/css/app.css`
- Add `@theme` with custom colors: `indigo-600`, `charcoal`, `slate-gray`, `glass-bg`, `glass-border`
- Define utility classes: `.card-glass`, `.btn-primary`, `.btn-secondary`, `.input-base`, `.label-base`
- Remove Aurora CSS (`.aurora-shell`, keyframes, `@media prefers-reduced-motion`)
- Keep print styles, font import

### 2. `resources/views/layouts/app.blade.php`
- Remove `@if($aurora ?? true)` Aurora div
- Change `body` class: `bg-slate-50 text-slate-900`
- Header: `bg-white/80 backdrop-blur-xl border-b border-slate-200`
- Logo: `bg-indigo-600` (was `blue-700`)
- Nav links: `text-indigo-600 hover:text-indigo-700`
- Sign-out button: `border-slate-300 hover:bg-slate-100`
- Status alert: `border-emerald-200 bg-emerald-50 text-emerald-900` (unchanged)

### 3. `resources/views/auth/login.blade.php`
- Remove `extends('layouts.app', ['aurora' => true])` → `extends('layouts.app')`
- Card: `rounded-3xl bg-white/75 backdrop-blur-xl border border-slate-200 shadow-xl shadow-indigo-900/10 p-6 sm:p-9`
- Headings: `text-slate-950` (charcoal)
- Subtext: `text-slate-600`
- Inputs: `border-slate-300 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100`
- Primary button: `bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/20`
- Link: `text-indigo-600 hover:underline`
- Error alert: `border-rose-200 bg-rose-50 text-rose-800` (unchanged)

### 4. `resources/views/admin/dashboard.blade.php`
- Section header: `text-indigo-600` (was `blue-700`)
- Cards: `rounded-2xl border border-slate-200 bg-white/75 backdrop-blur-xl p-6 shadow-sm hover:border-indigo-300 hover:shadow-md`
- Card titles: `text-slate-950`
- Card descriptions: `text-slate-600`
- CTA links: `font-semibold text-indigo-600`
- Primary button (Create Staff): `rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700`

### 5. `resources/views/field/home.blade.php`
- Same card style as admin dashboard
- Section label: `text-indigo-600`
- Headings: `text-slate-950`
- Descriptions: `text-slate-600`
- CTA: `font-semibold text-indigo-600`

### 6. `resources/views/schools/form.blade.php`
- Back link: `text-indigo-600`
- Card: `rounded-2xl border border-slate-200 bg-white/75 backdrop-blur-xl p-6 shadow-sm`
- Headings: `text-slate-950`
- Subtext: `text-slate-600`
- Inputs: `border-slate-300 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-100`
- Fieldset legends: `text-lg font-semibold text-slate-950`
- Checkbox labels: `text-slate-700`
- Helper text: `text-xs text-slate-500`
- Error text: `text-sm text-rose-700`
- Warning banners: `border-amber-200 bg-amber-50 text-amber-900` (unchanged)
- Primary button: `rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700`
- Cancel link: `font-semibold text-slate-600 hover:underline`

### 7. `resources/views/schools/show.blade.php`
- Same card/input styles as form
- Code badge: `bg-slate-100 text-slate-700`
- Section cards: glassmorphism
- Action buttons: primary/secondary variants

### 8. `resources/views/schools/index.blade.php`
- Table: `bg-white/75 backdrop-blur-xl border border-slate-200 rounded-2xl overflow-hidden`
- Header: `bg-slate-50 text-slate-600 uppercase tracking-wide text-xs`
- Rows: `border-t border-slate-100 hover:bg-slate-50/50`
- Status badges: `bg-emerald-100 text-emerald-800` / `bg-slate-100 text-slate-700`
- Action links: `text-indigo-600 hover:underline`

### 9. `resources/views/deliveries/form.blade.php`
- Card: glassmorphism
- School cards: `rounded-2xl border border-slate-200 bg-white/75 backdrop-blur-xl p-5 shadow-sm`
- School code: `text-xs font-semibold uppercase tracking-wide text-slate-500`
- School name: `text-lg font-semibold text-slate-950` (lang=bn)
- "Already entered" badge: `bg-slate-100 text-slate-700`
- Inputs: `border-slate-300 focus:border-indigo-600`
- Labels: `text-xs font-semibold uppercase tracking-wide text-slate-500`
- Primary button: `rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700`
- Date picker form: `border-slate-300` inputs, `bg-slate-900` load button → change to `bg-slate-900` (neutral) or `bg-indigo-600`

### 10. `resources/views/deliveries/index.blade.php`
- Card grid: glassmorphism
- Empty state: `text-slate-600`

### 11. `resources/views/schools/partials/*.blade.php` (if any)
- Apply same input/card styles

### 12. `components/ui/aurora-background.tsx`
- **Delete file** (unused in Blade; React not rendered)

### 13. `vite.config.js`
- Remove `aurora.tsx` from input if referenced (not currently)

---

## Validation Checklist

After implementation, verify:

1. **Login page** — no Aurora animation; glass card centered; indigo button; charcoal heading; slate subtext
2. **Admin dashboard** — 4 glass cards in grid; indigo primary button; indigo links; charcoal titles
3. **Field home** — 3 glass cards; consistent spacing/typography
4. **School forms** — all inputs use indigo focus rings; glass card; indigo save button
5. **School index** — glass table; indigo action links; slate headers
6. **Delivery entry** — glass school cards; indigo record button; slate labels
7. **Global** — header backdrop-blur; indigo logo; indigo nav links
8. **No blue-700 remains** — search codebase for `blue-700`, `blue-600`, `blue-800` in Blade/CSS
9. **Build passes** — `bun run build` succeeds
10. **TypeScript passes** — `bunx tsc --noEmit` succeeds
11. **Tests pass** — `php artisan test` passes

---

## Rollout

1. Apply CSS theme + layout changes first (affects all pages)
2. Update each view file in parallel (no cross-page dependencies)
3. Delete Aurora component
4. Run build + tests
5. Visual regression: compare login, dashboard, school form, delivery form

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Glassmorphism readability on busy backgrounds | Background is solid `slate-50`; glass cards have `border-slate-200` + shadow |
| Focus ring contrast on glass cards | `focus:ring-indigo-100` (10% opacity) on `bg-white/75` — test with keyboard nav |
| Existing `blue-700` classes missed | Grep for `blue-` in `resources/views/**/*.blade.php` and `resources/css/**/*.css` |
| Tailwind v4 `@theme` not picking up custom colors | Verify with `bun run build` and inspect output CSS |
| Print styles broken | Keep existing `@media print` block unchanged |

---

## Open Questions (Resolved by Spec)

- ✅ Glassmorphism vs solid — **Glassmorphism**
- ✅ Aurora keep/remove — **Remove entirely, solid slate-50**
- ✅ Custom @theme vs arbitrary — **@theme in app.css**

---

## Implementation Order

1. `resources/css/app.css` — theme + utilities + Aurora removal
2. `resources/views/layouts/app.blade.php` — global shell
3. `resources/views/auth/login.blade.php` — entry point
4. `resources/views/admin/dashboard.blade.php` — admin entry
5. `resources/views/field/home.blade.php` — field entry
6. `resources/views/schools/*.blade.php` — school module
7. `resources/views/deliveries/*.blade.php` — delivery module
8. Delete `components/ui/aurora-background.tsx`
9. `bun run build` + `php artisan test` + visual check