<?php

namespace App\Console\Commands;

use App\Jobs\ImportGutendexBooks;
use Illuminate\Console\Command;
use App\Models\ImportLog;

class TestImportBooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:test-import {--batch=2} {--pages=1} {--start=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test import books from Gutendex with small parameters';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = $this->option('batch');
        $maxPages = $this->option('pages');
        $startPage = $this->option('start');
        
        $this->info('Starting test import with small parameters:');
        $this->line("- Batch size: $batchSize");
        $this->line("- Max pages: $maxPages");
        $this->line("- Start page: $startPage");
        
        try {
            // Lưu log
            ImportLog::create([
                'type' => 'test_import_command',
                'status' => 'queued',
                'message' => 'Test import job queued from command line',
                'metadata' => [
                    'batch_size' => $batchSize,
                    'max_pages' => $maxPages,
                    'start_page' => $startPage
                ]
            ]);
            
            // Dispatch job
            ImportGutendexBooks::dispatch(
                (int) $startPage,
                (int) $maxPages,
                (int) $batchSize
            );
            
            $this->info('Test import job has been queued successfully.');
            $this->line('Run "php artisan queue:work" to process the job.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to queue test import job: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 