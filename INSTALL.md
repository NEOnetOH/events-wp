# Deploy Event Scheduler to a customer WordPress site

This plugin displays **live upcoming events** from that customer’s Event Scheduler. It does not add events, categories, or venues in WordPress. Create and edit those in Event Scheduler.

## Required configuration

The plugin needs **four values** from the customer’s Event Scheduler. Without all four, the widget cannot list events.

| Setting | What it is | Example |
| --- | --- | --- |
| **Event Scheduler URL** | The customer’s public scheduler hostname | `https://events.districtname.org` |
| **API key** | The Event Scheduler API key (OAuth client ID) | Issued in Event Scheduler |
| **Secret** | The Event Scheduler API secret (OAuth client secret) | Issued with the API key |
| **Group ID** | The Event Scheduler group whose public events this site should show | `216` |

These come from Event Scheduler, not WordPress. You can enter them by hand on **Settings → Event Scheduler** and click **Save Changes**, or use **Connect to Event Scheduler** to have Event Scheduler fill them in.

The Event Scheduler URL is the scheduler host, not the WordPress site. You can paste `events.districtname.org`; the plugin adds `https://`. Localhost and private IPs are rejected.

## Before you start

| Requirement | Notes |
| --- | --- |
| WordPress 6.5+ and PHP 8.1+ | On the customer website |
| A WordPress administrator | Can activate plugins, save settings, and manage widgets |
| Event Scheduler URL, API key, secret, and group ID | From this customer’s Event Scheduler |
| A public HTTPS WordPress site | Required if you use Connect. Localhost is for testing only. |

## 1. Install the plugin

1. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**.
2. Upload `event-schedule-wp-1.1.0.zip`.
3. Click **Install Now**, then **Activate**.

The plugin name in the list is **Event Scheduler**.

If you prefer the filesystem: unzip so the folder is `wp-content/plugins/event-schedule-wp/`, then activate it.

## 2. Enter the four required settings

Go to **Settings → Event Scheduler** and fill in:

1. **Event Scheduler URL** — `https://events.districtname.org`
2. **API key** — the client ID from Event Scheduler
3. **Secret** — the client secret from Event Scheduler (the field stays blank after save; a saved secret is kept if you leave it empty)
4. **Group ID** — the numeric group this site should list. Use a comma-separated list only if more than one group is required, such as `19,216`.

Click **Save Changes**, then **Test connection**. You should see the scheduler hostname and a count of upcoming events.

Do not leave the page until Test connection succeeds.

### Option: fill the four values with Connect

If the customer’s Event Scheduler is 1.136.0 or later and you do not already have an API key and secret:

1. Enter the **Event Scheduler URL**.
2. Click **Connect to Event Scheduler**.
3. Sign in to Event Scheduler as a user who can **manage settings**.
4. Confirm the WordPress site, select the groups WordPress may read, and click **Approve and return to WordPress**.
5. WordPress writes the **API key**, **secret**, and **group ID** for you.
6. Click **Test connection**.

## 3. Publish the upcoming-events list

The main publish path is a widget.

1. Go to **Appearance → Widgets** (or **Appearance → Editor** on a block theme, then the widget area).
2. Add **Event Scheduler: Upcoming Events** to the sidebar or homepage area the customer uses for events.
3. Set:
   - **Title** — usually “Upcoming Events”
   - **Number of events** — default 6
   - **Days ahead** — how far into the future to show. `90` is about a quarter. `0` means no date cap; only the event limit applies.
4. Save / Update.

Optional display settings on **Settings → Event Scheduler**:

- **Calendar URL** — the “View Calendar” link on this WordPress site, often `/events`
- **Show location / category / CEU** — off by default; turn on only if the API provides those fields
- **Accent color** and **Date badge color** — match the customer theme

### If the site cannot use a widget

Paste this shortcode into a page or HTML block:

```
[event_schedule_upcoming limit="6" days="90"]
```

Or insert the **Upcoming Events** Gutenberg block.

## 4. What the customer should know

- Events are managed in Event Scheduler, not in WordPress.
- The list is live. New public events in the connected group appear on the next page load.
- Treat the **secret** like a password. Do not put it in email, tickets, or screenshots.
- Changing the Event Scheduler URL points the site at a different customer. Tokens from the previous host are cleared.
- **Disconnect** removes the API key, secret, and group ID from WordPress.

## 5. Hand-off checklist

- [ ] Plugin activated on the customer WordPress site
- [ ] Event Scheduler URL is the correct customer host
- [ ] API key, secret, and group ID are saved
- [ ] Test connection succeeded
- [ ] Widget (or shortcode) visible on the agreed page
- [ ] “View Calendar” points at the customer’s calendar page
- [ ] Sample event titles and times match Event Scheduler
- [ ] Customer knows events are edited in Event Scheduler only

## Troubleshooting

**“Enter a public Event Scheduler URL first”**  
The URL was empty or invalid. Use the public hostname, such as `https://events.districta.org`.

**“Enter the Event Scheduler API key and secret”**  
The **API key** or **secret** is missing. Paste them from Event Scheduler and save, or use Connect to issue a new pair.

**“Enter the Event Scheduler group ID”**  
**Group ID** is required. Use the numeric ID from Event Scheduler, not the group name.

**“That Event Scheduler URL is not a public HTTPS address”**  
The plugin rejects `http://`, localhost, `.local`, and private IPs. Production WordPress must use HTTPS, and so must Event Scheduler.

**Widget says “No upcoming events”**  
The API only returns events that have not ended in the configured group. Confirm the **group ID** is correct and that group has future public events. Increase **Days ahead** or set it to `0`.

**Wrong district’s events**  
Check the four required settings. The URL and group ID must belong to that customer. Disconnect, enter the correct values, and test again.

**Connect: “Event Scheduler did not issue credentials”**  
That Event Scheduler instance is older than 1.136.0, or `/wordpress/connect` is not deployed. Enter the API key, secret, and group ID by hand, or upgrade Event Scheduler and use Connect.

**Connect: “Event Scheduler did not return any group IDs”**  
On the approve page, leave at least one group checked.
