<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RedisImportLogService
{
    protected string $streamKey = 'import_logs';
    protected int $maxLength = 1000;

    public function log(
        string $type,
        string $status,
        ?string $message = null,
        array $metadata = []
    ): string {
        try {
            // Process the metadata to ensure all values are strings
            $processedMetadata = $this->processMetadata($metadata);
            
            // Convert metadata to JSON string
            $jsonMetadata = json_encode($processedMetadata, JSON_UNESCAPED_UNICODE);
            if ($jsonMetadata === false) {
                $jsonMetadata = json_encode([
                    'error' => 'Failed to encode metadata',
                    'original' => print_r($processedMetadata, true)
                ]);
            }

            // Create the data array with all string values
            $data = [
                'type' => (string) $type,
                'status' => (string) $status,
                'message' => (string) ($message ?? ''),
                'metadata' => $jsonMetadata,
                'created_at' => Carbon::now()->toIso8601String()
            ];

            // Add to Redis stream
            return Redis::xadd(
                $this->streamKey,
                '*',
                $data,
                'MAXLEN',
                '~',
                $this->maxLength
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Redis log error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => isset($data) ? print_r($data, true) : null,
                'metadata' => isset($metadata) ? print_r($metadata, true) : null
            ]);
            return '';
        }
    }

    /**
     * Process metadata array to ensure all values are strings and not too long
     * Enhanced to handle complex data types
     */
    protected function processMetadata(array $metadata): array
    {
        $processed = [];
        foreach ($metadata as $key => $value) {
            // Convert key to string (just to be sure)
            $stringKey = (string) $key;
            
            if (is_array($value)) {
                // If value is array, convert to JSON string
                try {
                    $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                    if ($jsonValue === false) {
                        $processed[$stringKey] = 'Error encoding array';
                    } else {
                        $processed[$stringKey] = strlen($jsonValue) > 1000 
                            ? substr($jsonValue, 0, 997) . '...' 
                            : $jsonValue;
                    }
                } catch (\Exception $e) {
                    $processed[$stringKey] = 'Error encoding array: ' . $e->getMessage();
                }
            } elseif (is_object($value)) {
                // If value is object, try to convert to JSON or use toString
                try {
                    $jsonValue = json_encode($value, JSON_UNESCAPED_UNICODE);
                    if ($jsonValue === false) {
                        // Try using print_r as fallback
                        $processed[$stringKey] = substr(print_r($value, true), 0, 1000);
                    } else {
                        $processed[$stringKey] = strlen($jsonValue) > 1000 
                            ? substr($jsonValue, 0, 997) . '...'
                            : $jsonValue;
                    }
                } catch (\Exception $e) {
                    $processed[$stringKey] = 'Error encoding object: ' . $e->getMessage();
                }
            } elseif (is_resource($value)) {
                // Resources cannot be converted to strings directly
                $processed[$stringKey] = 'Resource type: ' . get_resource_type($value);
            } elseif (is_bool($value)) {
                // Convert boolean to more readable format
                $processed[$stringKey] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                // Handle null values
                $processed[$stringKey] = '';
            } else {
                // Handle all other scalar values (string, int, float)
                $stringValue = (string) $value;
                $processed[$stringKey] = strlen($stringValue) > 1000 
                    ? substr($stringValue, 0, 997) . '...' 
                    : $stringValue;
            }
        }
        return $processed;
    }

    public function getRecent(int $count = 100): Collection
    {
        $logs = Redis::xrevrange($this->streamKey, '+', '-', 'COUNT', $count);
        return collect($logs)->map(function ($item) {
            $data = $item[1];
            try {
                $data['metadata'] = json_decode($data['metadata'], true) ?? [];
            } catch (\Exception $e) {
                $data['metadata'] = [];
            }
            return $data;
        });
    }

    public function getByType(string $type, int $count = 100): Collection
    {
        $logs = $this->getRecent($this->maxLength);
        return $logs->filter(function ($log) use ($type) {
            return $log['type'] === $type;
        })->take($count);
    }

    public function getByStatus(string $status, int $count = 100): Collection
    {
        $logs = $this->getRecent($this->maxLength);
        return $logs->filter(function ($log) use ($status) {
            return $log['status'] === $status;
        })->take($count);
    }

    public function clear(): bool
    {
        return (bool) Redis::del($this->streamKey);
    }

    public function getStats(): array
    {
        $logs = $this->getRecent($this->maxLength);
        
        $typeStats = $logs->groupBy('type')->map->count();
        $statusStats = $logs->groupBy('status')->map->count();
        
        return [
            'total' => $logs->count(),
            'by_type' => $typeStats,
            'by_status' => $statusStats
        ];
    }
} 