<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\GutendexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportBooksWithAuthors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:import-with-authors 
                            {--page=1 : Trang bắt đầu import}
                            {--limit=10 : Số sách tối đa sẽ import}
                            {--batch=5 : Số sách xử lý mỗi lần}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import sách từ Gutendex API với đầy đủ thông tin tác giả';

    /**
     * Execute the console command.
     */
    public function handle(GutendexService $gutendexService)
    {
        $startPage = (int)$this->option('page');
        $limit = (int)$this->option('limit');
        $batchSize = (int)$this->option('batch');
        
        $this->info("Bắt đầu import sách từ trang {$startPage} với giới hạn {$limit} sách...");
        
        $stats = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'pages' => 0,
        ];
        
        try {
            $currentPage = $startPage;
            $hasMorePages = true;
            $booksProcessed = 0;
            
            // Tạo progress bar
            $progressBar = $this->output->createProgressBar($limit);
            $progressBar->start();
            
            while ($hasMorePages && $booksProcessed < $limit) {
                $this->info("\nĐang xử lý trang {$currentPage}...");
                
                // Lấy danh sách sách từ Gutendex API
                $booksResponse = $gutendexService->getBooks(null, $currentPage, 60);
                
                if ($booksResponse['status'] !== 200 || empty($booksResponse['data']['results'])) {
                    $this->error("Không tìm thấy sách trên trang {$currentPage} hoặc lỗi API.");
                    break;
                }
                
                $books = $booksResponse['data']['results'];
                $stats['pages']++;
                
                // Kiểm tra xem có còn trang tiếp theo không
                $hasMorePages = isset($booksResponse['data']['next']) && !empty($booksResponse['data']['next']);
                
                // Xử lý từng cuốn sách
                foreach ($books as $book) {
                    // Kiểm tra đã đạt giới hạn chưa
                    if ($booksProcessed >= $limit) {
                        break;
                    }
                    
                    $stats['processed']++;
                    $booksProcessed++;
                    
                    // Hiển thị tiến trình
                    $progressBar->advance();
                    
                    try {
                        // Kiểm tra xem sách đã tồn tại chưa
                        $existingBook = Book::where('gutendex_id', $book['id'])->first();
                        
                        if (!$existingBook) {
                            // Sách chưa tồn tại, import mới
                            $importResult = $gutendexService->saveBook($book['id']);
                            
                            if ($importResult['status'] === 200) {
                                $stats['success']++;
                                
                                // Hiển thị thông tin chi tiết nếu verbose
                                if ($this->getOutput()->isVerbose()) {
                                    $this->info("\nĐã import thành công: {$book['title']}");
                                }
                            } else {
                                $stats['failed']++;
                                $this->error("\nLỗi khi import: {$book['title']} - " . ($importResult['error'] ?? 'Lỗi không xác định'));
                            }
                        } else {
                            // Sách đã tồn tại, đánh dấu là bỏ qua
                            $stats['skipped']++;
                            
                            if ($this->getOutput()->isVerbose()) {
                                $this->line("\nĐã bỏ qua (đã tồn tại): {$book['title']}");
                            }
                        }
                    } catch (\Exception $e) {
                        $stats['failed']++;
                        $this->error("\nLỗi khi xử lý sách ID {$book['id']}: " . $e->getMessage());
                        Log::error("Lỗi khi import sách", [
                            'book_id' => $book['id'],
                            'title' => $book['title'] ?? 'Unknown',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                    
                    // Tạm dừng giữa các request để tránh quá tải API
                    if ($stats['processed'] % $batchSize === 0) {
                        sleep(1);
                    }
                }
                
                // Chuyển sang trang tiếp theo
                $currentPage++;
                
                // Tạm dừng giữa các trang để tránh quá tải
                sleep(2);
            }
            
            $progressBar->finish();
            
            // Hiển thị kết quả
            $this->newLine(2);
            $this->info("Hoàn thành import sách!");
            $this->table(
                ['Đã xử lý', 'Thành công', 'Thất bại', 'Bỏ qua', 'Số trang'],
                [[$stats['processed'], $stats['success'], $stats['failed'], $stats['skipped'], $stats['pages']]]
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Lỗi nghiêm trọng: " . $e->getMessage());
            Log::error("Lỗi nghiêm trọng khi import sách", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
