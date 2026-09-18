# EasyStock

A small inventory application for teams that count things: products with options (size, colour, logo…), a stock
ledger, CSV/Excel/PDF reports and a public product page reachable through a QR code.

Built with [Yii 3](https://www.yiiframework.com/) on PHP 8.5, SQLite and server-rendered templates.

## Features

- **User accounts and permissions** – sign in with a username and password. Accounts have one of three roles
  (`admin`, `manager`, `staff`); every page and action is guarded by a permission, checked server-side.
- **User management for administrators** – create accounts, change roles, reset passwords, activate/deactivate
  accounts, with guards that prevent locking yourself (or the last administrator) out.
- **Products with options** – define option types (`Size`, `Color`, `Logo`…) and their values, then attach values
  to a product. EasyStock generates one variant per combination with a readable SKU
  (`TSHIRT-XL-RED`, `TABLE-5FT-BLACK`, …).
- **Stock ledger** – every change is a movement (`in`, `out`, `adjustment`) with quantity, optional unit cost,
  reference, note and the user who recorded it. Levels, low-stock warnings and history are derived from the ledger.
- **Reports** – export products, stock levels or movements to **CSV**, **Excel (XLSX)** or **PDF**, and import
  products from CSV/XLSX with a downloadable template.
- **QR codes and a public page** – every product gets a token and an SVG QR code; anyone scanning it sees a
  read-only product summary (stock per variant and its options) without signing in.
- **Tests and quality gates** – 148 Codeception tests (unit, functional, web, console), Psalm at level 1,
  PHP CS Fixer, Rector and Composer Dependency Analyser.

## Requirements

- PHP **8.4.1** or newer (the locked dependencies require it; the Docker image ships PHP 8.5)
- Extensions: `pdo`, `pdo_sqlite`, `sqlite3`, `mbstring`, `intl`, `dom`, `xml`, `xmlreader`, `xmlwriter`,
  `simplexml`, `iconv`, `zip`, `ctype`, `curl`, `phar`, `openssl`, `filter`
- [Composer](https://getcomposer.org/) 2
- Optional: Docker with the Compose plugin, for the containerized workflow

## Quick start

### With Docker

```bash
make build     # build the images
make up        # start the development stack (http://localhost)
make yii migrate:up --no-interaction
make yii user:create --username=admin --password=change-me --name="Site Administrator" --no-interaction
make yii seed:demo --no-interaction   # optional: option types, products and stock to play with
```

The application is then available on <http://localhost>. `make help` lists every available target
(`shell`, `test`, `test-coverage`, `psalm`, `cs-fix`, `rector`, `composer-dependency-analyser`, …).

### Without Docker

```bash
composer install
cp .env.example .env                 # optional, see "Configuration"
./yii migrate:up --no-interaction    # create the schema
./yii user:create --username=admin --password=change-me --name="Site Administrator" --no-interaction
./yii serve                          # http://127.0.0.1:8080
```

`./yii seed:demo --no-interaction` adds option types (`Size`, `Color`, `Logo`), three products with variants and
opening stock, so there is something to look at right away.

Add `--no-interaction` to every command when you script or automate them.

## Configuration

Configuration lives in `config/` and is assembled by the Yii config plugin: `config/common/params.php` for
parameters, `config/common/di/*.php` and `config/web/di/*.php` for the dependency injection container,
`config/common/routes.php` for routes. After changing the *structure* of `config/configuration.php`, run
`composer yii-config-rebuild --no-interaction` to refresh the merge plan.

Environment variables (read in `src/Environment.php`, `.env` is loaded in development):

| Variable        | Default            | Meaning                                                        |
|-----------------|--------------------|----------------------------------------------------------------|
| `APP_ENV`       | `dev`              | `dev`, `test` or `prod`; selects `config/environments/<env>/`. |
| `APP_DEBUG`     | `false`            | Verbose error pages and debug logging.                         |
| `APP_C3`        | `false`            | Enables Codeception code coverage (`c3.php`).                  |
| `APP_HOST_PATH` | unset              | Host path of the project, used when running inside Docker.     |
| `APP_DB_DSN`    | `sqlite:runtime/…` | PDO DSN of the database (SQLite by default).                   |

The database files are `runtime/database/app.sqlite` (prod and any environment without an override),
`runtime/database/dev.sqlite` (dev) and `runtime/database/test.sqlite` (test, recreated by the suite).

## Database and migrations

The schema is defined by hand-written migrations in `src/Migrations/`:

| Table                 | Contents                                                            |
|-----------------------|---------------------------------------------------------------------|
| `user`                | Accounts: username, password hash, display name, email, role, active |
| `product`             | Products: SKU, name, unit, description, low-stock threshold, QR token |
| `option_type`         | Option types (`Size`, `Color`, …)                                   |
| `option_value`        | Values of an option type (`XL`, `Red`, …)                           |
| `variant`             | One row per combination of option values, with its own SKU          |
| `variant_option_value`| Which option values make up a variant                               |
| `stock_movement`      | The stock ledger: variant, type, quantity change, cost, reference, note, user |

Useful commands:

```bash
./yii migrate:up --no-interaction        # apply pending migrations
./yii migrate:new --no-interaction       # list migrations that are not applied yet
./yii migrate:history --no-interaction   # applied migrations
./yii migrate:create <name> --no-interaction
./yii migrate:down --no-interaction
```

## Roles and permissions

Permissions are declared in `App\Access\Permission` and granted per role in `config/common/params.php`
(`permissions`). A route is protected by adding the permission middleware to it; the sidebar only shows what the
signed-in user may open.

| Permission        | admin | manager | staff |
|-------------------|:-----:|:-------:|:-----:|
| `product:view`    |   ✔   |    ✔    |   ✔   |
| `product:manage`  |   ✔   |    ✔    |       |
| `option:manage`   |   ✔   |    ✔    |       |
| `stock:view`      |   ✔   |    ✔    |   ✔   |
| `stock:operate`   |   ✔   |    ✔    |   ✔   |
| `export`          |   ✔   |    ✔    |       |
| `import`          |   ✔   |    ✔    |       |
| `user:manage`     |   ✔   |         |       |

Guests are redirected to `/login`; signed-in users without the permission get a `403` page.

## Products, options and variants

1. Create the option types and values under **Option types** (`Size` → `XL`, `L`, `5ft`; `Color` → `Red`, `Black`).
2. Create a product and tick the option values that apply to it.
3. EasyStock writes one variant per combination and names it after the product SKU plus the option values:
   the product `TSHIRT` with `Size=XL`, `Color=Red` and `Logo=With logo` gets the variant
   `TSHIRT-XL-RED-WITH-LOGO`, the product `TABLE` with `Size=5ft` and `Color=Black` gets `TABLE-5FT-BLACK`.
   Products without options get a single default variant that simply reuses the product SKU, so stock can always be
   booked against them.

Editing a product reconciles the variant list: variants that are still selected keep their stock, removed
combinations are deactivated (never deleted) and new combinations are added.

## Stock

Stock is an append-only ledger – the current level of a variant is the sum of its movements. When you record an
`out` or an `adjustment` that would take the level below zero, EasyStock rejects it. Levels at or below the
product's low-stock threshold are flagged on the stock page and in the dashboard.

## Reports

### Export

**Export** writes a report to `runtime/exports/` and offers it as a download:

| Report    | Columns                                                                                                    |
|-----------|------------------------------------------------------------------------------------------------------------|
| Products  | `sku`, `name`, `unit`, `low_stock_threshold`, `variants`, `on_hand`, `active`                              |
| Stock     | `product_sku`, `product`, `variant_sku`, `options`, `on_hand`, `unit`, `low_stock_threshold`, `low_stock`, `last_movement` |
| Movements | `date`, `product`, `variant_sku`, `type`, `change`, `reference`, `note`, `unit_cost`, `user`               |

Pick the format with the format selector: `CSV`, `XLSX` (Excel) or `PDF`.

### Import

**Import** accepts a `.csv` or `.xlsx` file with these columns (a template is downloadable from the same page):

| Column                | Required | Notes                                                          |
|-----------------------|:--------:|----------------------------------------------------------------|
| `sku`                 |    ✔     | Product SKU; existing SKUs are updated                         |
| `name`                |    ✔     | Product name                                                   |
| `unit`                |          | Defaults to `piece`                                            |
| `low_stock_threshold` |          | Integer, defaults to `0`                                       |
| `active`              |          | `1`/`0`, defaults to `1`                                       |
| `options`             |          | `Size=XL; Color=Red`, missing option types/values are created  |
| `quantity`            |          | Opening stock; only applied when the product has one variant   |

The result page lists how many rows were imported and every rejected row with its line number and reason.

## QR codes and the public product page

Every product has a random token. Its detail page shows an SVG QR code (and a link to the raw SVG) that encodes
`/p/{token}`. Opening that URL shows a read-only summary – name, SKU, unit, stock per variant and low-stock
flags – **without authentication**, so it can be printed on a shelf label. Everything else requires a session.

## Project structure

```
assets/           CSS written by hand (tokens, layout, components, pages, auth)
config/           Config plugin: params, DI, routes, per-environment overrides
public/           Web entry point and public assets
runtime/          Database files, exported reports, caches, logs
src/
  Access/         Permissions, role matrix, access checker
  Console/        `user:create`, `seed:demo`
  Export/         Dataset builders and CSV/XLSX/PDF exporters
  Import/         CSV/XLSX readers and the product importer
  Migrations/     Schema migrations
  Products/       Products, options, variants, repositories and services
  QrCode/         Token generator and SVG QR code generator
  Shared/         Database helpers, application parameters
  Stock/          Ledger, levels and stock service
  User/           Users, roles, service and the current-user provider
  Web/            Actions and templates per feature (Auth, Dashboard, Products, …)
tests/            Codeception suites: Unit, Functional, Web, Console
```

## Tests and quality

```bash
composer test                       # all suites
./vendor/bin/codecept run Unit --no-interaction
./vendor/bin/codecept run Functional --no-interaction
./vendor/bin/codecept run Web --no-interaction        # starts a test server on port 8090
./vendor/bin/codecept run Console --no-interaction
```

| Suite        | What it covers                                                                          |
|--------------|-----------------------------------------------------------------------------------------|
| `Unit`       | Permissions, services, variant generation, exporters, importers, QR codes, pagination    |
| `Functional` | The application through PSR-7 requests: routing, permissions, forms, CSRF, flash messages |
| `Web`        | The running application over HTTP (PhpBrowser): login/logout, redirects, 404 page, public QR page and QR SVG |
| `Console`    | `user:create` and `seed:demo`, including their validation and exit codes                  |

The test suites run against `runtime/database/test.sqlite`, which is emptied before every test.

Quality gates:

```bash
./vendor/bin/psalm --no-progress                                   # static analysis, level 1
./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --diff    # coding style
./vendor/bin/rector process --dry-run                              # automated refactoring checks
./vendor/bin/composer-dependency-analyser --config=composer-dependency-analyser.php
```

With Docker: `make test`, `make test-coverage` (HTML report in `tests/_output/coverage/`), `make psalm`,
`make cs-fix`, `make rector`.

## AI usage

This project was built with the help of **AI (GitHub Copilot)**: the models, migrations, services, actions,
templates, styles and test suites were generated in an AI-assisted session, with the requirements, review and
acceptance decisions made by a human developer. Every change was reviewed, run and verified – the test suites,
Psalm, PHP CS Fixer, Rector and the dependency analyser all run clean on the code that was kept.

The domain model, the permission matrix and the UI conventions were chosen by hand; the AI was asked to follow
them. If you extend EasyStock, treat the code the same way: read it, run the tests, and keep the quality gates
green.
