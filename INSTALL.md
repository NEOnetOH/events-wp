# Deploy events.apptoolstack.com to a customer WordPress site

This plugin displays **live upcoming events** from that customer’s events.apptoolstack.com host. It does not add events, categories, or venues in WordPress. Create and edit those in events.apptoolstack.com.

## Required configuration

The plugin needs **four values** from the customer’s events.apptoolstack.com host. Without all four, the widget cannot list events.

| Setting | What it is | Example |
| --- | --- | --- |
| **Host URL** | The customer’s public scheduler hostname | `https://events.districtname.org` |
| **API key** | The events.apptoolstack.com API key (OAuth client ID) | Issued in events.apptoolstack.com |
| **Secret** | The events.apptoolstack.com API secret (OAuth client secret) | Issued with the API key |
| **Group ID** | The group whose public events this site should show | `216` |

These come from events.apptoolstack.com, not WordPress. You can enter them by hand on **Settings → events.apptoolstack.com** and click **Save Changes**, or use **Connect to events.apptoolstack.com** to have the host fill them in.

The host URL is the scheduler host, not the WordPress site. You can paste `events.districtname.org`; the plugin adds `https://`. Localhost and private IPs are rejected.

## Before you start

| Requirement | Notes |
| --- | --- |
| WordPress 6.5+ and PHP 8.1+ | On the customer website |
| A WordPress administrator | Can activate plugins, save settings, and manage widgets |
| Host URL, API key, secret, and group ID | From this customer’s events.apptoolstack.com host |
| A public HTTPS WordPress site | Required if you use Connect. Localhost is for testing only. |

## 1. Install the plugin

1. In WordPress, go to **Plugins → Add New Plugin → Upload Plugin**.
2. Upload `event-schedule-wp-1.3.1.zip`.
3. Click **Install Now**, then **Activate**.

The plugin name in the list is **events.apptoolstack.com**.

If you prefer the filesystem: unzip so the folder is `wp-content/plugins/event-schedule-wp/`, then activate it.

After the plugin is in the WordPress.org directory, customers can also install it from **Plugins → Add New** by searching for events.apptoolstack.com. Sites installed from the directory receive updates in wp-admin. Zip-installed copies do not switch automatically.

## 2. Enter the four required settings

Go to **Settings → events.apptoolstack.com** and fill in:

1. **Host URL** — `https://events.districtname.org`
2. **API key** — the client ID from events.apptoolstack.com
3. **Secret** — the client secret from events.apptoolstack.com (the field stays blank after save; a saved secret is kept if you leave it empty)
4. **Group ID** — the numeric group this site should list. Use a comma-separated list only if more than one group is required, such as `19,216`.

Click **Save Changes**, then **Test connection**. You should see the scheduler hostname and a count of upcoming events.

Do not leave the page until Test connection succeeds.

### Option: fill the four values with Connect

If the customer’s host is 1.136.0 or later and you do not already have an API key and secret:

1. Enter the **Host URL**.
2. Click **Connect to events.apptoolstack.com**.
3. Sign in to events.apptoolstack.com as a user who can **manage settings**.
4. Confirm the WordPress site, select the groups WordPress may read, and click **Approve and return to WordPress**.
5. WordPress writes the **API key**, **secret**, and **group ID** for you.
6. Click **Test connection**.

## 3. Publish the upcoming-events list

The main publish path is a widget.

1. Go to **Appearance → Widgets** (or **Appearance → Editor** on a block theme, then the widget area).
2. Add **events.apptoolstack.com: Upcoming Events** to the sidebar or homepage area the customer uses for events.
3. Set:
   - **Title** — usually “Upcoming Events”
   - **Number of events** — default 6
   - **Days ahead** — how far into the future to show. `90` is about a quarter. `0` means no date cap; only the event limit applies.
   - **Category IDs** (optional) — comma-separated numeric IDs from events.apptoolstack.com. Use this when a widget should only list one department’s events (e.g. Fiscal events on the Fiscal page). Overrides the global Category IDs setting; leave blank to use it.
   - **Open event links in a new tab** (optional) — when on, event titles and “View Calendar” open in a new browser tab.
4. Save / Update.

You can add the widget more than once and give each copy its own **Category IDs** so different pages show different departments.

Optional display settings on **Settings → events.apptoolstack.com**:

- **Calendar URL** — where the “View Calendar” footer link (under the upcoming-events list) should point. Enter a path on this WordPress site such as `/events`, or a full `https://` URL. Leave blank to hide the link. This is a page on this WordPress site, not the events.apptoolstack.com host.
- **Show location / category / CEU** — off by default; turn on only if the API provides those fields
- **Accent color** and **Date badge color** — match the customer theme

### If the site cannot use a widget

Paste this shortcode into a page or HTML block:

```
[event_schedule_upcoming limit="6" days="90"]
```

Filter to one department’s categories and open links in a new tab:

```
[event_schedule_upcoming limit="6" days="90" category_ids="12,34" open_in_new_tab="1"]
```

Or insert the **Upcoming Events** Gutenberg block; the same options are in the block sidebar.

## 4. What the customer should know

- Events are managed in events.apptoolstack.com, not in WordPress.
- The list is live. New public events in the connected group appear on the next page load.
- Treat the **secret** like a password. Do not put it in email, tickets, or screenshots.
- Changing the host URL points the site at a different customer. Tokens from the previous host are cleared.
- **Disconnect** removes the API key, secret, and group ID from WordPress.

## 5. Hand-off checklist

- [ ] Plugin activated on the customer WordPress site
- [ ] Host URL is the correct customer host
- [ ] API key, secret, and group ID are saved
- [ ] Test connection succeeded
- [ ] Widget (or shortcode) visible on the agreed page
- [ ] “View Calendar” points at the customer’s calendar page
- [ ] Sample event titles and times match events.apptoolstack.com
- [ ] Customer knows events are edited in events.apptoolstack.com only

## Troubleshooting

**“Enter a public events.apptoolstack.com URL first”**  
The URL was empty or invalid. Use the public hostname, such as `https://events.districta.org`.

**“Enter the events.apptoolstack.com API key and secret”**  
The **API key** or **secret** is missing. Paste them from events.apptoolstack.com and save, or use Connect to issue a new pair.

**“Enter the events.apptoolstack.com group ID”**  
**Group ID** is required. Use the numeric ID from events.apptoolstack.com, not the group name.

**“That events.apptoolstack.com URL is not a public HTTPS address”**  
The plugin rejects `http://`, localhost, `.local`, and private IPs. Production WordPress must use HTTPS, and so must the host.

**Widget says “No upcoming events”**  
The API only returns events that have not ended in the configured group. Confirm the **group ID** is correct and that group has future public events. Increase **Days ahead** or set it to `0`.

**Wrong district’s events**  
Check the four required settings. The URL and group ID must belong to that customer. Disconnect, enter the correct values, and test again.

**Connect: “events.apptoolstack.com did not issue credentials”**  
That host is older than 1.136.0, or `/wordpress/connect` is not deployed. Enter the API key, secret, and group ID by hand, or upgrade the host and use Connect.

**Connect: “events.apptoolstack.com did not return any group IDs”**  
On the approve page, leave at least one group checked.
