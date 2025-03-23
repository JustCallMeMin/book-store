<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class QueueWorkLongTimeout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work-long {--timeout=3600 : Thời gian tối đa (giây) mà một job có thể chạy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy queue worker với timeout dài hơn cho các tác vụ import dữ liệu';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        $this->info('Starting queue worker with extended timeout (' . $timeout . ' seconds)');
        
        // Chạy queue worker với timeout dài hơn
        Artisan::call('queue:work', [
            '--timeout' => $timeout,
            '--tries' => 3,
            '--memory' => 1024,
            '--sleep' => 3,
            '--queue' => 'default,imports',
        ]);
    }
} 