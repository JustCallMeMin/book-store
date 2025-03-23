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
    public $tries = 5;

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
        Log::info('Starting to import books from Gutendex', [
            'start_page' => $this->startPage, 
            'max_pages' => $this->maxPages,
            'batch_size' => $this->batchSize,
            'job_id' => $this->job->getJobId() ?? 'unknown'
        ]);

        $results = [
            'success' => [],
            'failed' => [],
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
                    break;
                }

                // Lấy danh sách sách từ Gutendex API với timeout dài hơn
                Log::info('Fetching books from Gutendex API', [
                    'page' => $currentPage,
                    'attempt' => $this->attempts()
                ]);
                
                $booksResponse = $gutendexService->getBooks(null, $currentPage, 60);
                
                if ($booksResponse['status'] !== 200 || empty($booksResponse['data']['results'])) {
                    // Nếu không còn sách để import thì dừng
                    Log::info('No more books to import or API error', [
                        'status' => $booksResponse['status'] ?? 'unknown',
                        'error' => $booksResponse['error'] ?? 'No books in results',
                        'page' => $currentPage,
                        'response' => json_encode(array_slice($booksResponse, 0, 200))
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
                
                Log::info('Processing page ' . $currentPage . ' with ' . count($books) . ' books', [
                    'has_more_pages' => $hasMorePages
                ]);
                
                // Xử lý theo batch để tránh memory overflow
                $chunks = array_chunk($books, $this->batchSize);
                
                foreach ($chunks as $index => $bookBatch) {
                    Log::info('Processing batch ' . ($index + 1) . ' of ' . count($chunks) . ' on page ' . $currentPage);
                    
                    foreach ($bookBatch as $book) {
                        $results['total_processed']++;
                        
                        try {
                            // Trước khi import, kiểm tra xem sách đã tồn tại chưa
                            $existingBook = \App\Models\Book::where('gutendex_id', $book['id'])->first();
                            
                            if (!$existingBook) {
                                // Sách chưa tồn tại, import mới
                                Log::info('Attempting to import new book', [
                                    'id' => $book['id'],
                                    'title' => $book['title'],
                                    'authors' => json_encode(array_column($book['authors'] ?? [], 'name'))
                                ]);
                                
                                try {
                                    $importResult = $gutendexService->saveBook($book['id']);
                                    
                                    if ($importResult['status'] === 200) {
                                        // Ensure the book has authors after import
                                        $importedBook = \App\Models\Book::where('gutendex_id', $book['id'])->first();
                                        
                                        // Check if the book has authors, if not, add default author
                                        if ($importedBook && $importedBook->authors()->count() === 0) {
                                            $defaultAuthor = \App\Models\Author::firstOrCreate(
                                                ['name' => 'Unknown Author'],
                                                ['gutendex_id' => '0']
                                            );
                                            $importedBook->authors()->attach($defaultAuthor->id);
                                            Log::info('Added default author to book with missing authors', [
                                                'book_id' => $book['id'],
                                                'title' => $book['title']
                                            ]);
                                        }
                                        
                                        $results['success'][] = [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'action' => 'imported'
                                        ];
                                        $results['total_success']++;
                                        $results['new_books']++;
                                        Log::info('Successfully imported new book', [
                                            'id' => $book['id'],
                                            'title' => $book['title']
                                        ]);
                                    } else {
                                        $results['failed'][] = [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'reason' => $importResult['error'] ?? 'Failed to import book'
                                        ];
                                        $results['total_failed']++;
                                        Log::warning('Failed to import book', [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'error' => $importResult['error'] ?? 'Unknown error',
                                            'response' => json_encode(array_slice($importResult, 0, 200)) // Log truncated response
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    $results['failed'][] = [
                                        'id' => $book['id'],
                                        'title' => $book['title'],
                                        'reason' => 'Exception: ' . $e->getMessage()
                                    ];
                                    $results['total_failed']++;
                                    Log::error('Exception while importing book', [
                                        'id' => $book['id'],
                                        'title' => $book['title'],
                                        'exception' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                            } else {
                                // Sách đã tồn tại, kiểm tra xem có cần cập nhật không
                                $needsUpdate = true;
                                
                                // Nếu sách có thời gian cập nhật, so sánh với thời gian hiện tại
                                if ($existingBook->updated_at) {
                                    // Kiểm tra xem thời gian cập nhật có cách đây hơn 7 ngày không
                                    $daysSinceUpdate = now()->diffInDays($existingBook->updated_at);
                                    if ($daysSinceUpdate < 7) {
                                        // Nếu mới cập nhật trong vòng 7 ngày, bỏ qua
                                        $needsUpdate = false;
                                        $results['skipped_books']++;
                                        Log::info('Skipped updating recent book', [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'days_since_update' => $daysSinceUpdate
                                        ]);
                                    }
                                }
                                
                                if ($needsUpdate) {
                                    // Cập nhật sách
                                    $updateResult = $gutendexService->updateBook($book['id']);
                                    
                                    if ($updateResult['status'] === 200) {
                                        // Ensure the book has authors after update
                                        $updatedBook = \App\Models\Book::where('gutendex_id', $book['id'])->first();
                                        
                                        // Check if the book has authors, if not, add default author
                                        if ($updatedBook && $updatedBook->authors()->count() === 0) {
                                            $defaultAuthor = \App\Models\Author::firstOrCreate(
                                                ['name' => 'Unknown Author'],
                                                ['gutendex_id' => '0']
                                            );
                                            $updatedBook->authors()->attach($defaultAuthor->id);
                                            Log::info('Added default author to book with missing authors during update', [
                                                'book_id' => $book['id'],
                                                'title' => $book['title']
                                            ]);
                                        }
                                        
                                        $results['success'][] = [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'action' => 'updated'
                                        ];
                                        $results['total_success']++;
                                        $results['updated_books']++;
                                        Log::info('Successfully updated existing book', [
                                            'id' => $book['id'],
                                            'title' => $book['title']
                                        ]);
                                    } else {
                                        $results['failed'][] = [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'reason' => $updateResult['error'] ?? 'Failed to update book'
                                        ];
                                        $results['total_failed']++;
                                        Log::warning('Failed to update book', [
                                            'id' => $book['id'],
                                            'title' => $book['title'],
                                            'error' => $updateResult['error'] ?? 'Unknown error'
                                        ]);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $results['failed'][] = [
                                'id' => $book['id'],
                                'title' => $book['title'],
                                'reason' => 'Exception: ' . $e->getMessage()
                            ];
                            $results['total_failed']++;
                            Log::error('Exception while importing book', [
                                'id' => $book['id'],
                                'title' => $book['title'],
                                'exception' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }

                        // Tạm dừng giữa các request để tránh quá tải API
                        if ($results['total_processed'] % 5 === 0) {
                            sleep(1);
                        }
                    }
                    
                    // Tạm dừng giữa các batch để tránh quá tải hệ thống
                    sleep(2);
                }
                
                // Chuyển đến trang tiếp theo
                $currentPage++;
            }

            // Lưu kết quả import vào database để tham khảo sau này
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
        } catch (\Exception $e) {
            Log::error('Critical error in book import job', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ném lại exception để job có thể thử lại
            throw $e;
        }
    }
    
    /**
     * Lưu kết quả import vào database
     */
    private function saveImportResult(array $results): void
    {
        try {
            \App\Models\ImportLog::create([
                'type' => 'gutendex_import',
                'user_id' => null,
                'data' => [
                    'total_processed' => $results['total_processed'],
                    'total_success' => $results['total_success'],
                    'total_failed' => $results['total_failed'],
                    'pages_processed' => $results['pages_processed'],
                    'new_books' => $results['new_books'] ?? 0,
                    'updated_books' => $results['updated_books'] ?? 0,
                    'skipped_books' => $results['skipped_books'] ?? 0,
                    'date' => now()->toDateTimeString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save import results', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xử lý khi job thất bại sau khi đã hết số lần retry
     * Đảm bảo dequeue job khỏi hàng đợi khi không thể xử lý thành công
     */
    public function failed(\Throwable $exception): void
    {
        // Ghi log về việc job thất bại hoàn toàn
        Log::error('Gutendex import job failed permanently after all retries', [
            'start_page' => $this->startPage, 
            'max_pages' => $this->maxPages,
            'batch_size' => $this->batchSize,
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'job_id' => $this->job ? $this->job->getJobId() : 'unknown',
            'attempts' => $this->attempts()
        ]);
        
        // Lưu thông tin về job thất bại vào database
        try {
            \App\Models\ImportLog::create([
                'type' => 'gutendex_import_failed',
                'user_id' => null,
                'data' => [
                    'start_page' => $this->startPage,
                    'max_pages' => $this->maxPages,
                    'batch_size' => $this->batchSize,
                    'error' => $exception->getMessage(),
                    'attempts' => $this->attempts(),
                    'date' => now()->toDateTimeString(),
                    'status' => 'failed_permanently'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save failed job log', [
                'exception' => $e->getMessage(),
                'original_error' => $exception->getMessage()
            ]);
        }
    }
    
    /**
     * Xác định cách xử lý khi job bị lỗi
     * Giải phóng tài nguyên đã đặt trước (reserved) nếu job thất bại
     */
    public function middleware(): array
    {
        // Sử dụng cả start_page, max_pages và batch_size để tạo key duy nhất
        $lockKey = "gutendex_import_{$this->startPage}_{$this->maxPages}_{$this->batchSize}";
        return [new \Illuminate\Queue\Middleware\WithoutOverlapping($lockKey)];
    }
    
    /**
     * Xác định thời gian chờ trước khi retry job nếu thất bại
     */
    public function backoff(): array
    {
        // Retry sau 30s, 60s, và 120s
        return [30, 60, 120];
    }
} 