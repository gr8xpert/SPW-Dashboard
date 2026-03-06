# Smart Property Widget - Laravel Dashboard

## Project Overview
Multi-tenant SaaS platform for managing property widget clients. Provides admin dashboard for client management, subscription handling, and widget customization.

## Tech Stack
- **Framework**: Laravel 10+
- **Database**: MySQL
- **Frontend**: Blade templates, Bootstrap 5
- **API**: RESTful JSON APIs

## Key Features

### Client Management
- Widget client CRUD operations
- Subscription management (active, grace, expired, manual, internal)
- Admin override for subscriptions
- License key generation and validation

### Display Preferences
- **Location Grouping**: Custom location groups with feed location mappings
- **Property Type Grouping**: Custom property type groups
- **Feature Grouping**: Custom feature groups
- Sort order, visibility, and custom naming for all item types

### Widget Proxy API
Endpoints at `/api/v1/widget/`:
- `GET /locations?domain={domain}` - Returns locations with custom grouping/preferences
- `GET /property-types?domain={domain}` - Returns property types with preferences
- `GET /features?domain={domain}` - Returns features with preferences

## Important Files

### Controllers
- `app/Http/Controllers/Admin/WidgetClientController.php` - Client management
- `app/Http/Controllers/Admin/LocationGroupingController.php` - Location grouping
- `app/Http/Controllers/Admin/PropertyTypeGroupingController.php` - Property type grouping
- `app/Http/Controllers/Admin/FeatureGroupingController.php` - Feature grouping
- `app/Http/Controllers/Api/WidgetProxyController.php` - Widget API proxy

### Services
- `app/Services/WidgetSubscriptionService.php` - Subscription checks
- `app/Services/LocationGroupingService.php` - Location grouping logic
- `app/Services/PropertyTypeGroupingService.php` - Property type grouping logic
- `app/Services/FeatureGroupingService.php` - Feature grouping logic

### Models
- `app/Models/Client.php` - Main client model
- `app/Models/ClientDisplayPreference.php` - Sort order, visibility, custom names
- `app/Models/ClientCustomLocationGroup.php` - Custom location groups
- `app/Models/ClientCustomPropertyTypeGroup.php` - Custom property type groups
- `app/Models/ClientCustomFeatureGroup.php` - Custom feature groups
- `app/Models/ClientLocationMapping.php` - Location to group mappings
- `app/Models/ClientPropertyTypeMapping.php` - Property type to group mappings
- `app/Models/ClientFeatureMapping.php` - Feature to group mappings

## Database Tables

### Core
- `clients` - Client accounts with API credentials, subscription status
- `client_display_preferences` - Sort order, visibility per item type

### Custom Grouping
- `client_custom_location_groups` - Custom location groups
- `client_location_mappings` - Feed location to group mappings
- `client_custom_property_type_groups` - Custom property type groups
- `client_property_type_mappings` - Feed type to group mappings
- `client_custom_feature_groups` - Custom feature groups
- `client_feature_mappings` - Feed feature to group mappings

### Client Flags
```php
$client->custom_location_grouping_enabled      // boolean
$client->custom_property_type_grouping_enabled // boolean
$client->custom_feature_grouping_enabled       // boolean
```

## API Flow

1. Widget frontend calls proxy at `smartpropertywidget.com/spw/php/api-proxy.php`
2. Proxy routes to dashboard API: `/api/v1/widget/{endpoint}?domain={domain}`
3. Dashboard fetches from CRM API, applies display preferences, returns sorted data
4. Widget receives data in correct order (no alphabetical re-sorting)

## Deployment

### Migrations on Shared Hosting
Create PHP scripts in `public/` folder:
```php
// public/deploy-xyz.php
$SECRET_TOKEN = 'your_token';
if ($_GET['token'] !== $SECRET_TOKEN) die('Access denied');
// Run artisan commands...
```

### Cache Clear
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Recent Changes (March 2026)

### Added Custom Grouping for Property Types & Features
- New controllers, services, models for property type and feature grouping
- Same functionality as location grouping
- Routes added to `routes/web.php`

### Fixed Widget Proxy Sorting
- Added `isRootItem()` helper to handle `parent_id: false` from CRM
- Fixed `sortByPreferences()` to properly detect root items

### Removed Widget Detection
- Removed confusing "Widget: Not detected" status check
- Was unreliable with WordPress installations

## Notes

- CRM API returns `parent_id: false` for root items (not null or 0)
- Display preferences use string item_ids
- Sort order is per-parent-group (hierarchical), not global
