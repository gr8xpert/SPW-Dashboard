<?php

use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\BounceController;
use App\Http\Controllers\Api\ContactApiController;
use App\Http\Controllers\Api\WidgetController;
use App\Http\Controllers\Api\WidgetProxyController;
use App\Http\Controllers\Api\WidgetInquiryController;
use App\Http\Controllers\Api\WidgetAnalyticsController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\Internal\TicketEmailReplyController;
use App\Http\Controllers\Api\Internal\MicroserviceController;
use App\Http\Controllers\Billing\PaddleWidgetWebhookController;
use App\Http\Controllers\Billing\PaddlePlatformWebhookController;
use Illuminate\Support\Facades\Route;

// ─── Paddle Webhooks (verified by per-account signature) ─────────────────────
Route::post('/webhooks/paddle/widget', [PaddleWidgetWebhookController::class, 'handle'])
    ->name('paddle.webhook.widget');
Route::post('/webhooks/paddle/platform', [PaddlePlatformWebhookController::class, 'handle'])
    ->name('paddle.webhook.platform');

// ─── Widget Public API (no auth — domain-validated, rate limited) ────────────
Route::middleware(['throttle:60,1', 'widget.cors'])->prefix('v1/widget')->name('widget.')->group(function () {
    Route::get('/subscription-check', [WidgetController::class, 'subscriptionCheck'])
        ->name('subscription-check');
    Route::get('/client-config', [WidgetController::class, 'clientConfig'])
        ->name('client-config');
    Route::post('/validate-license', [LicenseController::class, 'validate'])
        ->name('validate-license');
    Route::post('/activate-license', [LicenseController::class, 'activate'])
        ->name('activate-license');
    Route::post('/capture-inquiry', [WidgetInquiryController::class, 'captureInquiry'])
        ->name('capture-inquiry');
    Route::post('/analytics', [WidgetAnalyticsController::class, 'store'])
        ->name('analytics');

    // Proxy endpoints with display preferences applied
    Route::get('/locations', [WidgetProxyController::class, 'locations'])
        ->name('locations');
    Route::get('/property-types', [WidgetProxyController::class, 'propertyTypes'])
        ->name('property-types');
    Route::get('/features', [WidgetProxyController::class, 'features'])
        ->name('features');
    Route::get('/labels', [WidgetProxyController::class, 'labels'])
        ->name('labels');
});

// ─── Internal API (n8n → Laravel, verified by internal API key) ──────────────
Route::middleware('internal.api')->prefix('internal')->name('internal.')->group(function () {
    Route::post('/tickets/{ticket}/reply-from-email', [TicketEmailReplyController::class, 'handle'])
        ->name('tickets.reply-from-email');

    // Microservice endpoints (for spw-transform)
    Route::get('/client-resales-config', [MicroserviceController::class, 'resalesConfig'])
        ->name('client-resales-config');
    Route::get('/labels', [MicroserviceController::class, 'labels'])
        ->name('labels');
    Route::get('/display-preferences', [MicroserviceController::class, 'displayPreferences'])
        ->name('display-preferences');
});

// ─── SmartMailer API (API key authenticated, rate limited) ───────────────────
Route::middleware(['api.key', 'throttle:120,1'])->prefix('v1')->group(function () {
    // Contacts API
    Route::get('contacts',         [ContactApiController::class, 'index']);
    Route::post('contacts',        [ContactApiController::class, 'store']);
    Route::get('contacts/{id}',    [ContactApiController::class, 'show']);
    Route::put('contacts/{id}',    [ContactApiController::class, 'update']);
    Route::delete('contacts/{id}', [ContactApiController::class, 'destroy']);
    Route::post('contacts/tags',   [ContactApiController::class, 'addTags']);

    // Lists API
    Route::get('lists',                    [ContactApiController::class, 'lists']);
    Route::post('lists/{id}/contacts',     [ContactApiController::class, 'addToList']);

    // Webhooks from n8n
    Route::post('webhooks/campaign-status', [WebhookController::class, 'campaignStatus']);
    Route::post('webhooks/bounce',          [BounceController::class, 'handle']);
    Route::post('webhooks/complaint',       [BounceController::class, 'complaint']);
});
