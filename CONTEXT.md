# ADHERI — Advocates for Health & Rights of Women and Youths Initiative

Custom PHP web application and admin CMS that replaces the current WordPress site at https://advocatesforhealthandrights.org/. This document is the single source of product truth: domain language, brand, every public page, every admin capability, every engine, every form, and every offering the organisation publishes. Build the full system against this document.

---

## Organisation

**Legal / public name:** Advocates for Health & Rights of Women and Youths Initiative  
**Short brands in use:** ADHERI, AHRWYI  
**Identity:** Women- and youth-led Nigerian NGO operating by feminist leadership principles.  
**Focus:** End stigma, human rights violations, gender-based violence (GBV), substance misuse, and climate-change effects on women and youth — aligned to SDGs 1, 3, 5, 10, and 13.  
**Methods:** Advocacy, counselling, empowerment, rehab referral, media, storytelling, art and culture (artivism), community programming, stakeholder engagement.  
**Named partnerships / projects (migrate into content):** Human Rights and Arts (British Council support); Women’s Rights in Human Rights; Healing with Maria storytelling program (DSTV); trainings and related programs.

**Jobs of the website**
1. Build trust and clarity about who ADHERI is.
2. Show programs and proof of work (gallery, testimonials, blog).
3. Convert visitors: donate, volunteer, contact, download policy.
4. Let staff update all site content, media, and records without developers.

**Audiences**
- Public / community members seeking information or help paths.
- Donors and partners evaluating credibility and impact.
- Prospective volunteers.
- Staff operating the admin CMS.

---

## Language

### Organisation & people

**Organisation:**
Advocates for Health & Rights of Women and Youths Initiative (ADHERI).
_Avoid_: “the charity” as a substitute name; vague “NGO site” without ADHERI.

**Person:**
A board member or youth advisory member with name, role group, photo, bio, and sort order.
_Avoid_: Staff (unless later expanded), User (for public faces).

**Board of Trustees:**
The Organisation’s trustee body displayed on About.
_Avoid_: Directors (unless content explicitly uses it).

**Youth Advisory Board:**
Women-led youth advisory members displayed on About.
_Avoid_: Interns, volunteers (different concepts).

**AdminUser:**
A staff account that signs into the admin CMS.
_Avoid_: User when meaning AdminUser; Client.

**RecoveryEmail:**
A personal email address belonging to an AdminUser or MailUser, collected at first sign-in, used only to receive password-reset OTPs. Must differ from the account login address.
_Avoid_: Shared owner inbox; global PASSWORD_RECOVERY_INBOX; product-owner Gmail as everyone’s fallback.

**MailUser:**
A mailbox account on ADHERI Mail (@MAIL_DOMAIN).
_Avoid_: AdminUser (CMS); generic User when meaning MailUser.

### Content & programs

**Page:**
A public route whose section content is managed in admin (Home, About, Programs shell, Contact, Volunteer, Donate, etc.).
_Avoid_: Screen, view (when meaning the CMS-managed page).

**PageSection:**
An editable block on a Page (hero, copy, image, CTA, strip).
_Avoid_: Widget, module (UI sense).

**Program:**
A thematic work area ADHERI runs (Advocacy, Counselling, Empowerment, Climate Change, Media, Rehab Support, Artivism), with title, summary, body, image, order, and home-feature flag.
_Avoid_: Service, product, offering (prefer Program in code and admin).

**Announcement:**
A homepage flyer/poster item (image, optional title, optional link, order, publish state / window).
_Avoid_: Event (this is not a dated Events CMS); Campaign (unless later defined).

**SdgBadge:**
A managed SDG mark shown on Home (SDGs 1, 3, 5, 10, 13).
_Avoid_: Hardcoded SDG images outside the SDG engine.

**Testimonial:**
A named community quote for Home (quote, name, location, optional photo, order, publish). Presented with a fixed five-star trust mark on the public site — not a variable rating field.
_Avoid_: Review, star rating as stored data.

**Post:**
A blog article (title, slug, excerpt, body, cover, category, status, published_at, SEO fields). Body is authored in the admin Docs-like TipTap editor (HTML). Google Docs API is not used — that API only creates/reads/batchUpdates Drive documents and does not expose an embeddable editor UI.
_Avoid_: Article as the entity name (category may be “Articles”).

**Category:**
A blog taxonomy label (existing: Articles).
_Avoid_: Tag (unless tags are added as a separate term).

**Document:**
A downloadable file managed in admin (e.g. GBV & Arts Policy).
_Avoid_: Attachment when meaning a public policy download.

### Media & gallery

**MediaAsset:**
An uploaded image, video, or file in the media library (with variants, alt text, metadata).
_Avoid_: Attachment, upload (as the noun for the stored object).

**Album:**
A named gallery group (title, slug, cover, description, date, sort, publish) containing MediaAssets.
_Avoid_: Folder, gallery page (the Gallery index lists Albums).

### Engagement & money

**Inquiry:**
A form submission from Contact or Volunteer (type discriminates the two).
_Avoid_: Lead, ticket, message (as the entity name).

**Donation:**
A Flutterwave-backed gift record (amount ≥ ₦1,000, donor fields, status, gateway reference).
_Avoid_: Payment (generic), Tip.

**SiteSetting:**
Global configuration: logo, contact, address, map URL, socials, Flutterwave keys, minimum donation amount, mail, defaults.
_Avoid_: Config dump, options (prefer SiteSetting).

**Role:**
Permission set for an AdminUser (e.g. Super Admin, Editor, Viewer).
_Avoid_: Permission group without Role entity.

---

## Brand system (locked to live site)

Migrate and keep ADHERI’s live brand colours. Elevate typography, layout, and motion; do not invent a new primary palette.

| Token | Value | Live source |
|--------|--------|-------------|
| Primary / brand | `rgb(0, 113, 188)` → `#0071BC` | `--wd-primary-color`, accented buttons, link hover, `theme-color` |
| Primary hover | `#333333` / `#242424` | Accented button hover / titles |
| Ink / body text | `rgb(34, 34, 34)` → `#222222` | `--wd-text-color` |
| Titles | `#242424` / `#333333` | Title variables |
| Success / growth green | `#459647` (slider also uses `#3FAA4A`) | Theme / RevSlider accents |
| Gold accent | `#E0B252` | Theme secondary; use sparingly (highlights, policy bar) |
| Surfaces | `#FFFFFF`, `#F7F7F7`, `#F5FBFF` | Buttons / soft sections |
| Logo | `ADHERI-new-logo-1.png` | Header |

**UI chrome from live theme:** Pill / rounded CTAs (~35px radius), primary = brand blue fill + white text, secondary = light gray fill (`#F7F7F7`).

**Typography direction:** Replace Graphik/Arial stack with a distinctive display face + strong UI sans. Banned defaults for this rebuild: Inter, Roboto, Arial as primary UI fonts.

**Visual language:** Editorial civic + artivism — photography-led, dignified, trust-first. Not purple SaaS, not generic AI cream/terracotta, not dark OLED-by-default.

**Motion / density:** Intentional scroll reveals and micro-interactions; airy public layouts; denser calm admin. Respect `prefers-reduced-motion`.

**Icons:** One light icon family (Phosphor Light). No emoji-as-icons.

**Voice:** Plain, active, dignified. CTAs name the action (“Donate”, “Volunteer with us”, “Send message”). No pity framing; centre agency of women and youth.

---

## Complete public sitemap

| Route | Purpose |
|--------|---------|
| `/` | Home |
| `/about` | Organisation story, vision, mission, values, Board, Youth Advisory |
| `/programs` | Full Program catalogue |
| `/gallery` | Album index |
| `/gallery/{album-slug}` | Album detail + lightbox |
| `/blog` | Post index |
| `/blog/{slug}` | Single Post |
| `/contact` | Contact information + Contact form |
| `/volunteer` | Volunteer form (first-party; replaces Bitly) |
| `/donate` | Flutterwave donations (minimum ₦1,000) |
| `/donate/success` | Successful payment return |
| `/donate/failed` | Failed / cancelled payment return |
| `/donate/webhook` | Flutterwave webhook endpoint |
| Policy Document URL | GBV & Arts Policy download (managed Document) |
| Privacy (and related legal pages as required) | Confidentiality-aligned legal content |
| `/admin/*` | Full CMS |

**Primary navigation:** Home · About · Programs · Gallery · Blog · Contact · Volunteer · **Donate** (primary CTA).  
**Global chrome:** Logo, social links (Facebook, X/Twitter, Instagram, LinkedIn), GBV & Arts Policy banner/footer CTA, footer with nav mirror, contact snippet, copyright.

**Social URLs (migrate):**
- Facebook: `https://www.facebook.com/profile.php?id=100086568457221`
- X/Twitter: `https://twitter.com/Advocatenearyou`
- Instagram: `https://www.instagram.com/advocatesnearyou/`
- LinkedIn: `https://www.linkedin.com/company/advocates-for-health-and-rights-of-women-and-youth-initiative/`

**Retire from the new site:** WordPress “Coming Soon” page; off-site Bitly volunteer link as primary path; Flutterwave sandbox redirect as primary donate path; flat non-album gallery dump.

---

## Engines (full system)

Every engine is production-complete and admin-driven.

### Auth & roles
- Admin login / logout / session management.
- CSRF protection on all state-changing requests.
- Roles and permissions (Super Admin, Editor, Viewer — exact matrix implemented in admin).
- Password hashing and account invite / disable.

### Media library
- Upload images, videos, and documents.
- Generate variants / responsive derivatives where applicable.
- Alt text, replace file, delete, search, attach to Pages, Albums, Posts, People, Programs, Announcements.
- Validation of type and size; safe storage paths.

### Page composer
- Editable PageSections for every managed public Page.
- Fields for copy, images, CTAs, order, visibility.
- Home, About, Programs intro, Contact, Volunteer, Donate sections fully editable.

### Programs engine
- CRUD for every Program.
- Reorder / sort.
- Featured-on-home flags.
- Image + rich text body.
- Home teasers pull from this engine (not duplicate hardcoding).

### Announcements / flyers
- Homepage flyer strip items: image, title, optional link, sort order, publish state, optional schedule window.
- Migrate existing flyer art (artists call, Instagram live, training invite, policy roundtable, “The Nigeria We Want”, red flag, ADHERI graphics, etc.).

### SDG / impact strip
- Manage SdgBadges shown on Home (1, 3, 5, 10, 13).
- Image/label/link fields as needed.

### Testimonials
- CRUD: quote, name, optional photo, order, publish.
- Migrate: Iniobong, Ama, Cyril.

### People
- Board of Trustees and Youth Advisory Board.
- Fields: photo, name, group, bio, order, publish.
- About page “Read bio” / expandable bio behaviour.

### Albums & gallery
- Album CRUD: title, slug, cover, description, date, sort, publish.
- MediaAsset membership, drag-reorder, captions, alt text.
- Public Album index + Album detail + lightbox (keyboard, swipe, captions).
- Migrate existing ~200+ gallery photographs into Albums.

### Blog
- Post drafts, publish, unpublish.
- Slugs, covers, excerpts, body, Category, SEO fields.
- Category management (seed: Articles).
- Migrate four existing Posts.

### Inquiries
- Intake for Contact and Volunteer forms (`Inquiry` type).
- Admin inbox: filter by type, mark read, archive.
- Email notification to Organisation addresses on new Inquiry.

### Donations
- Initiate Flutterwave payment from `/donate`.
- Server-side enforce amount ≥ ₦1,000 (configurable minimum in SiteSetting).
- Preset amounts and custom amount.
- Verify callback / webhook; update Donation status; store gateway reference.
- Admin ledger: list, filter, status, amounts; export as needed.
- Success and failed return pages.
- Flutterwave public/secret keys live only in env / SiteSetting (operator-supplied).

### Documents
- Upload/replace public Documents (GBV & Arts Policy and future files).
- Stable public download URL; sitewide banner/footer CTA.

### Site settings
- Logo, favicons.
- Contact emails, phones, address, Google Map URL.
- Social URLs.
- Flutterwave keys and minimum donation amount.
- Mail / notification recipients.
- Default SEO fallbacks.

### Mail / notifications
- Inquiry alerts.
- Optional donation confirmation emails.
- Notification logging where useful for support.

### SEO
- Per-Page / Post title, description, Open Graph image.
- Sensible defaults from SiteSetting.

### Security & quality bar
- Upload validation; rate limits on public forms.
- Webhook signature verification for Flutterwave.
- Confidential handling of Inquiry PII (aligns with Confidentiality value).
- Admin audit log for sensitive actions.
- Accessibility: contrast on brand blue/white, visible focus, 44px targets, labelled inputs, lightbox a11y, `prefers-reduced-motion`.
- Performance: lazy images, reserved aspect ratios, responsive srcset/WebP where possible, mobile-first (Nigeria).

---

## Domain objects (entities)

Page · PageSection · Program · Announcement · SdgBadge · Testimonial · Person · Album · MediaAsset · Post · Category · Inquiry · Donation · Document · SiteSetting · AdminUser · MailUser · RecoveryEmail · Role · NotificationLog

---

## Public pages — content, layout, behaviour

### Home `/`

**Content to preserve and elevate**
- Mission: end injustice and violence that plague vulnerable minority groups, youth, and marginalized women in Nigeria; join in making the world better.
- About teaser: women and youth-led; feminist leadership; stigma, human rights violations, GBV, substance misuse, climate effects; SDGs 1, 3, 5, 10, 13; media, storytelling, art/culture, community programming, stakeholder engagement; British Council / Healing with Maria / trainings.
- SDG badges: 1, 3, 5, 10, 13.
- Presence / community imagery (scrolling photographic strips).
- Upcoming Programs & Announcements flyer rail.
- Program teasers: Advocacy, Counselling, Empowerment, Rehab Support → link into Programs.
- Testimonials: eight community voices with location and photo (Iniobong, Ama, Cyril, and expanded seed set); Home marquee with five-star trust mark.
- CTAs: About, Donate / Support; GBV & Arts Policy download.

**Layout**
1. Full-bleed hero — brand presence, one mission line, CTAs About + Donate.
2. About band — editorial split (copy + photography).
3. SDG row — five SdgBadges.
4. Presence / “Where we work” image sequence (admin MediaAssets).
5. Announcements — horizontal premium flyer rail.
6. Programs — asymmetric editorial blocks (not four identical cards).
7. Testimonials — carousel / slider.
8. Closing action band — Donate + Volunteer + policy Document.

**Admin-owned:** All sections above via PageSections, Announcements, SdgBadges, Programs (featured), Testimonials, MediaAssets, Documents.

---

### About `/about`

**Content**
- Full organisation narrative (as on live About).
- Program chips historically listed on About: Advocacy, Counselling, Storytelling, Empowerment and Training, Substance Misuse Programs, Climate Change, Media (Programs engine remains source of truth for full Program pages).
- **Vision:** A world where everyone’s voice is heard.
- **Mission:** Empower minority and vulnerable youth and amplify voices by: feminist principles and evidence-based interventions; community needs and stakeholder involvement; media, art, culture, storytelling to reduce stigma/discrimination/HR violations; addressing root causes (poverty, violence, health, education, empowerment).
- **Core values (full):**
  - **Community Driven** — programs curated for communities; community-centred impact.
  - **Accountability** — honest, transparent; evaluate and communicate outcomes; accountable to partners, supporters, donors, communities.
  - **Confidentiality** — privacy for persons served; data protection; anonymous benefactors; protect non-conforming identities from stigma.
  - **No Stigma** — respect intersecting identities; empower all genders; no discrimination on beliefs/wellbeing.
  - **Collaborative** — trusting relationships, partnerships, alliances for progressive change.
  - **Time** — timely interventions; seasonality of impact; programming cycle.
- **Board of Trustees (migrate bios):**
  - Emmanuel Etim — multi-sector advocacy, GBV, mental health, media, training; youth transformation.
  - Nnenna Okoli — international development; poverty, social protection, gender equality, empowerment.
  - Melody Otutuloro — industrial chemist; design/aesthetics; women’s development and empowerment.
  - Emmanuel Gabari — broadcaster; UN Youth Parliament Ambassador; youth development; weekly radio.
  - Umanta Ude Umanta — notary public / lawyer; University of Ibadan LLB; oil & gas legal leadership; youth advocacy.
- **Women-led Youth Advisory Board:** Omoye Oriaghan, Emerald Emmanuel, Oluwadolapo Ojo, Victoria Oletu, Charles Egbuokporo.

**Layout:** Story hero → vision / mission → values → Board (portraits + expandable bios) → Youth Advisory → CTA Donate / Volunteer.

---

### Programs `/programs`

**Intro copy:** Everyone deserves dignity, respect, and opportunity regardless of gender, race, ethnicity, religion, health, and status; working toward SDGs 1, 3, 5, 10, and 13.

**Every Program (first-class, admin-editable)**

1. **Advocacy**  
   Rights and wellbeing of vulnerable and minority groups; stigma, GBV, drug misuse, poverty; educate/empower/transform through evidence-based interventions including Women’s Rights in Human Rights advocacy events, Running on Heels, Human Rights and Arts.

2. **Counselling**  
   Rise in need for counselling and psychosocial support; counsellors, therapists, mental health doctors; therapeutic tele-counselling across substance misuse, sexual abuse, and GBV; hybrid tele-counselling or one-on-one.

3. **Empowerment**  
   Soft skills, digital marketing, coding training, skills acquisition, startup kits, internship opportunities; partnerships with companies, individuals, corporate organisations.

4. **Climate Change**  
   Succour to women and girls who suffer GBV due to climate actions; psychosocial support, welfare, therapy, training, empowerment.

5. **Media**  
   Media engagement and programming; media cohort for personnel to advocate on GBV and societal ills; storytelling as advocacy tool.

6. **Rehab Support**  
   Referral support to rehab centres across Nigeria; affordable healthcare access; reintegration via opportunities and job volunteering; regain trust and confidence.

7. **Artivism (Arts & Activism)**  
   Fusion of art and activism for social change; amplify artists/activists for health and rights of women and youth.  
   - **(a) Creating Safe Spaces Through Arts Project** — inclusive spaces; creativity, dialogue, collaboration; art for healing, self-discovery, resilience.  
   - **(b) Human Rights and Arts** — visual arts, music, dance, theatre; challenge stereotypes; ignite conversations.  
   - **(c) Training for Artists** — periodic workshops so artists leverage platforms for advocacy; focus gender equality, reproductive health, youth empowerment; inspire action and policy change.

**Layout:** Page hero → sticky program index (desktop) / chip scroller (mobile) → each Program: image + title + full rich text (+ optional deep link into related Album).

---

### Gallery `/gallery` and `/gallery/{album-slug}`

**Public**
- Index: premium Album covers in asymmetric / bento-like grid; title, media count, date, short description.
- Album detail: cover hero → masonry or justified photo grid → lightbox with keyboard navigation, swipe, captions.
- Migrate all current gallery photographs (~200+) into curated Albums.

**Admin**
- Create / edit / publish Albums.
- Bulk upload MediaAssets; drag-sort; set cover; captions and alt text; unpublish.

---

### Blog `/blog` and `/blog/{slug}`

**Category:** Articles.

**Posts to migrate**
1. Advocating for Change: Our Mission, Impact, and How You Can Get Involved.
2. Men and Sexual Abuse.
3. How climate change fuels gender-based violence — and what we can do about it.
4. Ending The Bystander Syndrome Could End Gbv.

**Layout:** Editorial index (cover, title, date, excerpt) → long-form Post with brand typography; related Posts.

**Admin:** Full Post editor, draft/publish, covers, slugs, Category, SEO.

---

### Contact `/contact`

**Contact information (migrate)**
- Emails: `contactus@advocatesforhealthandrights.org`, `info@advocatesforhealthandrights.org`
- Phones: `(+234) 0818 422 3082`, `(+234) 0906 628 3022`
- Address: Advocates for Health and Rights of Women and Youths Initiative — Asap Chops Building, Jakande First Gate, Lekki, Lagos (After iFitness & FCMB)
- Google Map: `https://maps.app.goo.gl/1GGTkjmDNTQRbAeE8`

**Form fields:** First name, Last name, Email, Phone, Message → creates Inquiry (`type=contact`) + email notify.

**Layout:** Split — information left, form right; clear success state. Heading spirit from live site: “Do you have any questions? We're here for you.” / “Stay connected” / “Fill form below — We'll get back to you soon.”

---

### Volunteer `/volunteer`

First-party page replacing `https://bit.ly/volunteer247` as the primary volunteer path.

**Content:** Short “why volunteer” band tied to community / artivism / programs.

**Form fields:** Full name, Email, Phone, City/State, Areas of interest (multi-select: Advocacy, Counselling, Media, Artivism, Events, Admin/Operations, Other), Availability note, Message → Inquiry (`type=volunteer`) + email notify.

**Layout:** Editorial intro + form; success state with expectation of follow-up.

---

### Donate `/donate` (+ success / failed / webhook)

First-party Flutterwave flow replacing the sandbox Heroku donate link as the primary path.

**Rules**
- Minimum amount ₦1,000 (SiteSetting; enforced server-side).
- Presets: ₦1,000 / ₦5,000 / ₦10,000 / ₦25,000 / custom.
- Fields: Name, Email, Phone (optional), Amount.
- Create Donation → redirect/checkout via Flutterwave → webhook and return URLs update status.
- Admin ledger lists all Donations with gateway reference and status.

**Layout:** Trust-led mission line; brand-blue primary pay CTA; gold only as sparing highlight if needed; success and failed pages with clear next steps (Home, Contact).

---

### GBV & Arts Policy (global)

- Sitewide banner and/or footer: “Click here to download our new GBV & Arts Policy” (copy may be refined; intent preserved).
- Served from Documents engine (host file on the application; Bitly may be retired once hosted).

---

## Admin application — full module list

| Module | Features |
|--------|----------|
| Dashboard | Counts and snapshots: new Inquiries, recent Donations, draft Posts, media storage overview |
| Pages | Edit all PageSections for Home, About, Programs intro, Contact, Volunteer, Donate |
| Programs | Full CRUD, reorder, home feature flags, images, rich text |
| Announcements | Flyer CRUD, links, schedule/publish, order |
| SDGs | SdgBadge images/labels/links |
| Testimonials | CRUD, order, publish |
| People | Board of Trustees + Youth Advisory Board |
| Media library | Upload, search, replace, alt text, delete |
| Albums | Album CRUD, membership, sort, covers, publish |
| Blog | Post editor, Category, draft/publish, SEO |
| Inquiries | Inbox for Contact and Volunteer; status; archive |
| Donations | Ledger, statuses, amounts, gateway refs, export |
| Documents | Policy and other public file upload/replace |
| Settings | Logo, contact, address, map, socials, Flutterwave keys, min amount, mail recipients, SEO defaults |
| Users & roles | Invite AdminUsers, assign Roles, disable accounts |
| SEO | Per-route meta fields |
| Security tools | Session management, audit log visibility for privileged Roles |

**Admin visual tone:** Same brand tokens; denser, calmer utility UI; low motion relative to the public site.

---

## Forms (complete)

| Form | Fields | Result |
|------|--------|--------|
| Contact | First, Last, Email, Phone, Message | Inquiry `contact` + notify |
| Volunteer | Name, Email, Phone, Location, Interests[], Availability, Message | Inquiry `volunteer` + notify |
| Donate | Name, Email, Phone?, Amount (≥ minimum) | Donation + Flutterwave |
| Admin login | Email, Password | Admin session |
| CMS entity forms | Per engine fields | Persist with CSRF + validation |

All public forms: visible labels, inline errors, success states, rate limiting, spam resistance as appropriate.

---

## Feature ↔ surface map

| Feature | Public | Admin |
|---------|--------|--------|
| Site chrome, logo, socials | Yes | SiteSetting |
| Page copy and heroes | Yes | Page composer |
| Announcements | Home strip | Announcements engine |
| SDG strip | Home | SDG engine |
| Programs | Home teasers + Programs page | Programs engine |
| Albums / MediaAssets | Gallery index + album + lightbox | Media + Albums |
| Blog Posts | Index + single | Blog engine |
| Testimonials | Home | Testimonials engine |
| People | About | People engine |
| Contact | Page + form | Inquiries + Settings |
| Volunteer | Page + form | Inquiries |
| Donate | Page + returns + webhook | Donations + Settings |
| Policy Document | Banner/footer download | Documents |
| SEO | Meta tags | SEO fields |
| Auth | — | Auth & roles |

**Explicitly out of product scope (do not build):** A newsletter product platform (integrate later only if ADHERI already has an external newsletter tool). A full dated Events CMS (Announcements remain flyer/media strips). New marketing sitemap sections beyond the locked IA above.

---

## WordPress migration checklist (complete)

Migrate into the new application:
- All public copy listed in this document.
- Logo and brand colours.
- Board of Trustees and Youth Advisory Board.
- All seven Programs (including Artivism sub-initiatives).
- Testimonials (Iniobong, Ama, Cyril + five additional community voices with location/photo).
- SDG artwork (1, 3, 5, 10, 13).
- Announcement / flyer images currently on Home.
- Entire gallery photograph set into Albums (~200+ assets; newer studio uploads included).
- Four blog Posts + Articles category.
- Contact emails, phones, address, map URL.
- Social profile URLs.
- GBV & Arts Policy file into Documents.
- Home scrolling / community photography assets.

---

## Implementation shape (system)

Custom **PHP** application with **MySQL**, server-rendered templates, `public/` front controller, `/admin` CMS, filesystem or S3-compatible media storage, Flutterwave adapter behind a payment gateway seam, and mailer notifications.

**Deep modules (seams):** Auth · Content (Pages/Sections) · Programs · Announcements · SDGs · Testimonials · People · Media · Albums · Blog · Inquiries · Donations · Documents · Settings · Notifications.

---

## Source of truth note

When content on the live WordPress site and this file disagree after migration starts, update this `CONTEXT.md` in the same change set as the decision. Domain terms in **Language** are canonical for code, admin labels, and docs.
