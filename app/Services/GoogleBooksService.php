<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class GoogleBooksService
{
    protected string $baseUrl = 'https://www.googleapis.com/books/v1';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_books.api_key', '');
    }

    /**
     * Tìm kiếm sách theo tiêu đề và tác giả
     */
    public function searchBook(string $title, ?string $author = null)
    {
        // Nếu không có API key, trả về lỗi
        if (empty($this->apiKey)) {
            return [
                'status' => 400,
                'error' => 'Google Books API key not configured'
            ];
        }

        try {
            $query = 'intitle:' . urlencode($title);
            
            if ($author) {
                $query .= '+inauthor:' . urlencode($author);
            }

            $response = Http::withOptions([
                'verify' => false,
            ])->get("{$this->baseUrl}/volumes", [
                'q' => $query,
                'maxResults' => 1,
                'key' => $this->apiKey
            ]);

            if ($response->successful() && isset($response['items'][0])) {
                return [
                    'status' => 200,
                    'data' => $response['items'][0]
                ];
            }

            return [
                'status' => 404,
                'error' => 'Book not found in Google Books'
            ];
        } catch (\Exception $e) {
            Log::error('Google Books API error: ' . $e->getMessage());
            return [
                'status' => 500,
                'error' => 'Error contacting Google Books API'
            ];
        }
    }

    /**
     * Tìm kiếm sách theo ISBN
     */
    public function searchByISBN(string $isbn)
    {
        // Nếu không có API key, trả về lỗi
        if (empty($this->apiKey)) {
            return [
                'status' => 400,
                'error' => 'Google Books API key not configured'
            ];
        }
        
        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->get("{$this->baseUrl}/volumes", [
                'q' => 'isbn:' . $isbn,
                'key' => $this->apiKey
            ]);

            if ($response->successful() && isset($response['items'][0])) {
                return [
                    'status' => 200,
                    'data' => $response['items'][0]
                ];
            }

            return [
                'status' => 404,
                'error' => 'ISBN not found in Google Books'
            ];
        } catch (\Exception $e) {
            Log::error('Google Books API error: ' . $e->getMessage());
            return [
                'status' => 500,
                'error' => 'Error contacting Google Books API'
            ];
        }
    }

    /**
     * Trích xuất thông tin giá và nhà xuất bản từ kết quả Google Books
     */
    public function extractBookInfo(array $googleBookData)
    {
        $volumeInfo = $googleBookData['volumeInfo'] ?? [];
        $saleInfo = $googleBookData['saleInfo'] ?? [];
        $accessInfo = $googleBookData['accessInfo'] ?? [];
        
        $publisher = $volumeInfo['publisher'] ?? null;
        $publishedDate = $volumeInfo['publishedDate'] ?? null;
        $description = $volumeInfo['description'] ?? null;
        $pageCount = $volumeInfo['pageCount'] ?? null;
        $categories = $volumeInfo['categories'] ?? [];
        $imageLinks = $volumeInfo['imageLinks'] ?? [];
        $isbn = null;
        
        // Lấy ISBN nếu có
        if (isset($volumeInfo['industryIdentifiers'])) {
            foreach ($volumeInfo['industryIdentifiers'] as $identifier) {
                if (in_array($identifier['type'], ['ISBN_10', 'ISBN_13'])) {
                    $isbn = $identifier['identifier'];
                    break;
                }
            }
        }
        
        // Lấy thông tin giá
        $price = null;
        $currencyCode = null;
        $contactForPrice = true;
        $isFree = false;
        
        // Kiểm tra sách miễn phí
        if (isset($saleInfo['saleability']) && $saleInfo['saleability'] === 'FREE') {
            $isFree = true;
            $contactForPrice = false;
        } elseif (isset($saleInfo['listPrice'])) {
            $price = $saleInfo['listPrice']['amount'] ?? null;
            $currencyCode = $saleInfo['listPrice']['currencyCode'] ?? null;
            $contactForPrice = false;
        } elseif (isset($saleInfo['retailPrice'])) {
            $price = $saleInfo['retailPrice']['amount'] ?? null;
            $currencyCode = $saleInfo['retailPrice']['currencyCode'] ?? null;
            $contactForPrice = false;
        }
        
        // Nếu giá trả về bằng USD hoặc EUR, chuyển đổi sang VND
        if ($price && $currencyCode) {
            if ($currencyCode === 'USD') {
                $price = $price * 24000; // Tỷ giá USD/VND
            } elseif ($currencyCode === 'EUR') {
                $price = $price * 26000; // Tỷ giá EUR/VND
            }
        }

        // Tạo giá ngẫu nhiên, bất kể trạng thái miễn phí
        if ($pageCount) {
            // Giá tăng theo số trang, từ 50k-250k
            $price = min(250000, max(50000, $pageCount * 500));
            
            // Thêm một chút ngẫu nhiên vào giá, ± 10%
            $variation = rand(-10, 10) / 100;
            $price = round($price * (1 + $variation), -3); // Làm tròn đến hàng nghìn
        } else {
            // Nếu không có số trang, tạo giá ngẫu nhiên
            $price = random_int(50, 200) * 1000;
        }
        
        // Thông tin tải và đọc
        $downloadLinks = [
            'pdf' => $accessInfo['pdf']['downloadLink'] ?? null,
            'epub' => $accessInfo['epub']['downloadLink'] ?? null,
        ];
        
        $webReaderLink = $accessInfo['webReaderLink'] ?? null;
        $accessViewStatus = $accessInfo['accessViewStatus'] ?? null;
        
        return [
            'isbn' => $isbn,
            'publisher' => $publisher ?: 'Unknown Publisher',
            'published_date' => $publishedDate,
            'description' => $description,
            'page_count' => $pageCount,
            'cover_image' => $imageLinks['thumbnail'] ?? null,
            'price' => $price,
            'categories' => $categories,
            'contact_for_price' => $contactForPrice,
            'is_free' => $isFree,
            'saleability' => $saleInfo['saleability'] ?? null,
            'download_links' => $downloadLinks,
            'web_reader_link' => $webReaderLink,
            'access_status' => $accessViewStatus
        ];
    }

    /**
     * Tìm kiếm nhiều sách từ Google Books API
     */
    public function search(?string $query = null, int $page = 1, int $perPage = 10)
    {
        // Nếu không có API key, trả về lỗi
        if (empty($this->apiKey)) {
            return [
                'status' => 400,
                'error' => 'Google Books API key not configured'
            ];
        }

        try {
            // Tính toán startIndex cho phân trang
            $startIndex = ($page - 1) * $perPage;
            
            $params = [
                'startIndex' => $startIndex,
                'maxResults' => $perPage,
                'key' => $this->apiKey
            ];
            
            // Thêm query nếu có
            if ($query) {
                $params['q'] = $query;
            } else {
                // Nếu không có query, search sách phổ biến
                $params['q'] = 'subject:fiction';
            }

            $response = Http::get("{$this->baseUrl}/volumes", $params);

            if ($response->successful()) {
                return [
                    'status' => 200,
                    'data' => $response->json()
                ];
            }

            return [
                'status' => $response->status(),
                'error' => 'Failed to search books from Google Books'
            ];
        } catch (\Exception $e) {
            Log::error('Google Books API error: ' . $e->getMessage());
            return [
                'status' => 500,
                'error' => 'Error contacting Google Books API'
            ];
        }
    }

    /**
     * Lấy chi tiết một cuốn sách từ Google Books API
     */
    public function getBook(string $volumeId)
    {
        // Nếu không có API key, trả về lỗi
        if (empty($this->apiKey)) {
            return [
                'status' => 400,
                'error' => 'Google Books API key not configured'
            ];
        }

        try {
            $response = Http::get("{$this->baseUrl}/volumes/{$volumeId}", [
                'key' => $this->apiKey
            ]);

            if ($response->successful()) {
                return [
                    'status' => 200,
                    'data' => $response->json()
                ];
            }

            return [
                'status' => $response->status(),
                'error' => 'Book not found in Google Books'
            ];
        } catch (\Exception $e) {
            Log::error('Google Books API error: ' . $e->getMessage());
            return [
                'status' => 500,
                'error' => 'Error contacting Google Books API'
            ];
        }
    }

    /**
     * Nhập sách từ Google Books vào database
     */
    public function importBook(string $volumeId)
    {
        $bookData = $this->getBook($volumeId);
        
        if (isset($bookData['error'])) {
            return $bookData;
        }

        try {
            DB::beginTransaction();

            // Lấy dữ liệu từ response
            $bookDetails = $bookData['data'];
            $volumeInfo = $bookDetails['volumeInfo'] ?? [];

            // Kiểm tra xem sách đã tồn tại chưa
            $existingBook = Book::where('google_books_id', $volumeId)->first();
            if ($existingBook) {
                return [
                    'status' => 409,
                    'error' => 'Book already exists in database',
                    'data' => $existingBook->load(['authors', 'categories'])
                ];
            }

            // Trích xuất thông tin
            $bookInfo = $this->extractBookInfo($bookDetails);

            // Xác định giá bán và thông báo liên hệ
            $price = null;
            $priceNote = null;

            if (!empty($bookInfo['price'])) {
                $price = $bookInfo['price'];
                $priceNote = null;
            } else if ($bookInfo['contact_for_price']) {
                // Nếu cần liên hệ để biết giá
                $price = 0; // Giá 0 để biểu thị cần liên hệ
                
                // Tạo thông báo liên hệ
                $contactInfo = [];
                if (!empty($bookInfo['publisher']) && $bookInfo['publisher'] !== 'Unknown Publisher') {
                    $contactInfo[] = "publisher ({$bookInfo['publisher']})";
                }
                
                // Tìm tác giả đầu tiên nếu có
                $authorName = $volumeInfo['authors'][0] ?? null;
                if (!empty($authorName)) {
                    $contactInfo[] = "author ($authorName)";
                }
                
                if (!empty($contactInfo)) {
                    $priceNote = "Please contact " . implode(" or ", $contactInfo) . " for pricing information.";
                } else {
                    $priceNote = "Please contact the publisher for pricing information.";
                }
            } else {
                // Nếu không có thông tin giá và không yêu cầu liên hệ
                $price = 0;
                $priceNote = "Price information not available.";
            }

            // Tạo mô tả nếu không có
            $description = $bookInfo['description'];
            if (empty($description) && isset($volumeInfo['categories'])) {
                $description = "This book covers the following categories: " . implode(", ", $volumeInfo['categories']);
            }

            // Lưu thông tin sách
            $book = Book::create([
                'google_books_id' => $volumeId,
                'title' => $volumeInfo['title'] ?? 'Unknown Title',
                'languages' => isset($volumeInfo['language']) ? [$volumeInfo['language']] : ['en'],
                'download_count' => 0,
                'media_type' => 'text',
                // Các trường từ Google Books API
                'isbn' => $bookInfo['isbn'],
                'publisher' => $bookInfo['publisher'],
                'published_date' => $bookInfo['published_date'] ? date('Y-m-d', strtotime($bookInfo['published_date'])) : null,
                'description' => $description,
                'page_count' => $bookInfo['page_count'] ?? random_int(100, 500),
                'cover_image' => $bookInfo['cover_image'],
                'quantity_in_stock' => random_int(10, 100),
                'price' => $price,
                'price_note' => $priceNote,
                'discount_percent' => 0,
                'is_featured' => false,
                'is_active' => true
            ]);

            // Lưu thông tin tác giả
            if (isset($volumeInfo['authors'])) {
                foreach ($volumeInfo['authors'] as $authorName) {
                    $author = Author::firstOrCreate(
                        ['name' => $authorName],
                        [
                            'birth_year' => null,
                            'death_year' => null
                        ]
                    );

                    $book->authors()->attach($author->id);
                }
            }

            // Lưu thông tin category
            if (isset($volumeInfo['categories'])) {
                foreach ($volumeInfo['categories'] as $categoryName) {
                    $category = Category::firstOrCreate(['name' => $categoryName]);
                    $book->categories()->attach($category->id);
                }
            }

            DB::commit();

            return [
                'status' => 200,
                'message' => 'Book imported successfully',
                'data' => $book->load(['authors', 'categories'])
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'error' => 'Failed to import book: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import nhiều sách từ Google Books vào database
     */
    public function bulkImportBooks(array $volumeIds)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($volumeIds as $volumeId) {
            $result = $this->importBook($volumeId);
            
            if ($result['status'] === 200) {
                $results['success'][] = [
                    'id' => $volumeId,
                    'title' => $result['data']->title
                ];
            } else {
                $results['failed'][] = [
                    'id' => $volumeId,
                    'reason' => $result['error'] ?? 'Unknown error'
                ];
            }
        }

        return [
            'status' => 200,
            'message' => 'Bulk import completed',
            'data' => $results
        ];
    }

    /**
     * Fallback khi không có Google Books API key
     */
    public function hasFallback()
    {
        return !empty($this->apiKey);
    }
} 