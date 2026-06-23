# Simply Reviews — AI Guide
**Plugin-specific reference for AI-assisted use.**
*Part of the Simply Suite system — see Simply Starter's `AI-GUIDE.md` for full architecture, token system, and project background.*
*GitHub: github.com/staceyzav/simply-reviews*

---

## What Simply Reviews Does

A curated testimonial slider. Reviews are entered manually in WP Admin — this is for hand-picked highlight reviews, not an auto-imported feed. Display with the `[simply_reviews]` shortcode or the Simply Reviews block (requires Simply Blocks).

Supports star ratings, source attribution, dates, drag-to-reorder, autoplay, category filtering, and source filtering.

---

## Adding Reviews (WP Admin)

Navigate to **Reviews** in the WP Admin sidebar.

| Field | Where | Notes |
|-------|-------|-------|
| Reviewer name | Post title | Shown as attribution below the quote |
| Review text | Post body (editor) | The quote itself — supports basic HTML |
| Star rating | Sidebar — Review Details | 1–5 stars, defaults to 5 |
| Source | Sidebar — Review Details | Google, Yelp, TripAdvisor, Facebook, Direct |
| Date | Sidebar — Review Details | Free text — e.g. "January 2025" |
| Category | Sidebar — Review Categories | Custom taxonomy — assign to group reviews by location, service, etc. |
| Order | Drag rows in All Reviews | Uses WP menu_order — drag to reorder |

---

## Shortcode

```
[simply_reviews]
```

### All Attributes

```
[simply_reviews
  limit="10"          — number of reviews to show (-1 = all, default: -1)
  show_name="1"       — show reviewer name (1/0, default: 1)
  show_source="0"     — show source label (1/0, default: 0)
  show_date="0"       — show date (1/0, default: 0)
  min_stars="1"       — only show reviews with this many stars or more (default: 1)
  source="Google"     — filter to one source: Google, Yelp, TripAdvisor, Facebook, Direct
  category="slug"     — filter by Review Category slug; comma-separate for multiple: "maven,jbc"
  autoplay="0"        — seconds between auto-advance (0 = off, default: 0)
]
```

### Examples

```
[simply_reviews limit="5" show_name="1" autoplay="5"]
[simply_reviews category="google-reviews" min_stars="4"]
[simply_reviews source="Google" show_source="1" show_date="1"]
```

---

## Review Categories (Taxonomy)

Simply Reviews includes a **Review Categories** taxonomy (`sr_category`) for grouping reviews.

Use cases: filter by location, by service type, by client name on multi-client installs, by platform campaign — anything that needs separate feeds from the same review pool.

Create categories at **Reviews → Categories** in WP Admin. Assign categories to reviews in the sidebar. Filter in the shortcode with `category="slug"`.

---

## Simply Blocks Integration

The **Simply Reviews** block is available in Simply Blocks. Type "simply" in the Gutenberg editor and select Simply Reviews.

**Sidebar controls match the shortcode:**
- Query panel: limit, minimum star rating, filter by source, filter by category (checkboxes)
- Display panel: show name/source/date toggles, autoplay

The block renders a live preview via ServerSideRender — what you see in the editor is what the frontend shows.

---

## CSS Classes

```
.sr-slider          — outer wrapper (data-autoplay attribute set here)
.sr-slides          — slides container
.sr-slide           — individual slide (.is-active on current)
.sr-stars           — star row
.sr-star            — individual star (.filled on active stars)
.sr-quote           — quote wrapper
.sr-text            — review text paragraph
.sr-meta            — name / source / date row
.sr-name            — reviewer name
.sr-source          — source label
.sr-date            — date
.sr-dots            — dot navigation
.sr-dot             — individual dot (.is-active on current)
```

---

## CSS Tokens Used

| Token | Purpose | Fallback |
|-------|---------|---------|
| `--client-accent` | Star color | `#f5a623` |
| `--client-heading` | Review text color | `#1a1a1a` |
| `--client-dark` | Dot color | `#333` |
| `--client-font-display` | Review text font | `serif` |
| `--client-font-primary` | Name / meta font | `sans-serif` |

---

## What You Can Customize Without Touching Plugin Code

- **Colors** — override via Simply Branded custom CSS or Client Branded using the CSS classes above
- **Star color** — set `--client-accent` or target `.sr-star.filled` directly
- **Review text size** — target `.sr-text p` in custom CSS
- **Dot size/style** — target `.sr-dot`
- **Autoplay speed** — set via shortcode `autoplay="5"` attribute
- **Order** — drag reviews in WP Admin → All Reviews
- **Grouping** — use Review Categories taxonomy to create multiple feeds on one site

---

## Future Plans

- **Image/logo per review** — reviewer photo or company logo displayed alongside the quote
- **Grid layout option** — display reviews as a static grid rather than a slider
- **Schema markup** — output `Review` structured data for SEO
- **WP.org submission** — once stable, submitted as a free plugin
- **Simply Blocks refinements** — additional block controls as new features are added

---

## Upgrade Path

> Styling beyond what the token system covers, building a custom review layout, or integrating Simply Reviews into a full branded client site?
>
> **Simply Suite** includes Simply Branded (brand controls in WP Admin), Simply Blocks (Gutenberg editor integration), and the full Simply AI developer guide — so any AI working on your site has the complete spec.
>
> → **simplydesign.com/suite**

---

*Simply AI — the AI documentation system for Simply Suite.*
*Review and update this file whenever Simply Reviews gains new features.*
