=== Skomi Analytics ===
Contributors: skomi
Tags: analytics, privacy, heatmaps, cookieless, gdpr
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the Skomi tracking snippet to your site. Analytics, heatmaps and feedback from one script, nothing stored on the visitor's device by default.

== Description ==

Skomi measures sites and apps: who arrived, from where, what they did on the
page, and what they wanted to tell you. This plugin puts its one script tag on
your site and gives you a switch to turn it off again.

You need a Skomi account and a site registered in it. The site ID this plugin
asks for is on that site's page in Skomi.

**This plugin relies on a third-party service**

Skomi is a hosted analytics service at https://skomi.com/ — this plugin is a
client for it and does not measure anything itself.

With a site ID saved, the plugin adds one `<script>` tag to your pages. Your
visitors' browsers then load that script from `https://t.skomi.com/` and it sends
what it measures — the page address, the referrer, the screen size and the like —
to the same host. Nothing is sent from your server, and the plugin transmits
nothing on its own.

Removing the site ID, or switching the plugin off, removes the tag and stops all
of it.

Skomi's terms of service: https://skomi.com/terms
Skomi's privacy policy: https://skomi.com/privacy

**What it does**

* Loads the Skomi bundle in the footer, deferred, so it never delays your pages.
* Survives a theme change. Pasting the tag into a theme is the usual advice and
  the usual way analytics silently stops — publishing a different theme takes the
  tag with it, with no error and nothing to notice.
* Leaves your own visits out by default. A small site's numbers are mostly its
  owner reloading pages, and nothing in a dashboard says so.
* Adds no database tables, no cron jobs and no dashboard widgets.

**What it does not do**

* It does not send anything anywhere by itself. The script it loads does the
  measuring, and what that collects is decided by your settings in Skomi.
* It does not measure your WooCommerce orders. That is a separate plugin,
  Skomi for WooCommerce.

**Privacy**

On Skomi's default setting nothing is stored on the visitor's device — no
cookie, no local storage — because a visitor is recognised on the server from a
secret that is replaced every day and stored nowhere. Most sites therefore need
no consent banner for it. That is a setting on your site in Skomi rather than
something this plugin controls, and it can be turned off, at which point a
banner is needed again.

== Installation ==

1. Install and activate the plugin.
2. Go to **Settings → Skomi Analytics**.
3. Paste your site ID and save.

That is the whole setup. The tag appears on the next page load that is not
cached — purge your cache if you use one.

== Frequently Asked Questions ==

= Nothing is arriving. =

Almost always one of two things on WordPress: a page cache that has not been
purged since you saved the setting, or an optimisation plugin set to copy
third-party scripts to your own server. Exclude Skomi from the second — a local
copy is frozen at the day it was made, so switching a product on in Skomi later
would change nothing and there would be no sign why.

Development addresses do not collect either. Skomi only accepts hits from the
domain you registered and its subdomains, so `localhost` and a staging domain
you have not registered are refused.

= Does this work on multisite? =

Yes, and each site in the network needs its own site ID, because each domain is
a separate site in Skomi.

= Where is my site ID? =

On your site's page in Skomi. It is a UUID and it is not a secret — it appears
in the page source of every page it measures.

== Changelog ==

= 1.0.0 =
* First release.
