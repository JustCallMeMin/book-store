<?php

/**
 * Script lấy thông tin sách từ cả Gutendex và Google Books API
 * 
 * Sử dụng: php scripts/api/combined_book_info.php <book_id>
 * - book_id: ID của sách trên Gutendex
 * 
 * Ví dụ:
 * - php scripts/api/combined_book_info.php 84   # Lấy thông tin của sách có ID 84
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Bootstrap Laravel để sử dụng các service
require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Bỏ qua xác minh SSL certificate cho HTTP client
\Illuminate\Support\Facades\Http::withOptions([
    'verify' => false,
]);

// Đảm bảo rằng tham số ID sách được cung cấp
if (!isset($argv[1])) {
    echo "Thiếu tham số. Sử dụng: php " . $argv[0] . " <book_id>\n";
    exit(1);
}

$bookId = intval($argv[1]);

// Thư mục lưu output
$outputDir = __DIR__ . '/output';

// Đảm bảo thư mục output tồn tại
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Hàm format JSON để in ra màn hình
function prettyPrint($data) {
    if (is_array($data) || is_object($data)) {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    return $data;
}

try {
    echo "====== THÔNG TIN SÁCH TÍCH HỢP (GUTENDEX + GOOGLE BOOKS) ======\n\n";
    
    // Lấy các service từ Laravel container
    $gutendexService = app(\App\Services\GutendexService::class);
    $googleBooksService = app(\App\Services\GoogleBooksService::class);
    
    // 1. Lấy thông tin sách từ Gutendex API
    echo "Đang lấy thông tin từ Gutendex API...\n";
    $gutendexResult = $gutendexService->getBook($bookId);
    
    // Kiểm tra xem dữ liệu Gutendex có hợp lệ không
    if (isset($gutendexResult['error'])) {
        throw new Exception("Không tìm thấy sách với ID $bookId trên Gutendex: " . $gutendexResult['error']);
    }
    
    $gutendexData = $gutendexResult['data'];
    
    // Kiểm tra xem dữ liệu Gutendex có hợp lệ không
    if (!isset($gutendexData['title'])) {
        throw new Exception("Dữ liệu Gutendex không hợp lệ: Thiếu thông tin tiêu đề sách");
    }
    
    $title = $gutendexData['title'];
    $authorName = isset($gutendexData['authors'][0]) ? $gutendexData['authors'][0]['name'] : null;
    
    // Lưu dữ liệu Gutendex
    $gutendexOutput = "$outputDir/gutendex_book_{$bookId}.json";
    file_put_contents($gutendexOutput, prettyPrint($gutendexData));
    echo "Đã lưu dữ liệu Gutendex vào file $gutendexOutput\n\n";

    // 2. Tìm kiếm trên Google Books API
    echo "Đang tìm kiếm trên Google Books API...\n";
    
    // Kiểm tra xem Google Books API key có được cấu hình không
    $apiKey = config('services.google_books.api_key');
    $envFile = base_path('.env');
    echo "Đang tìm file .env tại: $envFile\n";
    
    if (file_exists($envFile)) {
        echo "File .env tồn tại.\n";
        if (!empty($apiKey)) {
            echo "Đã tìm thấy API key: " . substr($apiKey, 0, 3) . "...[ẩn]\n";
            
            // Tìm kiếm sách trên Google Books bằng tiêu đề và tác giả
            $googleBookResult = $googleBooksService->searchBook($title, $authorName);
            
            // Kiểm tra kết quả từ Google Books
            if ($googleBookResult['status'] === 200) {
                $googleData = $googleBookResult['data'];
                
                // Lưu dữ liệu Google Books
                $googleOutput = "$outputDir/google_books_{$bookId}.json";
                file_put_contents($googleOutput, prettyPrint($googleData));
                echo "Đã lưu dữ liệu Google Books vào file $googleOutput\n\n";
            } else {
                echo "Không tìm thấy kết quả phù hợp từ Google Books API: " . ($googleBookResult['error'] ?? 'Unknown error') . "\n\n";
                $googleData = null;
            }
        } else {
            echo "Không tìm thấy GOOGLE_BOOKS_API_KEY được cấu hình\n";
            $googleData = null;
        }
    } else {
        echo "CẢNH BÁO: Không tìm thấy file .env tại đường dẫn: $envFile\n";
        $googleData = null;
    }
    
    // 3. Kết hợp thông tin từ cả hai nguồn
    echo "====== THÔNG TIN SÁCH ĐẦY ĐỦ ======\n";
    echo "ID Gutendex: " . $gutendexData['id'] . "\n";
    echo "Tiêu đề: " . $gutendexData['title'] . "\n";
    echo "Tác giả: " . implode(', ', array_column($gutendexData['authors'] ?? [], 'name')) . "\n";
    
    // Thông tin từ Gutendex
    echo "\n>> THÔNG TIN TỪ GUTENDEX <<\n";
    echo "Subjects: " . implode(', ', $gutendexData['subjects'] ?? []) . "\n";
    echo "Bookshelves: " . implode(', ', $gutendexData['bookshelves'] ?? []) . "\n";
    echo "Languages: " . implode(', ', $gutendexData['languages'] ?? []) . "\n";
    echo "Download count: " . ($gutendexData['download_count'] ?? 'N/A') . "\n";
    echo "Copyright: " . ($gutendexData['copyright'] ? 'Yes' : 'No') . "\n";
    echo "Media type: " . ($gutendexData['media_type'] ?? 'N/A') . "\n";
    
    // Link tải sách (nếu có)
    if (isset($gutendexData['formats'])) {
        echo "\nFormats từ Gutendex:\n";
        foreach ($gutendexData['formats'] as $format => $url) {
            echo "- " . $format . ": " . $url . "\n";
        }
    }
    
    // Thông tin từ Google Books (nếu có)
    if (isset($googleData)) {
        $volumeInfo = $googleData['volumeInfo'] ?? [];
        $saleInfo = $googleData['saleInfo'] ?? [];
        
        echo "\n>> THÔNG TIN TỪ GOOGLE BOOKS <<\n";
        echo "ID Google Books: " . ($googleData['id'] ?? 'N/A') . "\n";
        
        // Trích xuất thông tin đầy đủ từ GoogleBooksService
        $bookInfo = $googleBooksService->extractBookInfo($googleData);
        
        // ISBN
        if (!empty($bookInfo['isbn'])) {
            // Hiển thị ISBN (có thể là ISBN_10 hoặc ISBN_13)
            if (strlen($bookInfo['isbn']) === 10) {
                echo "ISBN_10: " . $bookInfo['isbn'] . "\n";
            } else {
                echo "ISBN_13: " . $bookInfo['isbn'] . "\n";
            }
            
            // Hiển thị các identifiers khác nếu có
            if (isset($volumeInfo['industryIdentifiers'])) {
                foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                    if ($identifier['identifier'] !== $bookInfo['isbn']) {
                        echo $identifier['type'] . ": " . $identifier['identifier'] . "\n";
                    }
                }
            }
        }
        
        // Thông tin xuất bản
        echo "Publisher: " . $bookInfo['publisher'] . "\n";
        echo "Published date: " . ($bookInfo['published_date'] ?? 'N/A') . "\n";
        echo "Page count: " . ($bookInfo['page_count'] ?? 'N/A') . "\n";
        
        // Categories/Genres
        if (!empty($bookInfo['categories'])) {
            echo "Categories: " . implode(', ', $bookInfo['categories']) . "\n";
        }
        
        // Descriptions
        if (!empty($bookInfo['description'])) {
            echo "\nDescription:\n" . substr($bookInfo['description'], 0, 500) . (strlen($bookInfo['description']) > 500 ? '...' : '') . "\n";
        }
        
        // Thông tin giá (từ extractBookInfo)
        echo "\nPrice: ";
        if (isset($bookInfo['price'])) {
            echo number_format($bookInfo['price'], 0, ',', '.') . " VND";
            
            // Hiển thị ghi chú nếu sách là miễn phí
            if ($bookInfo['is_free'] ?? false) {
                echo " (Free on Google Books)";
                
                // Hiển thị link tải PDF nếu có
                if (!empty($bookInfo['download_links']['pdf'])) {
                    echo "\nPDF Download: " . $bookInfo['download_links']['pdf'];
                }
                
                // Hiển thị link tải EPUB nếu có
                if (!empty($bookInfo['download_links']['epub'])) {
                    echo "\nEPUB Download: " . $bookInfo['download_links']['epub'];
                }
                
                // Hiển thị link đọc web nếu có
                if (!empty($bookInfo['web_reader_link'])) {
                    echo "\nWeb Reader: " . $bookInfo['web_reader_link'];
                }
            }
        } else {
            // Fallback khi không có thông tin giá
            $randomPrice = random_int(50, 200) * 1000;
            echo number_format($randomPrice, 0, ',', '.') . " VND (Giá tạm tính)";
        }
        echo "\n";
        
        // Cover image
        if (!empty($bookInfo['cover_image'])) {
            echo "\nCover images:\n";
            
            if (isset($volumeInfo['imageLinks'])) {
                foreach ($volumeInfo['imageLinks'] as $type => $url) {
                    echo "- $type: $url\n";
                }
            } else {
                echo "- thumbnail: " . $bookInfo['cover_image'] . "\n";
            }
        }
    } else {
        echo "\n>> THÔNG TIN GOOGLE BOOKS KHÔNG KHẢ DỤNG <<\n";
        echo "Gợi ý: Đảm bảo rằng bạn đã cấu hình GOOGLE_BOOKS_API_KEY trong file .env\n";
    }
    
    // Lưu kết quả tổng hợp
    $combinedData = [
        'gutendex' => $gutendexData,
        'google_books' => $googleData ?? null,
        'extracted_info' => isset($googleData) ? $googleBooksService->extractBookInfo($googleData) : null
    ];
    
    $combinedOutput = "$outputDir/combined_book_{$bookId}.json";
    file_put_contents($combinedOutput, prettyPrint($combinedData));
    echo "\nĐã lưu dữ liệu tổng hợp vào file $combinedOutput\n";
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
} 