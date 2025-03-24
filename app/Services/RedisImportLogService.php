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
            // Convert metadata to JSON string
            $jsonMetadata = json_encode($metadata, JSON_UNESCAPED_UNICODE);
            if ($jsonMetadata === false) {
                $jsonMetadata = json_encode([
                    'error' => 'Failed to encode metadata',
                    'original' => print_r($metadata, true)
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
                'data' => $data ?? null
            ]);
            return '';
        }
    }

    /**
     * Process metadata array to ensure all values are strings and not too long
     */
    protected function processMetadata(array $metadata): array
    {
        $processed = [];
        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $processed[$key] = $this->processMetadata($value);
            } else {
                // Convert to string and limit length to 1000 characters
                $stringValue = is_null($value) ? '' : (string) $value;
                $processed[$key] = strlen($stringValue) > 1000 
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