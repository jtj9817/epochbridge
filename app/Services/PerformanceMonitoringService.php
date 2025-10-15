<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoringService
{
    /**
     * Monitor query performance for saved conversions.
     */
    public static function monitorSavedConversionsQuery($userId, $perPage = 10): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Enable query log
        DB::enableQueryLog();

        try {
            $conversions = \App\Models\SavedConversion::forUser($userId)
                ->withConversionData()
                ->paginate($perPage);

            $queries = DB::getQueryLog();
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $metrics = [
                'execution_time' => ($endTime - $startTime) * 1000, // in milliseconds
                'memory_usage' => $endMemory - $startMemory, // in bytes
                'query_count' => count($queries),
                'total_results' => $conversions->total(),
                'current_page' => $conversions->currentPage(),
                'per_page' => $conversions->perPage(),
                'queries' => array_map(function ($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time'],
                        'bindings' => $query['bindings'],
                    ];
                }, $queries),
            ];

            // Log slow queries (>100ms)
            if ($metrics['execution_time'] > 100) {
                Log::warning('Slow saved conversions query detected', [
                    'user_id' => $userId,
                    'metrics' => $metrics,
                ]);
            }

            // Cache metrics for monitoring dashboard
            Cache::put('saved_conversions_metrics_'.$userId, $metrics, 300); // 5 minutes

            return [
                'conversions' => $conversions,
                'metrics' => $metrics,
            ];

        } finally {
            DB::disableQueryLog();
        }
    }

    /**
     * Get storage usage statistics for saved conversions.
     */
    public static function getStorageStats(): array
    {
        $stats = DB::selectOne('
            SELECT 
                COUNT(*) as total_conversions,
                AVG(LENGTH(conversion_data)) as avg_json_size,
                MAX(LENGTH(conversion_data)) as max_json_size,
                MIN(LENGTH(conversion_data)) as min_json_size,
                SUM(LENGTH(conversion_data)) as total_json_size
            FROM saved_conversions 
            WHERE conversion_data IS NOT NULL
        ');

        return [
            'total_conversions' => (int) $stats->total_conversions,
            'avg_json_size_bytes' => (int) $stats->avg_json_size,
            'max_json_size_bytes' => (int) $stats->max_json_size,
            'min_json_size_bytes' => (int) $stats->min_json_size,
            'total_json_size_bytes' => (int) $stats->total_json_size,
            'total_json_size_mb' => round($stats->total_json_size / 1024 / 1024, 2),
        ];
    }

    /**
     * Check if JSON column indexing is needed.
     */
    public static function shouldAddJsonIndex(): bool
    {
        $stats = self::getStorageStats();

        // Consider JSON indexing if:
        // 1. We have more than 1000 conversions
        // 2. Average JSON size is reasonable (< 10KB)
        // 3. Total storage is significant (> 50MB)
        return $stats['total_conversions'] > 1000 &&
               $stats['avg_json_size_bytes'] < 10240 &&
               $stats['total_json_size_mb'] > 50;
    }

    /**
     * Get performance recommendations.
     */
    public static function getRecommendations(): array
    {
        $recommendations = [];
        $stats = self::getStorageStats();

        if ($stats['total_conversions'] > 10000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Consider implementing data archiving for old conversions',
                'action' => 'Archive conversions older than 1 year',
            ];
        }

        if ($stats['avg_json_size_bytes'] > 20480) { // > 20KB
            $recommendations[] = [
                'type' => 'storage',
                'priority' => 'medium',
                'message' => 'Average JSON size is large, consider compression',
                'action' => 'Implement JSON compression for conversion_data',
            ];
        }

        if (self::shouldAddJsonIndex()) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'medium',
                'message' => 'Consider adding JSON column indexes for better query performance',
                'action' => 'Add generated columns for frequently accessed JSON fields',
            ];
        }

        return $recommendations;
    }

    /**
     * Log performance metrics for monitoring.
     */
    public static function logMetrics(string $operation, array $metrics): void
    {
        Log::info('Performance metrics', [
            'operation' => $operation,
            'metrics' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
