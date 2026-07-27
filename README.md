# Currency Rates

A Laravel + Vue app that tracks BTC, ETH and USDT exchange rates against USD, polling [CoinGecko](https://www.coingecko.com/) on a schedule, storing every observation, and emailing an alert when a rate moves sharply. The homepage is a small dashboard with a live-updating chart per currency.

## Stack

- Laravel 13 (PHP 8.5), PostgreSQL, Redis, Guzzle
- Vue 3 + Vite + Tailwind CSS v4
- Laravel Sail for local development
- Pint (style), Larastan/PHPStan level 8 (static analysis), PHPUnit (tests)

## Requirements

- Docker (for Sail) — or a local PHP 8.3+/PostgreSQL/Redis setup
- Node.js + npm

## Setup

```bash
cp .env.example .env
make install
make up
make artisan cmd="key:generate"
make fresh          # migrate + seed
npm install
npm run build        # or `npm run dev`
```

The app is served at `http://localhost`.

### Makefile targets

`make up` / `make down` / `make restart` - Start / stop / restart the Sail containers
`make install`                           - `composer install` inside the container
`make artisan cmd="..."`                 - Run any artisan command, e.g. `make artisan cmd="route:list"`
`make migrate`                           - Run pending migrations
`make fresh`                             - Drop all tables, re-migrate and seed
`make seed`                              - Run database seeders
`make queue`                             - Run the default queue worker
`make queue-redis`                       - Run the redis-connection queue worker (rate-change notifications)
`make schedule`                          - Run the scheduler (dispatches `UpdateCurrencyRatesJob` every 10 minutes)
`make test`                              - Run the test suite
`make pint` / `make pint-test`           - Fix / check code style
`make phpstan`                           - Run static analysis
`make tinker`                            - Open a Tinker
`make shell`                             - Open a shell in the app container
`make logs` / `make ps`                  - Tail container logs / show container status

Run `make schedule` and `make queue-redis` in separate terminals

## Configuration

`RATE_PROVIDER_BASE_URI`         - CoinGecko API base URL
`RATE_PROVIDER_TIMEOUT`          - HTTP timeout (seconds)
`RATE_PROVIDER_MAX_ATTEMPTS`     - Retry for provider requests
`RATE_PROVIDER_RETRY_DELAY_MS`   - Backoff for provider requests
`RATE_PROVIDER_BASE_CURRENCY`    - Currency all rates are quoted against (default `USD`)
`RATE_CHANGE_THRESHOLD_PERCENT`  - % move that triggers an email alert
`RATE_CHANGE_NOTIFICATION_EMAIL` - Recipient for rate-change alerts
