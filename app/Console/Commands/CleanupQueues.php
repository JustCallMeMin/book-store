<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:cleanup {--days=1 : Số ngày để giữ lại các failed jobs} {--timeout=60 : Thời gian (phút) để xác định job bị treo} {--queue=default : Queue cần dọn dẹp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dọn dẹp các jobs bị treo và quá hạn để giải phóng tài nguyên';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $timeout = (int) $this->option('timeout');
        $queue = $this->option('queue');
        
        $this->info('Bắt đầu dọn dẹp queue...');
        
        // 1. Dọn dẹp các failed jobs cũ
        $this->cleanupOldFailedJobs($days);
        
        // 2. Dọn dẹp các jobs bị treo (stuck/ghost jobs)
        $this->cleanupStuckJobs($timeout, $queue);
        
        // 3. Đồng bộ lại số lượng jobs trong batch nếu cần
        $this->resyncJobBatches();
        
        $this->info('Hoàn tất dọn dẹp queue!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Dọn dẹp các failed jobs cũ hơn X ngày
     */
    protected function cleanupOldFailedJobs(int $days): void
    {
        $cutoffDate = now()->subDays($days);
        
        $this->info("Đang xóa các failed jobs cũ hơn {$days} ngày...");
        
        try {
            $count = DB::table('failed_jobs')
                ->where('failed_at', '<', $cutoffDate)
                ->delete();
                
            $this->info("Đã xóa {$count} failed jobs cũ.");
            
        } catch (\Exception $e) {
            $this->error("Lỗi khi xóa failed jobs: " . $e->getMessage());
            Log::error("Queue cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Dọn dẹp các jobs bị treo
     * - Jobs đã được reserved nhưng quá thời gian timeout
     * - Jobs đang chờ nhưng đã quá hạn
     */
    protected function cleanupStuckJobs(int $timeoutMinutes, string $queue): void
    {
        $this->info("Đang xóa các jobs bị treo (timeout > {$timeoutMinutes} phút)...");
        
        try {
            // Tìm các jobs bị reserved quá lâu (jobs bị treo)
            $cutoffTime = time() - ($timeoutMinutes * 60);
            
            $stuckJobs = DB::table('jobs')
                ->where('queue', $queue)
                ->where(function($query) use ($cutoffTime) {
                    // Jobs bị reserved quá lâu
                    $query->where('reserved_at', '<=', $cutoffTime)
                          ->whereNotNull('reserved_at');
                })
                ->get();
                
            if ($stuckJobs->count() > 0) {
                $this->warn("Tìm thấy {$stuckJobs->count()} jobs bị treo.");
                
                // Log thông tin về các jobs bị treo trước khi xóa
                foreach ($stuckJobs as $job) {
                    Log::warning("Removing stuck job", [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'attempts' => $job->attempts,
                        'reserved_at' => $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null,
                        'available_at' => date('Y-m-d H:i:s', $job->available_at),
                        'created_at' => date('Y-m-d H:i:s', $job->created_at)
                    ]);
                }
                
                // Xóa các jobs bị treo
                $deletedCount = DB::table('jobs')
                    ->where('queue', $queue)
                    ->where(function($query) use ($cutoffTime) {
                        $query->where('reserved_at', '<=', $cutoffTime)
                              ->whereNotNull('reserved_at');
                    })
                    ->delete();
                    
                $this->info("Đã xóa {$deletedCount} jobs bị treo.");
            } else {
                $this->info("Không tìm thấy jobs bị treo.");
            }
            
        } catch (\Exception $e) {
            $this->error("Lỗi khi xóa jobs bị treo: " . $e->getMessage());
            Log::error("Queue cleanup error: " . $e->getMessage());
        }
    }
    
    /**
     * Đồng bộ lại số lượng jobs trong các batches
     * (đôi khi số lượng jobs pending không khớp với thực tế)
     */
    protected function resyncJobBatches(): void
    {
        $this->info("Đang đồng bộ lại các job batches...");
        
        try {
            // Lấy tất cả các batches chưa hoàn thành
            $batches = DB::table('job_batches')
                ->whereNull('finished_at')
                ->get();
                
            if ($batches->count() > 0) {
                $this->info("Đang kiểm tra {$batches->count()} job batches...");
                
                foreach ($batches as $batch) {
                    // Đếm số lượng jobs thực tế trong batch
                    $actualPendingJobs = DB::table('jobs')
                        ->whereJsonContains('payload->batchId', $batch->id)
                        ->count();
                    
                    // Nếu không còn jobs nào nhưng batch vẫn đang pending
                    if ($actualPendingJobs == 0 && $batch->pending_jobs > 0) {
                        $this->warn("Batch {$batch->id} hiển thị {$batch->pending_jobs} pending jobs nhưng thực tế là 0. Đang cập nhật...");
                        
                        // Đánh dấu batch là đã hoàn thành
                        DB::table('job_batches')
                            ->where('id', $batch->id)
                            ->update([
                                'pending_jobs' => 0,
                                'finished_at' => now()->getTimestamp()
                            ]);
                    }
                    // Nếu số lượng jobs trong batch không khớp
                    else if ($actualPendingJobs != $batch->pending_jobs) {
                        $this->warn("Batch {$batch->id} hiển thị {$batch->pending_jobs} pending jobs nhưng thực tế là {$actualPendingJobs}. Đang cập nhật...");
                        
                        DB::table('job_batches')
                            ->where('id', $batch->id)
                            ->update([
                                'pending_jobs' => $actualPendingJobs
                            ]);
                    }
                }
            } else {
                $this->info("Không có job batches cần đồng bộ.");
            }
            
        } catch (\Exception $e) {
            $this->error("Lỗi khi đồng bộ job batches: " . $e->getMessage());
            Log::error("Queue cleanup error: " . $e->getMessage());
        }
    }
}
