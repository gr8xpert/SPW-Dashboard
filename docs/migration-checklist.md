# Migration Checklist — SmartPropertyWidget

## Stage 1: Build in Parallel (Current Stage)
- [x] New Laravel project created at C:\Users\shahzaib\SmartPropertyWidget
- [x] All migrations created (widget fields, license keys, analytics, tickets, credits, etc.)
- [x] All Eloquent models created
- [x] Paddle billing replaces Stripe
- [x] WidgetSubscriptionService with admin override logic
- [x] All API endpoints (subscription-check, client-config, license, inquiry, analytics)
- [x] Admin panel extensions (widget clients, license keys, tickets, webmasters, credits)
- [x] Client dashboard extensions (widget status, analytics, setup, tickets, credits)
- [x] Webmaster dashboard (tickets, timesheet)
- [x] Support ticket system with credit hours
- [x] Security middleware (headers, CORS, internal API auth)
- [x] Artisan commands (check-subscriptions, auto-close-tickets)
- [x] Audit logging enhanced
- [ ] Deploy to staging server
- [ ] Run migrations and seeders
- [ ] Install npm dependencies and build frontend assets

## Stage 2: Internal Testing
- [ ] Test full lifecycle: signup → admin setup → widget activation → inquiry → mailer
- [ ] Test Paddle webhooks with sandbox mode
- [ ] Test admin override and internal site flags
- [ ] Test credit hour booking and purchase flow
- [ ] Test ticket lifecycle (create → assign → work → review → resolve → close)
- [ ] Test email reply-to-ticket via n8n

## Stage 3: Parallel Run
- [ ] Deploy Laravel platform to production alongside existing PHP backend
- [ ] Widget proxy feature flag: `USE_LARAVEL_API=true` per client
- [ ] Migrate ONE test client (e.g., internal site) to new system
- [ ] Verify widget continues working correctly
- [ ] Gradually migrate more clients

## Stage 4: Full Cutover
- [ ] All clients migrated and verified
- [ ] Update WordPress plugin API endpoints:
  - `class-license-manager.php`: Change `$api_base_url` from
    `https://smartpropertywidget.com/spw/php/subscription/` to
    `https://smartpropertywidget.com/api/v1/widget/`
  - `validate-license.php` → `POST /api/v1/widget/validate-license`
  - `activate-license.php` → `POST /api/v1/widget/activate-license`
- [ ] Update `api-proxy.php`: Replace SubscriptionService with HTTP call to Laravel API
- [ ] Update `send-inquiry.php`: Add contact capture POST to Laravel API
- [ ] Release new WP plugin version
- [ ] Remove old PHP backend files
- [ ] Final DNS/routing updates
