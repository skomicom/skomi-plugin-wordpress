# Skomi for WordPress

The Skomi snippet as a plugin, so it survives a theme change.

```
sdk/wordpress/skomi-wordpress/     ← the plugin, zip this folder
```

Two settings, which is the whole configuration:

| Setting | Default | What it does |
|---|---|---|
| **Enabled** | on | Collect at all. Off loads no script and keeps the site ID. |
| **Site ID** | empty | From your site's page in Skomi. A UUID, and not a secret. |

Two more, for the cases that need them:

| Setting | Default | What it does |
|---|---|---|
| Measure signed-in users | **off** | Your own reloads outnumber a small site's real traffic |
| Tracker address | `https://t.skomi.com` | For a staging or self-hosted Edge |

## Why a plugin rather than the tag

[The install guide](https://skomi.com/docs/installing-on-wordpress) gives
three ways to paste the tag and warns about the one that bites: a tag in a theme
leaves with that theme. Publishing a different theme, or reinstalling one from
the theme store, takes it away — with no error and no sign in the dashboard that
anything changed. A plugin is the only one of the three that survives.

## What it does

* Enqueues the bundle in the footer with `strategy => defer`, and **no `?ver=`
  query string** — the URL has to stay the one the dashboard shows somebody.
* Refuses a site ID that is not a UUID, and says so, rather than storing it and
  printing a broken script URL onto every page.
* Never loads in admin, a feed, a preview or an AJAX request.
* Adds no tables, no cron and no dashboard widgets.

## Testing it

`php -l` on each file is the only check that runs here — there is no PHP test
harness in this repository. Before releasing, run it against a real install:

1. Activate, save a site ID, load a page as a signed-out visitor.
2. Confirm the tag is in the footer and the URL has no query string.
3. Turn **Enabled** off, reload, confirm no script and that the ID survived.
4. Publish a different theme and confirm it still collects — the reason this
   exists.

⚠ **A local install collects nothing.** Skomi only accepts hits from the domain
registered against the site, so `localhost` is refused by design. Test on a real
domain or a registered subdomain.

## Distributing

Released from
**[skomicom/skomi-plugin-wordpress](https://github.com/skomicom/skomi-plugin-wordpress)**
as a zip attached to a release. Skomi.com no longer serves a download of its own;
[the install guide](https://skomi.com/docs/installing-on-wordpress) points at the
latest release.

`build/Plugins.targets` packs this folder into a correctly shaped
`skomi-wordpress.zip` — that is what a release asset should be built from. The
zip is build output and git-ignored; a committed one would be a second copy that
goes stale the first time somebody edits the PHP.

⚠ **Do not offer GitHub's "Download ZIP" as the install.** It wraps everything
in `<repo>-<branch>/`, and WordPress installs a plugin under whatever folder the
zip contains — so it would land under the wrong slug.

The zip, the folder inside it and the plugin slug are all `skomi-wordpress`.
⚠ They must stay in step: the folder inside the zip is what WordPress installs
under, and it is what `Requires Plugins: skomi-wordpress` in the WooCommerce
plugin points at. Change one and the dependency silently stops resolving.

`readme.txt` is what wordpress.org builds the listing page from.

### Listing images

`readme.txt` has **no `== Screenshots ==` section**, deliberately — there are no
screenshots to show yet, and a caption with no file behind it displays nothing at
all: no placeholder, no error, just a listing with fewer images than the readme
claims. Add the captions when the images exist, not before.

Whatever the listing does carry — banners (`banner-772x250.png`,
`banner-1544x500.png`), the icon (`icon-256x256.png`), and screenshots if they
are ever added — goes in the wordpress.org SVN repository's top-level `assets/`
directory, a sibling of `trunk/` and `tags/`. Never in this folder: anything in
`assets/` is excluded from what people download, so it costs the plugin nothing
in size.

WooCommerce support is a separate plugin: [`../woocommerce`](../woocommerce).
