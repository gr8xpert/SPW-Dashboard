# Smart Property Widget — Dashboard

Unified SaaS dashboard for managing [Smart Property Widget](https://smartpropertywidget.com) clients, subscriptions, licensing, and widget configuration.

## Overview

This Laravel application serves as the central admin panel for the Smart Property Widget platform. It manages:

- **Widget Clients** — CRM API credentials, domain authorization, widget config (branding, currencies, features)
- **License Keys** — Generation, activation, revocation, and validation for WordPress plugin installations
- **Subscriptions** — Plan management, billing (Paddle integration), grace periods, admin overrides
- **Support** — Ticket system, knowledge base, canned responses
- **Credits** — Credit-hour balance tracking for support/development time
- **Analytics** — Widget usage tracking per client

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 (PHP 8.2+) |
| Database | MySQL 8 / MariaDB |
| Frontend | Blade templates, Bootstrap 5, Bootstrap Icons |
| Billing | Paddle (subscriptions + one-time payments) |
| Auth | Laravel built-in session authentication |
| Encryption | Laravel `Crypt` (AES-256) for API keys |

## Architecture

```
Dashboard (this repo)
    ├── Admin Panel — manage clients, plans, licenses, support
    ├── Client Portal — billing, analytics, widget config
    ├── License API — validates/activates keys for WP plugin
    └── Paddle Webhooks — subscription lifecycle events

Widget Backend (RealtysoftV3 repo)
    ├── API Proxy — routes CRM requests through subscription check
    ├── Subscription Service — reads client data from this DB
    └── License API endpoint — delegates to SubscriptionService

WordPress Plugin (in RealtysoftV3 repo)
    └── Calls License API → receives client config → injects widget
```

## Key Models

| Model | Purpose |
|-------|---------|
| `Client` | Widget client — domain, API credentials, subscription, widget_config |
| `Plan` | Subscription plans (Starter, Professional, Enterprise) |
| `LicenseKey` | Unique keys for WP plugin activation (XXXX-XXXX-XXXX-XXXX) |
| `BrandKit` | Email/PDF branding (logo, colors, fonts) |
| `SupportTicket` | Client support tickets |
| `CreditTransaction` | Credit hour ledger |
| `WidgetAnalytic` | Widget usage events |

## Widget Config Flow

Widget configuration is centralized in the dashboard and pushed to WordPress sites:

```
Dashboard (widget_config JSON per client)
    ↓  license-api.php returns it during validate/activate
WP Plugin stores as wp_option (realtysoft_dashboard_config)
    ↓  inject_widget_scripts() merges:
    ↓  dashboard config (base) → WP settings → Custom Config (overrides)
Frontend: window.RealtySoftConfig = merged result
```

## Setup

### 1. Clone and install dependencies

```bash
git clone https://github.com/gr8xpert/SPW-Dashboard.git
cd SPW-Dashboard
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and configure database, Paddle keys, and other settings.

### 3. Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
```

### 4. Create storage symlink

```bash
php artisan storage:link
```

### 5. Serve

```bash
php artisan serve
```

## Environment Variables

See `.env.example` for all required variables. Key ones:

| Variable | Purpose |
|----------|---------|
| `DB_*` | Database connection |
| `PADDLE_*` | Paddle billing credentials |
| `INTERNAL_API_KEY` | Shared secret for inter-service API calls |
| `MAIL_*` | SMTP for transactional emails (invites, notifications) |

## Directory Structure

```
app/
├── Http/Controllers/
│   ├── Admin/                        # Super admin controllers
│   │   ├── WidgetClientController    # Client CRUD, connection check, license management
│   │   ├── CreditController          # Credit hour management
│   │   └── KnowledgeBaseController   # Support articles
│   ├── Client/                       # Client-facing dashboard
│   │   ├── BillingController         # Paddle checkout, portal
│   │   └── WidgetDashboardController # Client analytics & config
│   └── Billing/                      # Paddle webhook handlers
├── Models/                           # Eloquent models
├── Services/
│   ├── WidgetSubscriptionService     # Subscription lifecycle
│   ├── PaddleBillingService          # Paddle API integration
│   └── WidgetAnalyticsService        # Usage analytics
└── Console/Commands/                 # Scheduled tasks

database/migrations/                  # All schema migrations
resources/views/                      # Blade templates
routes/
├── web.php                           # Admin + client web routes
└── api.php                           # Paddle webhooks, internal API
```

## Security

- API keys encrypted with `Crypt::encryptString()` (AES-256-CBC)
- Paddle webhooks verified with signature validation
- Tenant isolation enforced at the Eloquent scope level
- CSRF protection on all web routes
- Setup/deployment scripts excluded from version control

## License

Proprietary. All rights reserved.
