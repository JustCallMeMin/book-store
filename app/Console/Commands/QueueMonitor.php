<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor {--queue=default : Queue cần kiểm tra} {--hours=24 : Kiểm tra hoạt động trong X giờ qua}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra trạng thái các queue và hiển thị thông tin thống kê';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queue = $this->option('queue');
        $hours = (int) $this->option('hours');
        
        $this->info("=== Queue Monitor - Kiểm tra queue '{$queue}' trong {$hours} giờ qua ===");
        
        // 1. Kiểm tra số lượng jobs đang chờ xử lý
        $this->checkPendingJobs($queue);
        
        // 2. Kiểm tra các jobs đang xử lý (reserved)
        $this->checkReservedJobs($queue);
        
        // 3. Kiểm tra các failed jobs
        $this->checkFailedJobs($hours);
        
        // 4. Kiểm tra các job batches
        $this->checkJobBatches($hours);
        
        // 5. Kiểm tra các import logs
        $this->checkImportLogs($hours);
        
        return Command::SUCCESS;
    }
    
    /**
     * Kiểm tra số lượng jobs đang chờ xử lý
     */
    protected function checkPendingJobs(string $queue): void
    {
        $this->info("\n📊 JOBS ĐANG CHỜ XỬ LÝ:");
        
        $pendingJobs = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNull('reserved_at')
            ->count();
            
        $this->info("- Số lượng jobs đang chờ: {$pendingJobs}");
        
        if ($pendingJobs > 0) {
            // Chi tiết về loại jobs đang chờ (dựa trên payload)
            $jobTypes = DB::table('jobs')
                ->where('queue', $queue)
                ->whereNull('reserved_at')
                ->select(DB::raw('JSON_EXTRACT(payload, "$.displayName") as job_type, count(*) as count'))
                ->groupBy('job_type')
                ->get();
                
            $this->info("- Chi tiết loại jobs đang chờ:");
            foreach ($jobTypes as $jobType) {
                $name = str_replace('"', '', $jobType->job_type);
                $this->info("  + {$name}: {$jobType->count}");
            }
        }
    }
    
    /**
     * Kiểm tra các jobs đang được xử lý (reserved)
     */
    protected function checkReservedJobs(string $queue): void
    {
        $this->info("\n⚙️ JOBS ĐANG XỬ LÝ:");
        
        $reservedJobs = DB::table('jobs')
            ->where('queue', $queue)
            ->whereNotNull('reserved_at')
            ->get();
            
        if ($reservedJobs->count() > 0) {
            $this->info("- Số lượng jobs đang xử lý: {$reservedJobs->count()}");
            
            $this->info("- Chi tiết các jobs đang xử lý:");
            $table = [];
            
            foreach ($reservedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? 'Unknown Job';
                $commandName = $payload['data']['commandName'] ?? 'N/A';
                $reservedAt = Carbon::createFromTimestamp($job->reserved_at)->diffForHumans();
                $attempts = $job->attempts;
                
                $table[] = [
                    'ID' => $job->id,
                    'Job Type' => $jobName,
                    'Command' => $commandName,
                    'Reserved' => $reservedAt,
                    'Attempts' => $attempts
                ];
            }
            
            $this->table(
                ['ID', 'Job Type', 'Command', 'Reserved', 'Attempts'],
                $table
            );
        } else {
            $this->info("- Không có jobs nào đang xử lý.");
        }
    }
    
    /**
     * Kiểm tra các failed jobs
     */
    protected function checkFailedJobs(int $hours): void
    {
        $this->info("\n❌ FAILED JOBS:");
        
        $cutoffTime = Carbon::now()->subHours($hours);
        
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $cutoffTime)
            ->orderBy('failed_at', 'desc')
            ->get();
            
        if ($failedJobs->count() > 0) {
            $this->info("- Số lượng failed jobs trong {$hours} giờ qua: {$failedJobs->count()}");
            
            $table = [];
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobName = $payload['displayName'] ?? 'Unknown Job';
                $failedAt = Carbon::parse($job->failed_at)->format('Y-m-d H:i:s');
                
                // Lấy phần đầu của exception để hiển thị
                $exception = substr($job->exception, 0, 100) . (strlen($job->exception) > 100 ? '...' : '');
                
                $table[] = [
                    'UUID' => substr($job->uuid, 0, 8) . '...',
                    'Job Type' => $jobName,
                    'Failed At' => $failedAt,
                    'Exception' => $exception
                ];
            }
            
            $this->table(
                ['UUID', 'Job Type', 'Failed At', 'Exception'],
                $table
            );
        } else {
            $this->info("- Không có failed jobs trong {$hours} giờ qua.");
        }
    }
    
    /**
     * Kiểm tra các job batches
     */
    protected function checkJobBatches(int $hours): void
    {
        $this->info("\n📦 JOB BATCHES:");
        
        // Chỉ kiểm tra nếu bảng job_batches tồn tại
        if (!Schema::hasTable('job_batches')) {
            $this->warn("- Bảng job_batches không tồn tại.");
            return;
        }
        
        $cutoffTime = Carbon::now()->subHours($hours)->getTimestamp();
        
        $batches = DB::table('job_batches')
            ->where('created_at', '>=', $cutoffTime)
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($batches->count() > 0) {
            $this->info("- Số lượng job batches trong {$hours} giờ qua: {$batches->count()}");
            
            $table = [];
            foreach ($batches as $batch) {
                $createdAt = Carbon::createFromTimestamp($batch->created_at)->format('Y-m-d H:i:s');
                $finishedAt = $batch->finished_at ? Carbon::createFromTimestamp($batch->finished_at)->format('Y-m-d H:i:s') : 'N/A';
                $status = $batch->finished_at ? 'Completed' : ($batch->cancelled_at ? 'Cancelled' : 'In Progress');
                
                $progress = $batch->total_jobs > 0 
                    ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100, 2) . '%'
                    : '0%';
                
                $table[] = [
                    'ID' => substr($batch->id, 0, 8) . '...',
                    'Name' => $batch->name,
                    'Status' => $status,
                    'Progress' => $progress,
                    'Total/Pending/Failed' => "{$batch->total_jobs}/{$batch->pending_jobs}/{$batch->failed_jobs}",
                    'Created' => $createdAt,
                    'Finished' => $finishedAt
                ];
            }
            
            $this->table(
                ['ID', 'Name', 'Status', 'Progress', 'Total/Pending/Failed', 'Created', 'Finished'],
                $table
            );
        } else {
            $this->info("- Không có job batches trong {$hours} giờ qua.");
        }
    }
    
    /**
     * Kiểm tra các import logs
     */
    protected function checkImportLogs(int $hours): void
    {
        $this->info("\n📝 IMPORT LOGS:");
        
        $cutoffTime = Carbon::now()->subHours($hours);
        
        $importLogs = DB::table('import_logs')
            ->where('created_at', '>=', $cutoffTime)
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($importLogs->count() > 0) {
            $this->info("- Số lượng import logs trong {$hours} giờ qua: {$importLogs->count()}");
            
            $table = [];
            foreach ($importLogs as $log) {
                $data = json_decode($log->data, true);
                $createdAt = Carbon::parse($log->created_at)->format('Y-m-d H:i:s');
                
                // Lấy các thông số chính
                $processed = $data['total_processed'] ?? $data['processed'] ?? 0;
                $success = $data['total_success'] ?? $data['success'] ?? 0;
                $failed = $data['total_failed'] ?? $data['failed'] ?? 0;
                
                $table[] = [
                    'ID' => substr($log->id, 0, 8) . '...',
                    'Type' => $log->type,
                    'Processed' => $processed,
                    'Success' => $success,
                    'Failed' => $failed,
                    'Created' => $createdAt
                ];
            }
            
            $this->table(
                ['ID', 'Type', 'Processed', 'Success', 'Failed', 'Created'],
                $table
            );
        } else {
            $this->info("- Không có import logs trong {$hours} giờ qua.");
        }
    }
}
