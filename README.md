# SmartMailer

A full-featured, multi-tenant email marketing SaaS platform built with Laravel 11. Clients get their own isolated workspace to manage contacts, build email campaigns, design templates, run automations, and track engagement — all powered by n8n for scalable SMTP delivery.

---

## Features

### Client Dashboard
- **Contacts** — Import via CSV, export, bulk actions (subscribe/unsubscribe/delete/add to list), search and filter
- **Contact Lists** — Segment contacts into named lists with subscriber counts
- **Templates** — Drag-and-drop email builder powered by [Unlayer](https://unlayer.com/), versioning, folder organisation, saved blocks
- **Campaigns** — Create, schedule, test-send, and send campaigns; A/B testing scaffold; per-campaign analytics
- **Automations** — Trigger-based email sequences (`contact_added`, `list_subscribed`) with configurable delay steps
- **Brand Kit** — Upload logo, set primary colour and font — auto-applied to all outgoing emails
- **Image Library** — Centralised media storage for email images
- **SMTP Accounts** — Add multiple SMTP credentials (passwords encrypted at rest with Laravel `Crypt`)
- **Team Management** — Invite team members by role (admin / editor / viewer), automated welcome email on invite
- **Analytics** — Open rate, click rate, bounce rate, unsubscribe rate per campaign; contact engagement scoring
- **Suppression Lists** — Per-client suppression + global platform suppression; contacts on either list are excluded from all sends

### Super Admin Panel
- Manage clients (suspend, impersonate, view usage)
- Manage subscription plans and quotas
- Global suppression list
- Audit logs

### Email Sending Engine (via n8n)
- SmartMailer **pushes** a batch payload to an n8n webhook
- n8n sends each email via its SMTP node, then **calls back** to SmartMailer with per-send results
- Supports open tracking (1×1 pixel), click tracking (all links wrapped), and one-click unsubscribe
- Personalization tokens: `{{first_name}}`, `{{last_name}}`, `{{email}}`, `{{company}}`
- Brand Kit is automatically wrapped around email HTML when configured

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 (PHP 8.2+) |
| Database | MySQL 8 |
| Cache / Queue | Redis (optional — falls back to file driver) |
| Email Delivery | n8n (self-hosted) via SMTP node |
| Frontend | Bootstrap 5, jQuery, Alpine.js |
| Email Editor | Unlayer Embed SDK |
| Auth | Laravel Breeze-style session auth + 2FA scaffold |
| Encryption | Laravel `Crypt` (AES-256) for SMTP passwords |

---

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ (for asset compilation, optional if using pre-built assets)
- [n8n](https://n8n.io) self-hosted instance (for email sending)

---

## Installation

### 1. Clone and install dependencies

```bash
git clone https://github.com/gr8xpert/smart-mailer.git
cd smart-mailer
composer install
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and fill in your values (see [Environment Variables](#environment-variables) below).

### 3. Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
```

This creates:
- Default admin account (see `database/seeders/AdminSeeder.php`)
- Default subscription plans (see `database/seeders/PlansSeeder.php`)

### 4. Create storage symlink

```bash
php artisan storage:link
```

### 5. (Optional) Build frontend assets

```bash
npm install && npm run build
```

### 6. Configure the scheduler

Add to your server's cron:

```
* * * * * cd /path/to/smartmailer && php artisan schedule:run >> /dev/null 2>&1
```

Or in Plesk: add a **Cron style** scheduled task with `* * * * *` running `php /path/to/smartmailer/artisan schedule:run`.

---

## Environment Variables

```env
APP_NAME=SmartMailer
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smartmailer
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=yourpassword
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="SmartMailer"

# n8n Integration
N8N_WEBHOOK_BASE_URL=https://your-n8n-instance.com
N8N_API_KEY=your_random_secret_string_here

# Stripe Billing (optional — leave blank to disable)
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### N8N_API_KEY
This is a **shared secret** you create yourself (any long random string, e.g. from `openssl rand -hex 32`). It is sent as `X-Callback-Secret` from n8n back to SmartMailer to verify callbacks are genuine.

---

## n8n Setup

SmartMailer requires two n8n workflows. Import the provided JSON files from the project root.

### Workflow 1 — Campaign Send (`n8n-campaign-send-workflow.json`)

1. In n8n, go to **Workflows → Import from File** and import `n8n-campaign-send-workflow.json`
2. Open the **Send Email** node → set your SMTP credential
3. Make sure the workflow is **Active**
4. The webhook URL will be: `https://your-n8n-instance.com/webhook/campaign-send`

### Workflow 2 — Automation Emails (`n8n-automation-workflow.json`)

1. Import `n8n-automation-workflow.json`
2. Open the **Send Email** node → set your SMTP credential
3. Activate the workflow
4. The webhook URL will be: `https://your-n8n-instance.com/webhook/automation`

Set `N8N_WEBHOOK_BASE_URL` in `.env` to your n8n base URL (without trailing slash).

### How the flow works

```
SmartMailer                              n8n
    │                                     │
    │── POST /webhook/campaign-send ──────►│
    │   { smtp, sends[], callback_url }   │
    │                                     │── splits sends array
    │                                     │── sends each email via SMTP
    │                                     │── POST callback_url per email
    │◄─ POST /api/n8n/callback ───────────│
    │   { send_id, status, error }        │
    │                                     │
```

---

## Architecture

### Multi-Tenancy

Every client gets a `client_id`. The `BelongsToTenant` trait + `TenantScope` automatically scope all queries to the authenticated client's data. Admin routes bypass tenant scoping.

### Scheduled Campaigns

The `ProcessScheduledCampaigns` console command runs every minute (via Laravel scheduler) and dispatches any campaigns with `status = 'scheduled'` and `scheduled_at <= now()`.

### Tracking

- **Opens**: A 1×1 transparent pixel is appended to every email. When loaded, `TrackingController` records an `email_event` and redirects to a transparent GIF.
- **Clicks**: All `href` links are rewritten to pass through `/t/c/{hash}`. The controller records the click, then 302-redirects to the original URL.
- **Unsubscribes**: A footer link with a unique token is injected into every email. Visiting `/unsubscribe/{token}` sets the contact's status to `unsubscribed`.

### Automations

1. Define an automation with a trigger (`contact_added` or `list_subscribed`)
2. Add email steps with subject, HTML body, and optional delay in minutes
3. When the trigger fires (contact created, contact added to list), `AutomationService` sends the step emails via the n8n automation webhook

---

## Directory Structure (Key Files)

```
app/
├── Services/
│   ├── EmailContentService.php      # Personalisation, brand kit, tracking, unsubscribe
│   ├── CampaignSendService.php      # Dispatch campaigns + test sends via n8n
│   └── AutomationService.php        # Fire automation emails via n8n
├── Http/Controllers/
│   ├── Api/
│   │   ├── N8nCallbackController.php # Receives delivery results from n8n
│   │   ├── TrackingController.php    # Open + click tracking
│   │   └── UnsubscribeController.php
│   ├── Client/                       # All client dashboard controllers
│   └── Admin/                        # Super admin controllers
├── Models/                           # Eloquent models (all tenant-scoped)
├── Console/Commands/
│   ├── ProcessScheduledCampaigns.php
│   ├── UpdateContactEngagement.php
│   └── ResetSmtpHourlyLimits.php
└── Mail/
    └── TeamWelcomeMail.php

config/
└── smartmailer.php                   # n8n URLs, API keys, plan defaults

n8n-campaign-send-workflow.json       # Import into n8n for campaign sending
n8n-automation-workflow.json          # Import into n8n for automation emails
```

---

## Scheduled Commands

| Command | Frequency | Purpose |
|---------|-----------|---------|
| `process:scheduled-campaigns` | Every minute | Dispatch campaigns whose scheduled time has passed |
| `update:contact-engagement` | Daily at midnight | Recalculate contact engagement tiers |
| `reset:smtp-hourly-limits` | Every hour | Reset per-SMTP hourly send counters |

---

## Security

- SMTP passwords encrypted with `Crypt::encryptString()` (AES-256-CBC)
- n8n callbacks verified with a shared secret (`X-Callback-Secret` header)
- Tenant isolation enforced at the Eloquent scope level — cross-tenant data access is not possible via the UI
- CSRF excluded only for the n8n callback route (`/api/n8n/callback`)
- Contact exports explicitly filter by `client_id` (safe during admin impersonation)
- Global suppression list enforced before every send — contacts on it are never emailed

---

## Admin Access

After seeding, log in at `/login` with the credentials defined in `AdminSeeder.php`. Admins can access `/admin/dashboard` and manage all clients, plans, and global settings.

---

## License

MIT
