=== Event Scheduler ===
Contributors: neonet
Tags: events, calendar, upcoming events, event scheduler
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Securely display upcoming events from the Event Scheduler API using a WordPress widget.

== Description ==

Event Scheduler for WordPress lists live upcoming events from a customer Event Scheduler host.

Required configuration: Event Scheduler URL, API key, secret, and group ID. Those values come from Event Scheduler.

Events, categories, and venues stay in Event Scheduler. This plugin does not add those editors to WordPress.

The main way to publish is the **Event Scheduler: Upcoming Events** widget under Appearance → Widgets.

= Security =

* HTTPS-only scheduler URLs
* Private and loopback hosts rejected
* Client secret stored separately
* Admin actions require manage_options and a nonce

== Installation ==

1. Upload event-schedule-wp-1.1.0.zip under Plugins → Add New Plugin → Upload Plugin
2. Activate Event Scheduler
3. Go to Settings → Event Scheduler and enter the Event Scheduler URL, API key, secret, and group ID
4. Click Save Changes, then Test connection
5. Go to Appearance → Widgets and add Event Scheduler: Upcoming Events

Full customer deploy steps are in INSTALL.md.

== Changelog ==

= 1.1.0 =
* Display-only plugin: live API, widget-first, no local event editor or sync job
