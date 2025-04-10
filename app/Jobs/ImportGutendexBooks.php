<?php

namespace App\Jobs;

use App\Services\GutendexService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\ImportLog;

class ImportGutendexBooks implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Thời gian timeout cho job (trong giây)
     * Mặc định: 3600 giây (1 giờ)
     */
    public $timeout = 3600;

    /**
     * Số lần thử lại nếu job thất bại
     */
    public $tries = 10;

    /**
     * Trang bắt đầu import
     */
    protected int $startPage;

    /**
     * Số trang tối đa sẽ import
     */
    protected int $maxPages;

    /**
     * Số sách sẽ import trong mỗi batch
     */
    protected int $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(int $startPage, int $maxPages, int $batchSize)
    {
        $this->startPage = $startPage;
        $this->maxPages = $maxPages;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     */
    public function handle(GutendexService $gutendexService): void
    {
        // Cấu hình môi trường
        ini_set('memory_limit', '512M');
        
        Log::info('Starting to import books from Gutendex', [
            'start_page' => $this->startPage, 
            'max_pages' => $this->maxPages,
            'batch_size' => $this->batchSize,
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'attempt' => $this->attempts(),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
        ]);

        $this->saveJobLog('started', 'Starting import job');

        $results = [
            'total_processed' => 0,
            'total_success' => 0,
            'total_failed' => 0,
            'pages_processed' => 0,
            'new_books' => 0,
            'updated_books' => 0,
            'skipped_books' => 0
        ];

        try {
            // Import sách từ nhiều trang
            $hasMorePages = true;
            $currentPage = $this->startPage;
            $maxPagesToProcess = $this->maxPages;
            $unlimitedPages = ($maxPagesToProcess <= 0);
            
            while ($hasMorePages && ($unlimitedPages || $currentPage < $this->startPage + $maxPagesToProcess)) {
                // Kiểm tra xem batch có bị hủy không
                if ($this->batch() && $this->batch()->cancelled()) {
                    Log::warning('Book import job was cancelled', ['page' => $currentPage]);
                    $this->saveJobLog('cancelled', 'Import job was cancelled');
                    break;
                }

                // Lấy danh sách sách từ Gutendex API
                Log::info('Fetching books from Gutendex API', [
                    'page' => $currentPage,
                    'attempt' => $this->attempts()
                ]);
                
                $booksResponse = $gutendexService->getBooks(null, $currentPage, 60);
                
                if ($booksResponse['status'] !== 200 || empty($booksResponse['data']['results'])) {
                    Log::info('No more books to import or API error', [
                        'status' => $booksResponse['status'] ?? 'unknown',
                        'error' => $booksResponse['error'] ?? 'No books in results',
                        'page' => $currentPage
                    ]);
                    
                    $this->saveJobLog('api_error', 'Error fetching books from API', [
                        'status' => $booksResponse['status'] ?? 'unknown',
                        'error' => $booksResponse['error'] ?? 'No books in results',
                        'page' => $currentPage
                    ]);
                    
                    $hasMorePages = false;
                    break;
                }
                
                $books = $booksResponse['data']['results'];
                Log::info('Retrieved books from API', [
                    'page' => $currentPage,
                    'count' => count($books)
                ]);
                
                $results['pages_processed']++;
                
                // Kiểm tra xem có còn trang tiếp theo không
                $hasMorePages = isset($booksResponse['data']['next']) && !empty($booksResponse['data']['next']);
                
                // Xử lý sách theo batch
                $chunks = array_chunk($books, min($this->batchSize, 5));
                
                foreach ($chunks as $index => $bookBatch) {
                    Log::info('Processing batch ' . ($index + 1) . ' of ' . count($chunks) . ' on page ' . $currentPage);
                    
                    foreach ($bookBatch as $book) {
                        $results['total_processed']++;
                        
                        try {
                            // Kiểm tra sách đã tồn tại chưa
                            $existingBook = \App\Models\Book::where('gutendex_id', $book['id'])->first();
                            
                            if (!$existingBook) {
                                // Import sách mới
                                $importResult = $gutendexService->saveBook($book['id']);
                                
                                if ($importResult['status'] === 200) {
                                    $results['total_success']++;
                                    $results['new_books']++;
                                    Log::info('Successfully imported new book', [
                                        'id' => $book['id'],
                                        'title' => $book['title']
                                    ]);
                                } else {
                                    $results['total_failed']++;
                                    Log::warning('Failed to import book', [
                                        'id' => $book['id'],
                                        'title' => $book['title'],
                                        'error' => $importResult['error'] ?? 'Unknown error'
                                    ]);
                                }
                            } else {
                                // Sách đã tồn tại, kiểm tra xem có cần cập nhật không
                                $needsUpdate = true;
                                
                                if ($existingBook->updated_at) {
                                    // Bỏ qua nếu mới cập nhật trong 7 ngày
                                    $daysSinceUpdate = now()->diffInDays($existingBook->updated_at);
                                    if ($daysSinceUpdate < 7) {
                                        $needsUpdate = false;
                                        $results['skipped_books']++;
                                        Log::info('Skipping recent book', [
                                            'id' => $book['id'],
                                            'title' => $book['title']
                                        ]);
                                    }
                                }
                                
                                if ($needsUpdate) {
                                    // Cập nhật sách
                                    $results['updated_books']++;
                                    $results['total_success']++;
                                    Log::info('Updated existing book', [
                                        'id' => $book['id'],
                                        'title' => $book['title']
                                    ]);
                                }
                            }
                        } catch (\Exception $e) {
                            $results['total_failed']++;
                            Log::error('Exception while importing book', [
                                'id' => $book['id'],
                                'title' => $book['title'] ?? 'unknown',
                                'exception' => $e->getMessage()
                            ]);
                        }

                        // Tạm dừng giữa các request để tránh quá tải API
                        if ($results['total_processed'] % 5 === 0) {
                            sleep(1);
                        }
                    }
                    
                    // Định kỳ lưu kết quả
                    if ($index % 2 === 0 || $index === count($chunks) - 1) {
                        $this->saveJobLog('in_progress', 'Import in progress', [
                            'page' => $currentPage,
                            'processed' => $results['total_processed'],
                            'success' => $results['total_success'],
                            'failed' => $results['total_failed']
                        ]);
                    }
                    
                    // Tạm dừng giữa các batch để tránh quá tải hệ thống
                    sleep(2);
                    
                    // Giải phóng bộ nhớ
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
                
                // Chuyển đến trang tiếp theo
                $currentPage++;
                
                $this->saveJobLog('page_completed', 'Completed processing page ' . ($currentPage - 1), [
                    'page' => $currentPage - 1,
                    'next_page' => $currentPage
                ]);
            }

            // Lưu kết quả import sau khi hoàn thành
            $this->saveImportResult($results);

            Log::info('Book import job completed', [
                'total_processed' => $results['total_processed'],
                'total_success' => $results['total_success'],
                'total_failed' => $results['total_failed'],
                'pages_processed' => $results['pages_processed'],
                'new_books' => $results['new_books'],
                'updated_books' => $results['updated_books'],
                'skipped_books' => $results['skipped_books']
            ]);
            
            $this->saveJobLog('completed', 'Import job completed successfully', $results);
            
        } catch (\Exception $e) {
            Log::error('Critical error in book import job', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->saveJobLog('error', 'Critical error in import job: ' . $e->getMessage());
            
            // Ném lại exception để job có thể thử lại
            throw $e;
        }
    }
    
    /**
     * Lưu thông tin log của job
     */
    private function saveJobLog(string $status, string $message, array $data = []): void
    {
        try {
            $logData = array_merge([
                'job_id' => $this->job->getJobId() ?? 'unknown',
                'attempt' => $this->attempts(),
                'start_page' => $this->startPage,
                'max_pages' => $this->maxPages,
                'batch_size' => $this->batchSize
            ], $data);
            
            ImportLog::create([
                'type' => 'gutendex_import_job',
                'status' => $status,
                'message' => $message,
                'metadata' => $logData
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save job log', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Lưu kết quả import vào database
     */
    private function saveImportResult(array $results): void
    {
        try {
            ImportLog::create([
                'type' => 'gutendex_import',
                'status' => 'completed',
                'message' => 'Import job completed',
                'metadata' => array_merge($results, [
                    'job_id' => $this->job->getJobId() ?? 'unknown',
                    'attempt' => $this->attempts(),
                    'date' => now()->toDateTimeString()
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save import results', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xử lý job thất bại
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Book import job failed after all retry attempts', [
            'job_id' => $this->job->getJobId() ?? 'unknown',
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'parameters' => [
                'start_page' => $this->startPage,
                'max_pages' => $this->maxPages,
                'batch_size' => $this->batchSize
            ],
            'attempts' => $this->attempts()
        ]);

        try {
            // Ghi log vào database
            ImportLog::create([
                'type' => 'gutendex_import',
                'status' => 'failed',
                'message' => 'Job failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
                'metadata' => [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'parameters' => [
                        'start_page' => $this->startPage,
                        'max_pages' => $this->maxPages,
                        'batch_size' => $this->batchSize
                    ],
                    'job_id' => $this->job->getJobId() ?? 'unknown',
                    'attempts' => $this->attempts(),
                    'date' => now()->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save import log', [
                'error' => $e->getMessage(),
                'original_exception' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new \Illuminate\Queue\Middleware\WithoutOverlapping('import_gutendex_books')];
    }

    /**
     * Mô tả thời gian chờ giữa các lần retry
     */
    public function backoff(): array
    {
        return [60, 120, 300, 600, 1200, 1800, 3600, 7200, 14400, 21600]; // 1m, 2m, 5m, 10m, 20m, 30m, 1h, 2h, 4h, 6h
    }
} 