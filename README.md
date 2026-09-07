# Skomi for WordPress

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
