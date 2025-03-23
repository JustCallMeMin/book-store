<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Services\GutendexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResyncBookAuthors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:resync-authors {--limit=} {--book-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-sync all books with the Gutendex API to ensure authors and categories are properly imported';

    /**
     * Execute the console command.
     */
    public function handle(GutendexService $gutendexService)
    {
        $limit = $this->option('limit');
        $bookId = $this->option('book-id');
        
        $query = Book::query();
        
        // Nếu có book-id cụ thể
        if ($bookId) {
            $query->where('id', $bookId)->orWhere('gutendex_id', $bookId);
        }
        
        // Lấy danh sách sách cần cập nhật
        $books = $limit ? $query->take($limit)->get() : $query->get();
        
        if ($books->isEmpty()) {
            $this->error('Không tìm thấy sách nào để cập nhật.');
            return 1;
        }
        
        $this->info("Bắt đầu cập nhật thông tin tác giả và danh mục cho {$books->count()} sách...");
        
        $progressBar = $this->output->createProgressBar($books->count());
        $progressBar->start();
        
        $stats = [
            'total' => $books->count(),
            'success' => 0,
            'skipped' => 0,
            'failed' => 0
        ];
        
        foreach ($books as $book) {
            if (!$book->gutendex_id) {
                $this->warn(" - Bỏ qua sách '{$book->title}' vì không có gutendex_id.");
                $stats['skipped']++;
                $progressBar->advance();
                continue;
            }
            
            try {
                // Cập nhật sách từ Gutendex
                $result = $gutendexService->updateBook($book->gutendex_id);
                
                if ($result['status'] === 200) {
                    $stats['success']++;
                } else {
                    $this->warn(" - Không thể cập nhật sách '{$book->title}': " . ($result['error'] ?? 'Unknown error'));
                    $stats['failed']++;
                }
            } catch (\Exception $e) {
                $this->error(" - Lỗi khi cập nhật sách '{$book->title}': {$e->getMessage()}");
                Log::error("Error resyncing book {$book->id}: {$e->getMessage()}", [
                    'exception' => $e
                ]);
                $stats['failed']++;
            }
            
            $progressBar->advance();
            
            // Tạm dừng giữa các yêu cầu để tránh overload API
            if ($stats['success'] % 5 === 0) {
                sleep(1);
            }
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("Hoàn thành cập nhật!");
        $this->table(
            ['Tổng số', 'Thành công', 'Bỏ qua', 'Thất bại'],
            [[$stats['total'], $stats['success'], $stats['skipped'], $stats['failed']]]
        );
        
        return 0;
    }
} 