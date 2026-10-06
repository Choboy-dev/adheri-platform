# ADHERI UI System Direction

Visual reference for the public site and admin CMS. Product scope and domain language live in `CONTEXT.md`. **Colours are locked to the old WordPress brand** so returning visitors still recognise the organisation; typography, layout and motion are where the rebuild had room to move.

---

## Direction

The audience is donors, volunteers, partners and community members in Nigeria, many of them on mid-range Android phones. The site leans editorial: real program photography, big type for the mission, and a light brand-blue palette. It should not look like a SaaS landing page or a pity-driven charity poster.

| | Public site | Admin |
|------|-------------|--------|
| Layout | Asymmetric, magazine-style splits | Plain grids, predictable |
| Motion | Scroll reveals, nothing that blocks reading | Only where it explains state |
| Density | Airy, photos get room | Denser tables and forms |

The one place we spend boldness: full-bleed documentary photography with large display type on the home hero, and gallery albums laid out like an exhibition rather than a thumbnail dump.

---

## Principles

1. **Brand first.** Logo and organisation name are hero-level signals on public pages — never only a tiny nav mark with a louder generic headline.
2. **Dignity over pity.** Imagery and copy centre agency of women and youth; no “victim poster” layouts.
3. **One job per section.** One purpose, one headline, one short support line; no cluttered promo stacks in the first viewport.
4. **Photography is the material.** Real program, community, and artivism images carry atmosphere — not abstract purple meshes.
5. **Editorial, not card farm.** Prefer splits, uneven grids, and paced typography over three/four identical rounded cards.
6. **Trust for money and forms.** Donate and Inquiry flows are calm, explicit, high-contrast, and error-clear.
7. **Admin is a tool.** Same tokens, quieter motion, higher density — staff speed over spectacle.
8. **Access is non-negotiable.** Contrast, focus, keyboard, reduced motion, labelled fields, mobile-first Nigeria.

---

## Colour tokens (locked)

Named CSS variables. Do not replace primary with purple, black-OLED, or warm-cream terracotta systems.

```css
:root {
  /* Brand — live site */
  --brand:            #0071BC; /* rgb(0, 113, 188) */
  --brand-deep:       #0A4F8A; /* hover / pressed / deep accents */
  --brand-soft:       #F5FBFF; /* cool wash from live theme */

  /* Ink & surfaces */
  --ink:              #222222; /* rgb(34, 34, 34) body */
  --ink-title:        #242424;
  --ink-muted:        #5C6670;
  --paper:            #FFFFFF;
  --paper-muted:      #F7F7F7; /* secondary buttons / soft bands */
  --paper-band:       #F5FBFF; /* alternate section wash */

  /* Supporting accents — sparingly */
  --pulse:            #459647; /* success / growth green */
  --pulse-bright:     #3FAA4A; /* legacy slider green; use only if needed */
  --gold:             #E0B252; /* policy bar / rare highlight — never primary CTA fill */

  /* Lines & focus */
  --line:             #D9E2EC; /* cool hairline; avoid heavy gray boxes */
  --ring:             #0071BC; /* focus ring */

  /* Semantic */
  --danger:           #C62828;
  --on-brand:         #FFFFFF;
  --on-ink:           #FFFFFF;
}
```

| Role | Token | Usage |
|------|--------|--------|
| Primary CTA | `--brand` on `--on-brand` | Donate, Submit, Pay |
| Primary hover | `--brand-deep` or live-like `#333333` for text buttons | Hover/active |
| Secondary CTA | `--paper-muted` on `--ink` | About, secondary actions |
| Text | `--ink` / `--ink-title` / `--ink-muted` | Body / headings / meta |
| Success | `--pulse` | Donation success, positive states |
| Policy / highlight | `--gold` | Thin banner accent, never large fills |
| Section alternate | `--paper-band` | Soft blue-white bands |

**Banned:** purple gradients, glassmorphism-everywhere, harsh `shadow-md` black drops, gray 1px card farms, cream `#F4F1EA` + terracotta as the “premium” default.

---

## Typography

**Pairing (locked for build):**

| Role | Family | Why |
|------|--------|-----|
| Display / headlines | **Outfit** | Bold grotesk for mobile-first heroes and section titles — brand punch without Fraunces/Inter defaults |
| UI / body / admin | **Manrope** | Geometric sans with warmth; readable forms and nav; **not** Inter/Roboto/Arial/Graphik |

Self-host or equivalent licensed delivery with `font-display: swap`. Do not load Inter as a fallback primary.

### Type scale (public)

| Step | Size (approx) | Weight | Use |
|------|----------------|--------|-----|
| Display | `clamp(2.35rem, 9vw, 5.2rem)` | Outfit 750–800 | Home/About heroes |
| H2 | `clamp(1.85rem, 5.5vw, 3rem)` | Outfit 750 | Section titles |
| H3 | `1.25–1.5rem` | Outfit 700 / Manrope 650 | Program titles, album titles |
| Body | `1.0–1.125rem` | Manrope 400–500 | Paragraphs (≤70ch) |
| Small / meta | `0.8125–0.875rem` | Manrope 500 | Dates, counts — **sentence case**, not ALL-CAPS spam |
| Button | `0.9–1rem` | Manrope 700–750 | CTAs |

**Line-height:** Display ~1.02–1.12; body ~1.55.

**Avoid:** Single-word accent colour in every headline; tracked-out ALL-CAPS eyebrows on every block; middle-dot meta chains as decoration.

---

## Layout system

### Grid & measure
- Max content width: **1200–1280px** content; full-bleed media breaks out.
- Section vertical rhythm (public): generous — aim **96–160px** section padding on large screens; tighten on mobile without crowding.
- Alignment: **left-aligned** editorial text as default; center only for short ceremonial moments (e.g. single closing CTA line), not every section.
- Breakpoints: 375 · 768 · 1024 · 1440.

### Public layout archetypes (use deliberately)

1. **Full-bleed hero** — edge-to-edge photo or photo+wash; brand + one headline + one support line + CTA group. No inset hero cards, no floating promo chips on the image.
2. **Editorial split** — ~50/50 or 55/45 copy | photography (About, programs, donate trust).
3. **Asymmetric program / feature row** — uneven columns or staggered blocks; never four identical equal cards as the default.
4. **Flyer rail** — horizontal scroll/snap for Announcements; large poster presence.
5. **Album bento** — Gallery index: mixed tile sizes; cover-led; museum pacing.
6. **Long-form column** — Blog posts: narrow measure, Fraunces titles, Manrope body.

### First viewport budget (landing / Home)
Only: **brand**, **one headline**, **one short support sentence**, **one CTA group**, **one dominant image plane**.  
No stats strips, schedule widgets, address blocks, or secondary promos in the hero.

### Navigation
- **Floating island nav** — pill container, not an edge-to-edge glued bar; logo left; links; Donate as brand-filled pill.
- Mobile: hamburger morph → full-screen overlay; staggered link reveal; large tap targets.
- Policy: slim global bar or persistent footer treatment using `--gold` as hairline accent only.

### Admin layout
- Persistent sidebar + top bar.
- Content canvas with clear page titles.
- Tables, filters, and forms on `--paper` / `--paper-muted`.
- Same brand for primary buttons; no Fraunces in dense tables (Manrope throughout admin UI).

---

## Components

### Buttons
- **Primary:** `--brand` fill, `--on-brand` text, pill radius (~999px or ~35px to echo live site), hover `--brand-deep`.
- **Secondary:** `--paper-muted` fill, `--ink` text.
- **Tertiary / text:** `--ink` with `--brand` hover underline or colour.
- **Donate** may stay primary brand blue (not gold fill). Optional gold only as a tiny adjacent marker.
- Press feedback: slight scale (`0.98`); transitions use custom easing (below).
- Icon-in-button: nested circular icon well when an arrow is used — not a naked “→” character spam.

### Links
- Default `--ink`; hover `--brand`. Focus ring `--ring`.

### Forms (Contact, Volunteer, Donate, Admin)
- Visible labels above fields (never placeholder-only).
- Fields: light border `--line`, focus ring `--brand`, radius soft but not playful blobs.
- Errors inline under fields in `--danger`; success banners use `--pulse`.
- Donate amount chips: selectable pills; selected = brand fill.

### Cards / panels
- Prefer **no card** when a section doesn’t need one.
- When needed (admin rows, album tiles): nested soft shell (outer hairline + inner surface), large radius on media, **no** heavy drop shadows.
- Album covers: image-led; title and count below or on restrained overlay — not sticker badges all over the photo.

### Media
- Documentary crop; consistent aspect tokens (e.g. hero 16/9 or 3/2; album cover 4/5 or 1/1 — pick per pattern and keep consistent).
- Lazy load; explicit width/height or aspect-ratio to prevent CLS.
- Lightbox: dark scrim, keyboard trap, captions in Manrope.

### Testimonials
- Large quote in Fraunces or large Manrope; name in meta style; optional portrait — not five-star rating widgets.

### People (Board / Youth Advisory)
- Portrait grid; name + expandable bio; generous gaps; no identical “team card” shadow stacks.

### SDG strip
- Five badges in a calm row; adequate size; alt text; no noisy animation.

### Announcement rail
- Large flyer images; snap scroll; optional link affordance; respect image as poster art.

---

## Motion

**Easing (default):** `cubic-bezier(0.32, 0.72, 0, 1)` — not `linear` / generic `ease-in-out`.  
**Duration:** 200–300ms micro; 500–800ms section entrance.

**Allowed**
- Page-load or scroll: gentle fade-up on key blocks (opacity + transform only).
- Nav overlay open/close; hamburger morph.
- Lightbox open/close.
- Button hover/press.
- Flyer rail scroll (user-driven).

**Forbidden as defaults**
- Parallax on every section.
- Staggered fade-up on every card forever.
- Animating `width` / `height` / `top` / `left`.
- Backdrop-blur on scrolling content (blur only on fixed nav/overlays).

**`prefers-reduced-motion: reduce`:** snap to final states; no scroll-scrub; no parallax.

---

## Iconography & imagery

- **Icons:** Phosphor Light (one family), stroke consistent.
- **Imagery:** Migrate real ADHERI photography and flyers; prefer community, workshops, artivism, advocacy moments.
- **Logo:** `ADHERI-new-logo-1.png` (and retina variants); clear space; never recolour off-brand.

---

## Page-level UI direction (quick map)

| Page | Direction |
|------|-----------|
| **Home** | Full-bleed hero → editorial about → SDG row → presence imagery → flyer rail → asymmetric programs → testimonials → Donate/Volunteer close |
| **About** | Story hero → vision/mission typographic band → values paced list/grid → board portraits → youth advisory → CTA |
| **Programs** | Strong intro → sticky index / chips → editorial program chapters with large images |
| **Gallery** | Album bento index → immersive album detail → lightbox |
| **Blog** | Magazine index → long-form reading column |
| **Contact** | Split info \| form; map link; calm |
| **Volunteer** | Short purpose + clear form; same form language as Contact |
| **Donate** | Trust copy + amount UI + brand pay CTA; success/fail clarity |
| **Admin** | Sidebar app shell; Manrope; brand primary actions; dense readable tables |

---

## Anti-patterns (instant fail)

- Inter / Roboto / Arial / Helvetica as primary UI fonts.
- Purple-on-white or purple mesh “AI SaaS” look.
- Warm cream + terracotta “AI luxury” look.
- Broadsheet hairline-only newspaper pastiche as the whole site.
- Hero with stats, chips, and floating badges.
- Four equal feature cards with soft gray shadows as the Programs language.
- Gold or green as the main Donate button fill (brand blue owns CTAs).
- Edge-glued sticky navbar with no island treatment on public marketing pages.
- Emoji icons; thick Material/Lucide as default.
- Dark mode as the public default.

---

## Quality checklist (ship gate)

- [ ] Contrast ≥ 4.5:1 for text on paper / brand buttons
- [ ] Visible `:focus-visible` rings
- [ ] 44×44px minimum targets
- [ ] Labels on all inputs
- [ ] Reduced-motion respected
- [ ] Responsive: 375, 768, 1024, 1440
- [ ] Hero matches first-viewport budget
- [ ] Gallery feels album/museum-grade
- [ ] Brand blue still reads as ADHERI within one screen
- [ ] No banned fonts or purple/cream-terracotta defaults

---

## Relationship to CONTEXT.md

- **CONTEXT.md** = what we build (features, domain, pages, engines).
- **DESIGN.md** = how it looks and behaves visually.
- If brand colour values change, update **both** in the same decision.
