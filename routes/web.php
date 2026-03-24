<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\GlobalSuppressionController;
use App\Http\Controllers\Admin\WidgetClientController;
use App\Http\Controllers\Admin\LicenseKeyController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\WebmasterController;
use App\Http\Controllers\Admin\CreditController as AdminCreditController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\LabelController as AdminLabelController;
use App\Http\Controllers\Admin\LocationGroupingController;
use App\Http\Controllers\Admin\PropertyTypeGroupingController;
use App\Http\Controllers\Admin\FeatureGroupingController;
use App\Http\Controllers\Client\DashboardController as ClientDashboard;
use App\Http\Controllers\Client\ContactController;
use App\Http\Controllers\Client\ListController;
use App\Http\Controllers\Client\TemplateController;
use App\Http\Controllers\Client\TemplateFolderController;
use App\Http\Controllers\Client\CampaignController;
use App\Http\Controllers\Client\AutomationController;
use App\Http\Controllers\Client\AnalyticsController;
use App\Http\Controllers\Client\TeamController;
use App\Http\Controllers\Client\SmtpAccountController;
use App\Http\Controllers\Client\BrandKitController;
use App\Http\Controllers\Client\ImageLibraryController;
use App\Http\Controllers\Client\SettingsController;
use App\Http\Controllers\Client\BillingController;
use App\Http\Controllers\Client\WidgetDashboardController;
use App\Http\Controllers\Client\TicketController as ClientTicketController;
use App\Http\Controllers\Client\CreditController as ClientCreditController;
use App\Http\Controllers\Client\OnboardingController;
use App\Http\Controllers\Client\PrivacyController;
use App\Http\Controllers\Client\LabelOverrideController;
use App\Http\Controllers\Webmaster\TicketController as WebmasterTicketController;
use App\Http\Controllers\Webmaster\TimesheetController;
use App\Http\Controllers\Api\N8nCallbackController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UnsubscribeController;
use App\Models\KnowledgeArticle;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────────────────────────────

Route::get('/', function () {
    return redirect()->route('login');
});

// Cron scheduler endpoint (for Plesk "Fetch a URL" scheduled tasks)
// SECURITY: Token MUST be set in .env as APP_CRON_TOKEN - no default fallback
Route::get('/cron-scheduler', function () {
    $configuredToken = config('app.cron_token');
    if (empty($configuredToken)) {
        abort(500, 'APP_CRON_TOKEN not configured');
    }

    $token = request()->query('token');
    if (!hash_equals($configuredToken, $token ?? '')) {
        abort(403, 'Invalid token');
    }

    $php = '/opt/plesk/php/8.3/bin/php';
    $artisan = base_path('artisan');
    $command = escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' schedule:run 2>&1';
    $output = [];
    $code = 0;
    exec($command, $output, $code);
    return response(implode("\n", $output) . "\nExit: $code", 200)
        ->header('Content-Type', 'text/plain');
});

// Auth
Route::get('/login',          [LoginController::class, 'show'])->name('login');
Route::post('/login',         [LoginController::class, 'login'])->name('login.post');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register',       [RegisterController::class, 'show'])->name('register');
Route::post('/register',      [RegisterController::class, 'register'])->name('register.post');
Route::get('/forgot-password',[ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password',[ForgotPasswordController::class, 'send'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password',[ForgotPasswordController::class, 'reset'])->name('password.update');

// 2FA
Route::get('/2fa',            [TwoFactorController::class, 'show'])->name('2fa.show');
Route::post('/2fa',           [TwoFactorController::class, 'verify'])->name('2fa.verify');

// Email Verification
Route::get('/email/verify',   function () { return view('auth.verify-email'); })
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('dashboard.home');
})->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// n8n callback (no auth — verified internally by shared secret)
Route::post('/api/n8n/callback', [N8nCallbackController::class, 'handle'])->name('n8n.callback');

// Tracking (no auth needed)
Route::get('/t/o/{hash}',       [TrackingController::class, 'open'])->name('track.open');
Route::get('/t/c/{hash}',       [TrackingController::class, 'click'])->name('track.click');
Route::get('/unsubscribe/{token}', [UnsubscribeController::class, 'show'])->name('unsubscribe.show');
Route::post('/unsubscribe/{token}',[UnsubscribeController::class, 'process'])->name('unsubscribe.process');

// Account status pages
Route::view('/suspended', 'auth.suspended')->name('suspended');
Route::view('/cancelled', 'auth.cancelled')->name('cancelled');

// Profile / Change Password (accessible to all authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', function () {
        return view('auth.profile', ['user' => auth()->user()]);
    })->name('profile');

    Route::put('/profile/password', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        auth()->user()->update(['password' => \Illuminate\Support\Facades\Hash::make($request->password)]);

        return back()->with('success', 'Password changed successfully.');
    })->name('profile.password');
});

// ─── Client Dashboard Routes ──────────────────────────────────────────────────

Route::middleware(['auth', 'tenant', 'verified'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {

    Route::get('/', [ClientDashboard::class, 'index'])->name('index');
    Route::get('', [ClientDashboard::class, 'index'])->name('home');

    // Contacts
    Route::post('contacts/import',       [ContactController::class, 'import'])->name('contacts.import');
    Route::get('contacts/export',        [ContactController::class, 'export'])->name('contacts.export');
    Route::post('contacts/bulk-action',  [ContactController::class, 'bulkAction'])->name('contacts.bulk-action');
    Route::resource('contacts', ContactController::class);

    // Lists
    Route::resource('lists', ListController::class);

    // Templates
    Route::resource('templates', TemplateController::class);
    Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate'])->name('templates.duplicate');
    Route::get('templates/{template}/versions',   [TemplateController::class, 'versions'])->name('templates.versions');
    Route::post('templates/{template}/restore/{version}', [TemplateController::class, 'restore'])->name('templates.restore');
    Route::post('templates/ai-generate',          [TemplateController::class, 'aiGenerate'])->name('templates.ai-generate');

    // Template Folders
    Route::get('template-folders',              [TemplateFolderController::class, 'index'])->name('template-folders.index');
    Route::post('template-folders',             [TemplateFolderController::class, 'store'])->name('template-folders.store');
    Route::put('template-folders/{folder}',     [TemplateFolderController::class, 'update'])->name('template-folders.update');
    Route::delete('template-folders/{folder}',  [TemplateFolderController::class, 'destroy'])->name('template-folders.destroy');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/schedule',  [CampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/send-now',  [CampaignController::class, 'sendNow'])->name('campaigns.send-now');
    Route::post('campaigns/{campaign}/pause',     [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/cancel',    [CampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::post('campaigns/{campaign}/test-send', [CampaignController::class, 'testSend'])->name('campaigns.test-send');
    Route::get('campaigns/{campaign}/preview',    [CampaignController::class, 'preview'])->name('campaigns.preview');
    Route::get('campaigns/{campaign}/stats',      [CampaignController::class, 'stats'])->name('campaigns.stats');

    // Automations
    Route::resource('automations', AutomationController::class);
    Route::post('automations/{automation}/activate',        [AutomationController::class, 'activate'])->name('automations.activate');
    Route::post('automations/{automation}/pause',           [AutomationController::class, 'pause'])->name('automations.pause');
    Route::post('automations/{automation}/steps',           [AutomationController::class, 'addStep'])->name('automations.steps.store');
    Route::delete('automations/{automation}/steps/{step}',  [AutomationController::class, 'removeStep'])->name('automations.steps.destroy');

    // Analytics
    Route::get('analytics',            [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/campaigns',  [AnalyticsController::class, 'campaigns'])->name('analytics.campaigns');
    Route::get('analytics/contacts',   [AnalyticsController::class, 'contacts'])->name('analytics.contacts');

    // Team
    Route::resource('team', TeamController::class);

    // SMTP Accounts
    Route::resource('smtp-accounts', SmtpAccountController::class);
    Route::post('smtp-accounts/{smtpAccount}/test',        [SmtpAccountController::class, 'test'])->name('smtp-accounts.test');
    Route::post('smtp-accounts/{smtpAccount}/set-default', [SmtpAccountController::class, 'setDefault'])->name('smtp-accounts.set-default');

    // Brand Kit
    Route::get('brand-kit',  [BrandKitController::class, 'edit'])->name('brand-kit.edit');
    Route::put('brand-kit',  [BrandKitController::class, 'update'])->name('brand-kit.update');

    // Image Library
    Route::get('images',          [ImageLibraryController::class, 'index'])->name('images.index');
    Route::post('images/upload',  [ImageLibraryController::class, 'upload'])->name('images.upload');
    Route::delete('images/{id}',  [ImageLibraryController::class, 'destroy'])->name('images.destroy');

    // Settings
    Route::get('settings',                         [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings',                         [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/api-keys/regenerate',    [SettingsController::class, 'regenerateApiKey'])->name('settings.api-keys.regenerate');

    // Billing
    Route::get('billing',            [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('billing/cancel',    [BillingController::class, 'cancel'])->name('billing.cancel');

    // 2FA Setup
    Route::get('2fa/setup',  [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('2fa/enable',[TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('2fa/disable',[TwoFactorController::class, 'disable'])->name('2fa.disable');

    // ─── Widget Management (NEW) ──────────────────────────────────────────
    Route::get('widget',                   [WidgetDashboardController::class, 'index'])->name('widget.index');
    Route::get('widget/analytics',         [WidgetDashboardController::class, 'analytics'])->name('widget.analytics');
    Route::get('widget/setup',             [WidgetDashboardController::class, 'setup'])->name('widget.setup');
    Route::get('widget/inquiry-contacts',  [WidgetDashboardController::class, 'inquiryContacts'])->name('widget.inquiry-contacts');
    Route::get('widget/inquiry-contacts/export', [WidgetDashboardController::class, 'exportInquiryContacts'])->name('widget.inquiry-contacts.export');
    Route::patch('widget/inquiry-contacts/{inquiry}/status', [WidgetDashboardController::class, 'updateInquiryStatus'])->name('widget.inquiry-contacts.update-status');
    Route::get('widget/download-plugin',  [WidgetDashboardController::class, 'downloadPlugin'])->name('widget.download-plugin');
    Route::put('widget/settings',         [WidgetDashboardController::class, 'updateSettings'])->name('widget.update-settings');
    Route::get('widget/config',           [WidgetDashboardController::class, 'config'])->name('widget.config');
    Route::put('widget/config',           [WidgetDashboardController::class, 'saveConfig'])->name('widget.save-config');

    // ─── Support Tickets (NEW) ────────────────────────────────────────────
    Route::get('tickets',                  [ClientTicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/create',           [ClientTicketController::class, 'create'])->name('tickets.create');
    Route::post('tickets',                 [ClientTicketController::class, 'store'])->name('tickets.store');
    Route::get('tickets/{ticket}',         [ClientTicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/message',[ClientTicketController::class, 'addMessage'])->name('tickets.message');

    // ─── Credit Hours (NEW) ───────────────────────────────────────────────
    Route::get('credits',         [ClientCreditController::class, 'index'])->name('credits.index');
    Route::get('credits/buy',     [ClientCreditController::class, 'buy'])->name('credits.buy');
    Route::post('credits/purchase', [ClientCreditController::class, 'purchase'])->name('credits.purchase');

    // ─── Onboarding (NEW) ─────────────────────────────────────────────────
    Route::get('onboarding',        [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('onboarding/step',  [OnboardingController::class, 'saveStep'])->name('onboarding.save-step');
    Route::post('onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');

    // ─── Privacy / GDPR (NEW) ─────────────────────────────────────────────
    Route::get('privacy',            [PrivacyController::class, 'index'])->name('privacy.index');
    Route::get('privacy/export',     [PrivacyController::class, 'export'])->name('privacy.export');
    Route::post('privacy/delete',    [PrivacyController::class, 'requestDeletion'])->name('privacy.delete');

    // ─── Widget Labels (Client Overrides) ────────────────────────────────
    Route::get('labels',         [LabelOverrideController::class, 'index'])->name('labels.index');
    Route::post('labels/update', [LabelOverrideController::class, 'update'])->name('labels.update');
    Route::post('labels/reset',  [LabelOverrideController::class, 'reset'])->name('labels.reset');

    // ─── Knowledge Base (Client View) ─────────────────────────────────────
    Route::get('help', function () {
        $articles = KnowledgeArticle::published()->orderBy('category')->orderBy('sort_order')->get();
        return view('client.help.index', compact('articles'));
    })->name('help.index');
});

// ─── Webmaster Routes (NEW) ──────────────────────────────────────────────────

Route::middleware(['auth', 'role:webmaster'])
    ->prefix('dashboard')
    ->name('dashboard.')
    ->group(function () {

    Route::get('my-tickets',                         [WebmasterTicketController::class, 'index'])->name('my-tickets.index');
    Route::get('my-tickets/{ticket}',                [WebmasterTicketController::class, 'show'])->name('my-tickets.show');
    Route::post('my-tickets/{ticket}/start',         [WebmasterTicketController::class, 'startWork'])->name('my-tickets.start');
    Route::post('my-tickets/{ticket}/message',       [WebmasterTicketController::class, 'addMessage'])->name('my-tickets.message');
    Route::post('my-tickets/{ticket}/book-hours',    [WebmasterTicketController::class, 'bookHours'])->name('my-tickets.book-hours');
    Route::post('my-tickets/{ticket}/submit-review', [WebmasterTicketController::class, 'submitForReview'])->name('my-tickets.submit-review');
    Route::get('my-timesheet',                       [TimesheetController::class, 'index'])->name('my-timesheet.index');
});

// ─── Super Admin Routes ───────────────────────────────────────────────────────

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

    // Existing
    Route::resource('clients', AdminClientController::class);
    Route::post('clients/{client}/suspend',     [AdminClientController::class, 'suspend'])->name('clients.suspend');
    Route::post('clients/{client}/activate',    [AdminClientController::class, 'activate'])->name('clients.activate');
    Route::post('clients/{client}/impersonate', [AdminClientController::class, 'impersonate'])->name('clients.impersonate');
    Route::get('stop-impersonating',            [AdminClientController::class, 'stopImpersonating'])->name('stop-impersonating');
    Route::get('clients/{client}/users/{user}/reset-password',  [AdminClientController::class, 'resetPassword'])->name('clients.reset-password');
    Route::post('clients/{client}/users/{user}/update-password', [AdminClientController::class, 'updatePassword'])->name('clients.update-password');
    Route::resource('plans', AdminPlanController::class);
    Route::get('global-suppressions',  [GlobalSuppressionController::class, 'index'])->name('suppressions.index');
    Route::post('global-suppressions', [GlobalSuppressionController::class, 'store'])->name('suppressions.store');
    Route::delete('global-suppressions/{id}', [GlobalSuppressionController::class, 'destroy'])->name('suppressions.destroy');

    // ─── Widget Client Management (NEW) ───────────────────────────────────
    Route::get('widget-clients',                              [WidgetClientController::class, 'index'])->name('widget-clients.index');
    Route::get('widget-clients/{client}/edit',                [WidgetClientController::class, 'edit'])->name('widget-clients.edit');
    Route::put('widget-clients/{client}',                     [WidgetClientController::class, 'update'])->name('widget-clients.update');
    Route::post('widget-clients/{client}/toggle-override',    [WidgetClientController::class, 'toggleOverride'])->name('widget-clients.toggle-override');
    Route::post('widget-clients/{client}/extend',             [WidgetClientController::class, 'extendSubscription'])->name('widget-clients.extend');
    Route::post('widget-clients/{client}/manual-activate',    [WidgetClientController::class, 'manualActivate'])->name('widget-clients.manual-activate');
    Route::post('widget-clients/{client}/expire',             [WidgetClientController::class, 'expire'])->name('widget-clients.expire');
    Route::get('subscription-status',                         [WidgetClientController::class, 'subscriptionStatus'])->name('subscription-status');

    // ─── License Keys (inline on widget-client edit) ───────────────────────
    Route::post('widget-clients/{client}/revoke-license',      [WidgetClientController::class, 'revokeLicense'])->name('widget-clients.revoke-license');
    Route::post('widget-clients/{client}/regenerate-license',  [WidgetClientController::class, 'regenerateLicense'])->name('widget-clients.regenerate-license');
    Route::get('widget-clients/{client}/check-connection',     [WidgetClientController::class, 'checkConnection'])->name('widget-clients.check-connection');
    Route::get('widget-clients/{client}/test-resales',         [WidgetClientController::class, 'testResales'])->name('widget-clients.test-resales');
    Route::get('widget-clients/{client}/display-preferences',  [WidgetClientController::class, 'displayPreferences'])->name('widget-clients.display-preferences');
    Route::post('widget-clients/{client}/display-preferences', [WidgetClientController::class, 'saveDisplayPreferences'])->name('widget-clients.save-display-preferences');
    Route::post('widget-clients/{client}/move-preference',     [WidgetClientController::class, 'movePreference'])->name('widget-clients.move-preference');

    // ─── Location Grouping ───────────────────────────────────────────────
    Route::get('widget-clients/{client}/location-grouping',                       [LocationGroupingController::class, 'index'])->name('widget-clients.location-grouping.index');
    Route::post('widget-clients/{client}/location-grouping/toggle',               [LocationGroupingController::class, 'toggleFeature'])->name('widget-clients.location-grouping.toggle');
    Route::post('widget-clients/{client}/location-grouping/groups',               [LocationGroupingController::class, 'storeGroup'])->name('widget-clients.location-grouping.groups.store');
    Route::put('widget-clients/{client}/location-grouping/groups/{group}',        [LocationGroupingController::class, 'updateGroup'])->name('widget-clients.location-grouping.groups.update');
    Route::delete('widget-clients/{client}/location-grouping/groups/{group}',     [LocationGroupingController::class, 'destroyGroup'])->name('widget-clients.location-grouping.groups.destroy');
    Route::post('widget-clients/{client}/location-grouping/groups/reorder',       [LocationGroupingController::class, 'reorderGroups'])->name('widget-clients.location-grouping.groups.reorder');
    Route::post('widget-clients/{client}/location-grouping/groups/{group}/map',   [LocationGroupingController::class, 'mapLocations'])->name('widget-clients.location-grouping.groups.map');
    Route::delete('widget-clients/{client}/location-grouping/mappings/{mapping}', [LocationGroupingController::class, 'unmapLocation'])->name('widget-clients.location-grouping.mappings.destroy');
    Route::post('widget-clients/{client}/location-grouping/groups/{group}/reorder-mappings', [LocationGroupingController::class, 'reorderMappings'])->name('widget-clients.location-grouping.mappings.reorder');
    Route::get('widget-clients/{client}/location-grouping/unmapped',              [LocationGroupingController::class, 'getUnmapped'])->name('widget-clients.location-grouping.unmapped');

    // ─── Property Type Grouping ──────────────────────────────────────────
    Route::get('widget-clients/{client}/property-type-grouping',                       [PropertyTypeGroupingController::class, 'index'])->name('widget-clients.property-type-grouping.index');
    Route::post('widget-clients/{client}/property-type-grouping/toggle',               [PropertyTypeGroupingController::class, 'toggleFeature'])->name('widget-clients.property-type-grouping.toggle');
    Route::post('widget-clients/{client}/property-type-grouping/groups',               [PropertyTypeGroupingController::class, 'storeGroup'])->name('widget-clients.property-type-grouping.groups.store');
    Route::put('widget-clients/{client}/property-type-grouping/groups/{group}',        [PropertyTypeGroupingController::class, 'updateGroup'])->name('widget-clients.property-type-grouping.groups.update');
    Route::delete('widget-clients/{client}/property-type-grouping/groups/{group}',     [PropertyTypeGroupingController::class, 'destroyGroup'])->name('widget-clients.property-type-grouping.groups.destroy');
    Route::post('widget-clients/{client}/property-type-grouping/groups/reorder',       [PropertyTypeGroupingController::class, 'reorderGroups'])->name('widget-clients.property-type-grouping.groups.reorder');
    Route::post('widget-clients/{client}/property-type-grouping/groups/{group}/map',   [PropertyTypeGroupingController::class, 'mapTypes'])->name('widget-clients.property-type-grouping.groups.map');
    Route::post('widget-clients/{client}/property-type-grouping/groups/{group}/reorder-mappings', [PropertyTypeGroupingController::class, 'reorderMappings'])->name('widget-clients.property-type-grouping.mappings.reorder');
    Route::delete('widget-clients/{client}/property-type-grouping/mappings/{mapping}', [PropertyTypeGroupingController::class, 'unmapType'])->name('widget-clients.property-type-grouping.mappings.destroy');
    Route::get('widget-clients/{client}/property-type-grouping/unmapped',              [PropertyTypeGroupingController::class, 'getUnmapped'])->name('widget-clients.property-type-grouping.unmapped');

    // ─── Feature Grouping ────────────────────────────────────────────────
    Route::get('widget-clients/{client}/feature-grouping',                       [FeatureGroupingController::class, 'index'])->name('widget-clients.feature-grouping.index');
    Route::post('widget-clients/{client}/feature-grouping/toggle',               [FeatureGroupingController::class, 'toggleFeature'])->name('widget-clients.feature-grouping.toggle');
    Route::post('widget-clients/{client}/feature-grouping/groups',               [FeatureGroupingController::class, 'storeGroup'])->name('widget-clients.feature-grouping.groups.store');
    Route::put('widget-clients/{client}/feature-grouping/groups/{group}',        [FeatureGroupingController::class, 'updateGroup'])->name('widget-clients.feature-grouping.groups.update');
    Route::delete('widget-clients/{client}/feature-grouping/groups/{group}',     [FeatureGroupingController::class, 'destroyGroup'])->name('widget-clients.feature-grouping.groups.destroy');
    Route::post('widget-clients/{client}/feature-grouping/groups/reorder',       [FeatureGroupingController::class, 'reorderGroups'])->name('widget-clients.feature-grouping.groups.reorder');
    Route::post('widget-clients/{client}/feature-grouping/groups/{group}/map',   [FeatureGroupingController::class, 'mapFeatures'])->name('widget-clients.feature-grouping.groups.map');
    Route::post('widget-clients/{client}/feature-grouping/groups/{group}/reorder-mappings', [FeatureGroupingController::class, 'reorderMappings'])->name('widget-clients.feature-grouping.mappings.reorder');
    Route::delete('widget-clients/{client}/feature-grouping/mappings/{mapping}', [FeatureGroupingController::class, 'unmapFeature'])->name('widget-clients.feature-grouping.mappings.destroy');
    Route::get('widget-clients/{client}/feature-grouping/unmapped',              [FeatureGroupingController::class, 'getUnmapped'])->name('widget-clients.feature-grouping.unmapped');

    // ─── Tickets (NEW) ───────────────────────────────────────────────────
    Route::get('tickets',                          [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}',                 [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('tickets/{ticket}/assign',         [AdminTicketController::class, 'assign'])->name('tickets.assign');
    Route::post('tickets/{ticket}/message',        [AdminTicketController::class, 'addMessage'])->name('tickets.message');
    Route::post('tickets/{ticket}/resolve',        [AdminTicketController::class, 'resolve'])->name('tickets.resolve');
    Route::post('tickets/{ticket}/send-back',      [AdminTicketController::class, 'sendBack'])->name('tickets.send-back');

    // ─── Webmasters (NEW) ────────────────────────────────────────────────
    Route::get('webmasters',         [WebmasterController::class, 'index'])->name('webmasters.index');
    Route::get('webmasters/create',  [WebmasterController::class, 'create'])->name('webmasters.create');
    Route::post('webmasters',        [WebmasterController::class, 'store'])->name('webmasters.store');
    Route::delete('webmasters/{webmaster}', [WebmasterController::class, 'destroy'])->name('webmasters.destroy');

    // ─── Credits (NEW) ───────────────────────────────────────────────────
    Route::post('credits/{client}/add',  [AdminCreditController::class, 'addCredits'])->name('credits.add');
    Route::get('credits/overview',       [AdminCreditController::class, 'overview'])->name('credits.overview');

    // ─── Audit Log (NEW) ─────────────────────────────────────────────────
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');

    // ─── Knowledge Base (NEW) ────────────────────────────────────────────
    Route::resource('knowledge-base', KnowledgeBaseController::class)->except(['show']);

    // ─── Widget Labels (NEW) ──────────────────────────────────────────────
    Route::get('labels',              [AdminLabelController::class, 'index'])->name('labels.index');
    Route::post('labels',             [AdminLabelController::class, 'store'])->name('labels.store');
    Route::put('labels/{label}',      [AdminLabelController::class, 'update'])->name('labels.update');
    Route::delete('labels/{label}',   [AdminLabelController::class, 'destroy'])->name('labels.destroy');
    Route::post('labels/import',      [AdminLabelController::class, 'import'])->name('labels.import');
    Route::get('labels/export',       [AdminLabelController::class, 'export'])->name('labels.export');
});
