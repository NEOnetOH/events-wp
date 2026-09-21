=== events.apptoolstack.com ===
Contributors: amelick
Tags: events, calendar, upcoming events, widget
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.3.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Official NEOnet plugin for AppToolStack Events (events.apptoolstack.com). Display upcoming events with a WordPress widget.

== Description ==

This is the official WordPress plugin by [NEOnet](https://www.neonet.org) for NEOnet’s AppToolStack Events product at [events.apptoolstack.com](https://events.apptoolstack.com). It lists live upcoming events from a customer AppToolStack Events host.

It does not create or edit events in WordPress. You need a working events.apptoolstack.com host, an API key, a secret, and a group ID.

Required configuration: host URL, API key, secret, and group ID. Those values come from events.apptoolstack.com.

Events, categories, and venues stay in events.apptoolstack.com. This plugin does not add those editors to WordPress.

The main way to publish is the **events.apptoolstack.com: Upcoming Events** widget under Appearance → Widgets.

= Security =

* HTTPS-only host URLs
* Private and loopback hosts rejected
* Client secret stored separately
* Admin actions require manage_options and a nonce

== Installation ==

1. Search for “events.apptoolstack.com” under Plugins → Add New, or upload the plugin zip
2. Activate events.apptoolstack.com
3. Go to Settings → events.apptoolstack.com and enter the host URL, API key, secret, and group ID
4. Click Save Changes, then Test connection
5. Go to Appearance → Widgets and add events.apptoolstack.com: Upcoming Events

== Frequently Asked Questions ==

= Who makes this plugin? =

[NEOnet](https://www.neonet.org) (Northeast Ohio Network for Educational Technology). This is the official companion plugin for NEOnet’s AppToolStack Events product.

= Does this plugin work without events.apptoolstack.com? =

No. It displays public events from an events.apptoolstack.com host. Install it only if you already have that service, or plan to.

= Where do events get created? =

In events.apptoolstack.com, not in WordPress. The widget reads the live API.

= What data does the plugin send? =

After an administrator saves a host URL (or uses Connect), the plugin requests public events from that host. Connect also sends this WordPress site’s URL and name so the host can approve the connection. Visitor browsing is not tracked by the plugin. See the Privacy section.

= Can I show different events on different pages? =

Yes. Add more than one widget and set Category IDs on each, or use the `[event_schedule_upcoming category_ids="12"]` shortcode.

== Screenshots ==

1. Settings: connect a host and test the API
2. Upcoming Events widget in Appearance → Widgets
3. Upcoming events list on the front of the site

== Privacy ==

This plugin contacts the events.apptoolstack.com host that a site administrator configures. It does not phone home to WordPress.org or to a shared NEOnet server unless that is the host the administrator entered.

* **Host URL, API key, secret, and group ID** are stored in the WordPress database on this site. The secret is stored separately and is not autoloaded.
* **Connect** sends the WordPress site URL and site name to the configured host so an events.apptoolstack.com administrator can approve access.
* **API requests** send an OAuth access token and a User-Agent that includes this site’s URL.
* **Front-end output** shows public event titles and times. Venue names appear only if “Show location” is enabled. Private events are not listed.
* Uninstalling the plugin removes its options and cached tokens from this site.

Service terms: [events.apptoolstack.com](https://events.apptoolstack.com)

== Changelog ==

= 1.3.4 =
* State that this is the official NEOnet plugin for AppToolStack Events.

= 1.3.3 =
* Plugin Check: escape template output correctly, use wp_safe_redirect for Connect, and prefix template variables.

= 1.3.2 =
* Tested up to WordPress 7.1.
* Text domain is events-apptoolstack-com so it matches the WordPress.org plugin slug.

= 1.3.1 =
* Plugin URI is the GitHub repository; Author URI is neonet.org. WordPress.org requires those two URLs to be different.

= 1.3.0 =
* Public name is events.apptoolstack.com. Option keys stay eswp_*.
* WordPress.org packaging: privacy section, LICENSE, directory assets, and GitHub Action deploy to SVN.
* Uninstall no longer touches leftover post types from an older sync version.

= 1.2.0 =
* Widget: per-widget Category IDs override so different pages can show different departments' events (same option added to the shortcode and Gutenberg block).
* Widget: option to open event and "View Calendar" links in a new tab (shortcode `open_in_new_tab="1"`; block toggle).
* Fix: Calendar URL setting no longer clears on save when a relative path like `/events` is entered. Help text now explains that this is the footer link on this WordPress site.
* Docs: customer handoff bundle (`dist/event-schedule-wp-<version>-handoff.zip`) with `INSTALL.md`, `API-CREDENTIALS.txt`, and the plugin zip together.

= 1.1.0 =
* Display-only plugin: live API, widget-first, no local event editor or sync job
