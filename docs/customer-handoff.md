# Customer handoff checklist

Send the customer everything they need in one delivery. Do not make them dig
into the plugin zip to find install steps.

## What to send

Zip up the **handoff bundle** produced by the release build
(`dist/event-schedule-wp-<version>-handoff.zip`) and email or ticket it to the
customer contact. The bundle contains:

| File | Purpose |
| --- | --- |
| `INSTALL.md` | Step-by-step deploy guide. Readable without unzipping the plugin. |
| `API-CREDENTIALS.txt` | The four required values, filled in for this customer. |
| `event-schedule-wp-<version>.zip` | The WordPress upload zip (Plugins -> Add New -> Upload Plugin). |

After the plugin is listed on WordPress.org, prefer that customers install from
Plugins -> Add New so they receive updates in wp-admin.

## Before you send

1. Copy `docs/api-credentials.template.txt` to a new file, fill in the four
   required values for this customer (host URL, API key, secret, group ID),
   and rename it `API-CREDENTIALS.txt` inside the handoff bundle.
2. Confirm the WordPress site is HTTPS and running WordPress 6.5+ / PHP 8.1+.
3. Confirm the group ID exists in events.apptoolstack.com and has upcoming
   public events. If it does not, the widget will correctly show "No upcoming
   events".
4. Treat the secret like a password. Do not paste it into email bodies,
   tickets, or screenshots -- keep it inside the bundle only.

## After they install

1. Ask them to run **Test connection** on Settings -> events.apptoolstack.com.
2. Confirm the widget shows on the agreed page.
3. Rotate the API secret in events.apptoolstack.com if it was ever exposed.
