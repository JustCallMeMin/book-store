<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:clean-expired 
                            {--days=7 : Number of days to keep carts}
                            {--batch=100 : Number of carts to process per batch}
                            {--sleep=1 : Sleep time in seconds between batches}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired carts from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $batchSize = $this->option('batch');
        $sleep = $this->option('sleep');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Cleaning up carts that have expired or not been active for {$days} days");
        
        // Đếm số lượng giỏ hàng hết hạn
        $expiredCarts = Cart::where(function ($query) use ($days) {
            $query->where('expires_at', '<', now())
                ->orWhere('last_activity', '<', now()->subDays($days))
                ->orWhere('created_at', '<', now()->subDays($days * 2));
        })->count();
        
        if ($expiredCarts === 0) {
            $this->info('No expired carts found.');
            return 0;
        }
        
        $this->info("Found {$expiredCarts} expired carts to clean up");
        
        // Yêu cầu xác nhận trong môi trường production
        if (app()->environment('production') && !$force) {
            if (!$this->confirm('Are you sure you want to delete expired carts in production?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }
        
        // Hiển thị progress bar
        $bar = $this->output->createProgressBar($expiredCarts);
        $bar->start();
        
        $totalDeleted = 0;
        $errorCount = 0;
        
        // Xử lý theo batch
        Cart::where(function ($query) use ($days) {
            $query->where('expires_at', '<', now())
                ->orWhere('last_activity', '<', now()->subDays($days))
                ->orWhere('created_at', '<', now()->subDays($days * 2));
        })->chunkById($batchSize, function ($carts) use (&$totalDeleted, &$errorCount, $bar, $sleep, $dryRun) {
            // Start transaction
            DB::beginTransaction();
            
            try {
                foreach ($carts as $cart) {
                    if (!$dryRun) {
                        // Xóa cart_items trước
                        CartItem::where('cart_id', $cart->id)->delete();
                        
                        // Xóa cart
                        $cart->delete();
                    }
                    
                    $totalDeleted++;
                    $bar->advance();
                }
                
                // Commit transaction
                if (!$dryRun) {
                    DB::commit();
                }
                
                // Sleep giữa các batch để giảm tải server
                if ($sleep > 0) {
                    sleep($sleep);
                }
            } catch (\Exception $e) {
                // Rollback transaction nếu có lỗi
                if (!$dryRun) {
                    DB::rollBack();
                }
                
                $errorCount++;
                Log::error('Error during cart cleanup', [
                    'error' => $e->getMessage(),
                    'batch_size' => count($carts),
                    'cart_ids' => $carts->pluck('id')->toArray()
                ]);
            }
        });
        
        $bar->finish();
        $this->newLine(2);
        
        if ($dryRun) {
            $this->info("Dry run completed. Would have deleted {$totalDeleted} carts.");
        } else {
            $this->info("Deleted {$totalDeleted} expired carts with {$errorCount} errors.");
        }
        
        if ($errorCount > 0) {
            $this->warn('Some errors occurred during cleanup. Check logs for details.');
            return 1;
        }
        
        return 0;
    }
} 