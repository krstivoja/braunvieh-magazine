# Magazine header: links from the main site

Status: **approved 2026-09-13 — in progress** (branch `header-menu-from-api`, based on `b366b69`, i.e. before the child-theme conversion on `main`)

## What and why

The magazine header should show the same links as the main site's English header.
Today the magazine keeps its own copy, and it is wrong: German links (BrunaNet, Marktplatz, Galerie, Shop) and a 2-item menu.
The main site already shares this data (theme 3.0.6):
`https://homepage.braunvieh.ch/wp-json/braunvieh/v1/header-footer?lang=en`

This is the first step only: the header.
The footer and the rest of the lean magazine theme come later.

## What comes from the main site

- Top links (next to the logo)
- Fullscreen menu (the main site's English menu)
- External links (bottom of the fullscreen menu)
- Social icons (fixed on the side)

## What stays on the magazine

- Logo image and link to the magazine home
- Search (searches the magazine)
- No language switcher

## How it works

- The magazine asks the main site for the data **once per hour**, in the background (WP-Cron).
- The last good answer is saved on the magazine.
  If the main site is down, the header keeps showing the last good links.
- Visitors never wait for the main site.
- When the links change, the magazine's page cache (LiteSpeed) is cleared, so the new header shows.
- The header is a small block in the magazine theme (`braunvieh-magazine/site-header`).
  It prints the same HTML as today, so the design and the menu script stay the same.
- The old `cows/site-header` from the plugin is no longer used in the header.

## Small English fixes in the header

- "Menü" → "Menu"
- "← Zurück" → "← Back"

## Files

- `inc/header-footer-api.php` — fetch, save, hourly refresh (new)
- `inc/site-header.php` — the header block (new)
- `parts/header.html` — use the new block
- `functions.php` — main site URL + load the new files

## Steps

- [x] 0. Back up: GitHub repo `krstivoja/braunvieh-magazine`, initial commit `b366b69`.
- [x] 1. Screenshot the current header and open menu (before), plus the main site's `/en/` header as the target.
- [x] 2. Write the fetch + save + hourly refresh.
- [x] 3. Write the header block and switch `parts/header.html`.
- [x] 4. Test: header shows the live links, menu opens, sub-menus and Back work, desktop and mobile.
- [x] 5. Test: main site unreachable → header still shows the last good links. (Bad host + HTTP 404 on 2026-09-13 with this same code: refresh returns false, stored copy kept, one log line each.)
- [x] 6. PHP lint, check the error log.
- [x] 7. Commit + push the branch.
- [ ] 8. Go live: replace the theme on the live magazine site (no DB changes). Then decide how this branch and the child theme on `main` come together.

## To fix on the main site (content, in wp-admin)

- The EN top link and external link "Magazines" point to `homepage.braunvieh.ch/en/chbraunvieh/`.
  They should point to the magazine site.

## Decided

- The header is the main site's English header, exactly as on its `/en/` pages:
  top links *About us / Federation / Magazines*, menu *Braunvieh / Breeding values / Services / Events / About us*, the same external links.
  No magazine-only menu items.

