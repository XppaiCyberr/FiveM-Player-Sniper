# FiveM Player Sniper

A lightweight web dashboard for monitoring FiveM server activity in real-time, with historical player tracking and analytics.

<img width="1902" height="956" alt="image" src="https://github.com/user-attachments/assets/ada9e318-f8f6-4e1b-9cdb-baf3c8a30e3c" />


## Features

- **Live Server Info** - Real-time player count, server details, and connection info
- **Player List** - Sortable and searchable player list with ping indicators
- **Faction Stats** - Automatic player grouping by faction tags (LSPD, LSSD, MEDIC, etc.)
- **Player History** - Track player activity over time with charts
- **Ping Analytics** - Per-player ping statistics (avg, min, max)
- **Player Modal** - Click any player to see their activity timeline and ping history

## Setup

### Requirements

- PHP 7.4+ with SQLite3 and cURL extensions
- A web server (Apache, Nginx, etc.)
- Cron job support

### Installation

1. Clone the repository into your web server directory:

```bash
git clone https://github.com/XppaiCyberr/FiveM-Player-Sniper.git
```

2. Set up a cron job to collect data every 5 minutes:

```bash
*/5 * * * * php /path/to/FiveM-Player-Sniper/fetch.php
```

3. Open `index.html` in your browser for the live view, or `history.html` for historical data.

## File Structure

| File | Description |
|------|-------------|
| `fetch.php` | Fetches server data from the FiveM API and stores it in SQLite |
| `api.php` | Serves historical data from SQLite as JSON for the frontend |
| `index.html` | Live server dashboard with player list and faction stats |
| `history.html` | Historical analytics with player count charts and activity timelines |

## Configuration

To monitor a different server, update the API URL in `fetch.php` and `index.html`:

```
https://frontend.cfx-services.net/api/servers/single/{SERVER_ID}
```

Faction tags can be customized in the `FACTION_TAGS` array inside `index.html`.

## Data Storage

Server data is stored in a local SQLite database (`log.db`) with two tables:

- `entries` - Timestamp, client count, max client count
- `players` - Player name and ping, linked to each entry

Old entries are automatically pruned to keep approximately 7 days of data.

## License

MIT
