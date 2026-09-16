# UI/UX redesign — design system and view rules

**Date:** 2026-09-15
**Sub-project:** 3 of 3 (languages → cleanup → UI redesign)
**Status:** Decided autonomously on the user's request ("volledige revamp"); review afterwards.

## Brief

Audio Translator turns a recording into the same message in another language and voice: upload → review transcript → review translation → new audio. Also text-to-speech, reusable voice style presets, credits, and an admin area (users, payments, files, bulk CSV/XLSX translation).
Audience: content teams, educators and small businesses who need multilingual audio. Primary job of the UI: move one recording through the review steps with confidence.
User feedback: the current UI looks "AI-generated and outdated" (dark near-black, indigo glow, gradient text, emoji, generic icon-tile grids, two clashing styles, hundreds of inline styles).

## Design plan

### Color (light, cool; color carries state, not decoration)

| Token | Hex | Use |
|---|---|---|
| paper | `#f6f7f9` | page background |
| surface | `#ffffff` | panels, inputs |
| sunken | `#eef0f4` | hover rows, neutral chips, tracks |
| ink | `#1b1f27` | text |
| muted | `#5e6573` | secondary text |
| faint | `#8a909c` | icons, placeholders |
| line / line-strong | `#dde1e7` / `#c5cbd4` | borders |
| accent | `#2a3bc4` (hover `#2230a3`, soft `#e8ebfb`) | the only action color: primary buttons, links, focus, active nav, progress |
| review | `#b86e0b` (soft `#fdf1dc`) | "waiting for your review" states |
| success | `#1e7a50` (soft `#e3f3ea`) | ready / paid |
| danger | `#b8322a` (soft `#fbe7e5`) | failed / destructive |

### Type
- **Bricolage Grotesque** (600–700): headings and language codes.
- **Atkinson Hyperlegible Next** (400/500/700): everything else — chosen for legibility, fitting an accessibility product.
- Scale: 14 / 15 / 16 (body) / 18 / 22 / 30–34 (page title) / 40–56 (landing hero). Sentence case everywhere; no uppercase labels, no eyebrow labels, no gradient or single-word accent in headlines. Reading blocks ≤ 70ch.

### Layout
- **App shell** (`layouts.app`): fixed left sidebar (≥1024px) with wordmark, primary nav, admin nav (admins only), remaining-translations meter and account/logout; content column max 1040px, left-aligned, 24–40px gutters. Below 1024px: top bar with menu button toggling the same nav.
- **Guest shell** (`layouts.guest`): slim top bar (wordmark + log in / get started or dashboard), content, small footer. Used by welcome, login, register, admin login.
- Page anatomy: `<x-page-header>` (title, one-line description, actions) → content in `<x-panel>`s. Radius hierarchy: 14px panels, 10px controls, pill only for statuses. No shadows except overlays.

### The one memorable element
The **language pair** set in large display type (`<x-language-pair from="nl" to="pl" size="lg"/>`): on the translation detail page, list rows and the landing hero. Everything around it stays quiet.

### Principles
1. The workflow is the structure: the detail page shows the real pipeline as a numbered step list (it is a sequence), with the current step highlighted.
2. Color means state: accent = act, amber = your review is needed, green = done, red = failed.
3. No decoration: no glows, gradients, blur, floating/pulsing icons, emoji, icon-tile feature grids, fade-up on every section.
4. Copy is plain and specific; buttons say what happens ("Approve transcript", "Generate audio").
5. Quality floor: responsive to 360px, visible focus, `prefers-reduced-motion` respected, WCAG AA contrast.

## Building blocks

CSS (`resources/css/app.css`, Tailwind v4): theme tokens become utilities (`bg-paper`, `bg-surface`, `bg-sunken`, `text-ink`, `text-muted`, `text-faint`, `border-line`, `border-line-strong`, `text-accent`, `bg-accent-soft`, `text-review`, `bg-review-soft`, `text-success`, `text-danger`, `font-display`, `rounded-control`, `rounded-panel`). Component classes: `btn btn-primary|secondary|ghost|danger [btn-sm|btn-lg]`, `panel`, `panel-body`, `field-label`, `field-hint`, `field-error`, `input`, `select`, `textarea`, `is-invalid`, `status status-neutral|progress|review|success|danger`, `alert alert-success|error|warning|info`, `table` (wrap in `overflow-x-auto`), `nav-item`, `lang-pair`, `lang-code`, `progress` (child `<span style="width:…">`), `dropzone` (+ `.drag-over`).

Anonymous Blade components (`resources/views/components`):

| Component | Props / slots |
|---|---|
| `<x-button>` | `variant` primary\|secondary\|ghost\|danger, `size` sm\|lg, `href`, `type`, `icon` (Font Awesome free name without `fa-`); other attributes merge |
| `<x-panel>` | `title`, `description`; slot `actions`; default slot is the body |
| `<x-page-header>` | `title`, `description`, `back` (url), `backLabel`; slot `actions` |
| `<x-status>` | `status` (audio/text-to-audio/payment/csv status), optional `label` override |
| `<x-alert>` | `type` success\|error\|warning\|info |
| `<x-field>` | `label`, `for`, `hint`, `error` (validation key); default slot is the control |
| `<x-language-pair>` | `from`, `to`, `size` md\|lg |
| `<x-empty-state>` | `title`, `description`, `icon`; slot `action` |

Icons: Font Awesome 6 **free** solid set only (`fa-solid fa-…`); Pro-only names are not allowed.

## Rules for rewriting a view

1. Extend `layouts.app` (authenticated/admin pages) or `layouts.guest` (welcome, login, register, admin login). No standalone `<!DOCTYPE>` pages.
2. No inline `style=""` except dynamic values (e.g. progress width). No `<style>` blocks. Layout with Tailwind utilities; appearance via the component classes/components above.
3. Keep behavior identical: every route, form `action`/`method`, `@csrf`/`@method`, input `name`, `value`, `id` used by JS or tests, `@error` keys, `old()` handling, `@include('partials.language-options', …)`, pagination and authorization checks.
4. Keep page scripts working. You may rewrite them for clarity, but keep the same endpoints, request payloads, polling and DOM-updates; update selectors if you change markup. Scripts go in `@push('scripts')`.
5. Keep strings that tests assert (see `tests/Feature/Language*Test.php`): language counts from `count(config('audio.languages'))` including the phrases "N Languages", "All N languages", "from N options", "N supported languages", "Select from N languages"; the CSV preset order line (twice) and `name="languages[]" value="…"` checkboxes.
6. Copy: sentence case, plain verbs, English (the app's current language). Remove "AI-powered" hype, emoji and exclamation marks in UI text (flash messages from controllers are out of scope).
7. Status labels come from `<x-status>`; language codes are shown with `<x-language-pair>` or `.lang-code`.

## Out of scope
Controller/route/business-logic changes, translating the UI into Dutch, dark mode.
