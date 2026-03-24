<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WidgetAnalyticsService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('smartmailer.widget_analytics_url', '');
    }

    /**
     * Fetch all analytics data in parallel (summary, trends, properties, searches).
     *
     * @param string $domain  Client domain used as client_id
     * @param string $period  One of: 7, 30, 90, all
     * @return array  ['summary' => [...], 'trends' => [...], 'properties' => [...], 'searches' => [...], 'error' => bool]
     */
    public function getAllAnalytics(string $domain, string $period = '30'): array
    {
        if (empty($this->baseUrl)) {
            Log::warning('WidgetAnalyticsService: WIDGET_ANALYTICS_URL not configured');
            return $this->emptyResponse(true);
        }

        $apiPeriod = $this->mapPeriod($period);
        $clientId = $this->domainToClientId($domain);

        try {
            $responses = Http::pool(fn ($pool) => [
                $pool->as('summary')
                    ->timeout(10)
                    ->get($this->baseUrl, [
                        'action'    => 'summary',
                        'client_id' => $clientId,
                        'period'    => $apiPeriod,
                    ]),
                $pool->as('trends')
                    ->timeout(10)
                    ->get($this->baseUrl, [
                        'action'    => 'trends',
                        'client_id' => $clientId,
                        'period'    => $apiPeriod,
                    ]),
                $pool->as('properties')
                    ->timeout(10)
                    ->get($this->baseUrl, [
                        'action'    => 'properties',
                        'client_id' => $clientId,
                        'period'    => $apiPeriod,
                    ]),
                $pool->as('searches')
                    ->timeout(10)
                    ->get($this->baseUrl, [
                        'action'    => 'searches',
                        'client_id' => $clientId,
                        'period'    => $apiPeriod,
                    ]),
            ]);

            $hasError = false;
            $result = [];

            foreach (['summary', 'trends', 'properties', 'searches'] as $key) {
                if ($responses[$key] instanceof \Throwable || !$responses[$key]->successful()) {
                    Log::warning("WidgetAnalyticsService: {$key} request failed for {$domain}");
                    $hasError = true;
                    $result[$key] = [];
                } else {
                    $result[$key] = $responses[$key]->json() ?? [];
                }
            }

            $result['error'] = $hasError;

            return $result;
        } catch (\Throwable $e) {
            Log::error('WidgetAnalyticsService: ' . $e->getMessage());
            return $this->emptyResponse(true);
        }
    }

    /**
     * Map UI period values to API period names.
     */
    protected function mapPeriod(string $period): string
    {
        return match ($period) {
            '7'   => 'week',
            '30'  => 'month',
            '90'  => 'quarter',
            'all' => 'all',
            default => 'month',
        };
    }

    /**
     * Convert domain to the client_id format used by analytics CSV files.
     * The PHP analytics API expects the domain as-is (with dots).
     */
    protected function domainToClientId(string $domain): string
    {
        // Domain is used as-is - CSV files are named with dots (e.g., solobanus.com.csv)
        return $domain;
    }

    /**
     * Return empty response structure.
     */
    protected function emptyResponse(bool $error = false): array
    {
        return [
            'summary'    => [],
            'trends'     => [],
            'properties' => [],
            'searches'   => [],
            'error'      => $error,
        ];
    }
}
