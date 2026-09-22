# events.apptoolstack.com for WordPress

Official [NEOnet](https://www.neonet.org) WordPress plugin for NEOnet’s AppToolStack Events product at [apptoolstack.com](https://apptoolstack.com). It displays upcoming events from that API. It does not create events, categories, or venues in WordPress.

**Customer deploy:** see [INSTALL.md](INSTALL.md).

WordPress.org updates (after the directory listing is approved) come from wordpress.org, not from this GitHub repository. Tag a release; the deploy workflow copies it to SVN when `SVN_USERNAME` and `SVN_PASSWORD` secrets are set.

## How it works

The plugin needs four settings from AppToolStack Events:

1. **Host URL** — the customer host (`https://events.districta.org`)
2. **API key** — OAuth client ID
3. **Secret** — OAuth client secret
4. **Group ID** — which group’s public events to list

Enter those on **Settings → events.apptoolstack.com** (or use Connect to fill them in), then publish with the **events.apptoolstack.com: Upcoming Events** widget.

The widget reads the live Events API. There is no local event editor and no required sync job.

## Security

- HTTPS-only scheduler URLs
- Private, loopback, and metadata hosts are rejected
- Outbound requests do not follow redirects
- Client secret is stored separately from settings and is not autoloaded
- Connect, disconnect, and the connection test require `manage_options` and a nonce
- Front-end output is escaped
- The v2 API returns public events only

## Requirements

- WordPress 6.5+
- PHP 8.1+
- AppToolStack Events 1.136.0+ with `/api/v2` and the WordPress connect handshake

## Branches

- `dev` — current work
- `main` — stable releases
