<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Đăng ký Command mới
        $this->commands([
            \App\Console\Commands\QueueWorkLongTimeout::class,
            \App\Console\Commands\CleanupQueues::class,
            \App\Console\Commands\QueueMonitor::class,
        ]);

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Chạy queue worker với timeout dài hơn
        $schedule->command('queue:work-long --timeout=3600')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();
            
        // Dọn dẹp queue mỗi giờ để tránh tích tụ tài nguyên
        $schedule->command('queue:cleanup --days=7 --timeout=120')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();
            
        // Restart queue workers hàng ngày để tránh rò rỉ bộ nhớ
        $schedule->command('queue:restart')
            ->daily()
            ->onOneServer();

        // Dọn dẹp giỏ hàng hết hạn - chạy lúc 3 giờ sáng
        $schedule->command('carts:clean-expired --days=7 --batch=500 --sleep=2')
            ->dailyAt('03:00')
            ->appendOutputTo(storage_path('logs/cart-cleanup.log'))
            ->onOneServer();
    }
} 