# WP Robots.txt Editor

Edit your site's virtual robots.txt from the WordPress admin — with validation, a "Disallow: /" safety warning, a live preview and one-click restore to default.

## Why

WordPress generates a basic robots.txt on its own, but the moment you need to block a crawler, add a sitemap line or keep a staging folder out of Google, the "proper" way is SSH + editing a file in the web root. This plugin puts the same control in **Settings → Robots.txt**, with a guard rail against the one mistake that de-indexes your whole site.

## Features

- **Admin editor** — textarea editor for the virtual robots.txt (via the `robots_txt` filter); empty box = WordPress default.
- **De-index warning** — saving a `Disallow: /` rule for all user-agents shows a loud warning, because that single line removes the site from search engines.
- **Physical-file detection** — warns you when a real `robots.txt` exists in the site root (the server serves that instead of the virtual one).
- **Live preview** — shows exactly what crawlers receive right now.
- **One-click restore** — delete custom rules and return to the WordPress default.
- **Sitemap included** — the default rules include your core sitemap URL when the WordPress sitemap is enabled.

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate it from **Plugins**.
3. Open **Settings → Robots.txt**.

## Uninstall

Deleting the plugin removes its saved custom robots.txt rules.
