<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class GutendexService
{
    protected string $baseUrl = 'https://gutendex.com';
    protected GoogleBooksService $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    /**
     * Lấy danh sách sách từ Gutendex API
     */
    public function getBooks(?string $search = null, int $page = 1, int $timeout = 30)
    {
        try {
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification
                'timeout' => $timeout, // Timeout tuỳ chỉnh
                'connect_timeout' => $timeout, // Timeout kết nối
            ])->get("{$this->baseUrl}/books", [
                'search' => $search,
                'page' => $page
            ]);

            if ($response->successful()) {
                return [
                    'status' => 200,
                    'data' => $response->json()
                ];
            }

            // Log chi tiết lỗi từ API
            \Illuminate\Support\Facades\Log::error('Gutendex API error', [
                'status_code' => $response->status(),
                'url' => "{$this->baseUrl}/books",
                'params' => ['search' => $search, 'page' => $page],
                'response_body' => $response->body()
            ]);

            return [
                'status' => $response->status(),
                'error' => 'Failed to fetch books from Gutendex: ' . $response->body()
            ];
        } catch (\Exception $e) {
            // Log chi tiết exception
            \Illuminate\Support\Facades\Log::error('Exception in Gutendex API call', [
                'url' => "{$this->baseUrl}/books",
                'params' => ['search' => $search, 'page' => $page],
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 500,
                'error' => 'Exception during Gutendex API call: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy chi tiết một cuốn sách từ Gutendex API
     */
    public function getBook(int $id)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$this->baseUrl}/books/{$id}");

        if ($response->successful()) {
            return [
                'status' => 200,
                'data' => $response->json()
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Book not found'
        ];
    }

    /**
     * Lưu sách từ Gutendex vào database local
     */
    public function saveBook(int $bookId)
    {
        $bookData = $this->getBook($bookId);
        
        if (isset($bookData['error'])) {
            return $bookData;
        }

        try {
            DB::beginTransaction();

            // Lấy dữ liệu từ response
            $bookDetails = $bookData['data'];

            // Kiểm tra xem sách đã tồn tại chưa
            $existingBook = Book::where('gutendex_id', $bookId)->first();
            if ($existingBook) {
                return [
                    'status' => 409,
                    'error' => 'Book already exists in database',
                    'data' => $existingBook->load(['authors', 'categories'])
                ];
            }

            // Tìm thông tin bổ sung từ Google Books API
            $additionalInfo = $this->getAdditionalBookInfo($bookDetails);

            // Tạo mô tả từ subjects nếu không có mô tả từ Google Books
            $description = $additionalInfo['description'];
            if (empty($description) && !empty($bookDetails['subjects'])) {
                $description = "This book covers the following subjects: " . implode(", ", array_slice($bookDetails['subjects'], 0, 5));
                if (count($bookDetails['subjects']) > 5) {
                    $description .= ", and more.";
                }
            }

            // Lấy cover image từ formats nếu không có từ Google Books
            $coverImage = $additionalInfo['cover_image'];
            if (empty($coverImage) && isset($bookDetails['formats']['image/jpeg'])) {
                $coverImage = $bookDetails['formats']['image/jpeg'];
            }

            // Đảm bảo luôn có giá sách - 10% sách miễn phí
            $makeBookFree = mt_rand(0, 9) === 0; // 10% chance for free books
            $price = !empty($additionalInfo['price']) ? $additionalInfo['price'] : ($makeBookFree ? 0 : random_int(50, 200) * 1000);
            $priceNote = null;
            
            // Nếu sách miễn phí (giá 0), thêm ghi chú
            if ($price == 0) {
                $priceNote = 'Sách miễn phí';
            }

            // Lưu thông tin sách với các trường mới
            $book = Book::create([
                'gutendex_id' => $bookId,
                'title' => $bookDetails['title'],
                'subjects' => $bookDetails['subjects'] ?? [],
                'bookshelves' => $bookDetails['bookshelves'] ?? [],
                'languages' => $bookDetails['languages'] ?? [],
                'summaries' => $bookDetails['summaries'] ?? [],
                'translators' => $bookDetails['translators'] ?? [],
                'copyright' => $bookDetails['copyright'] ?? false,
                'media_type' => $bookDetails['media_type'] ?? 'text',
                'formats' => $bookDetails['formats'] ?? [],
                'download_count' => $bookDetails['download_count'] ?? 0,
                // Các trường từ Google Books API
                'isbn' => $additionalInfo['isbn'],
                'publisher' => $additionalInfo['publisher'],
                'published_date' => $additionalInfo['published_date'] ? date('Y-m-d', strtotime($additionalInfo['published_date'])) : null,
                'description' => $description,
                'page_count' => $additionalInfo['page_count'] ?? random_int(100, 500),
                'cover_image' => $coverImage,
                'quantity_in_stock' => random_int(10, 100),
                'price' => $price,
                'price_note' => $priceNote,
                'discount_percent' => 0,
                'is_featured' => false,
                'is_active' => true
            ]);

            // Lưu thông tin tác giả - Đã được sửa để đảm bảo tác giả luôn được tạo
            if (!empty($bookDetails['authors'])) {
                foreach ($bookDetails['authors'] as $authorData) {
                    // Tạo một unique ID nếu không có sẵn
                    $authorId = $authorData['id'] ?? md5($authorData['name']);
                    
                    // Tạo hoặc cập nhật tác giả
                    $author = Author::firstOrCreate(
                        ['gutendex_id' => $authorId],
                        [
                            'name' => $authorData['name'],
                            'birth_year' => $authorData['birth_year'] ?? null,
                            'death_year' => $authorData['death_year'] ?? null
                        ]
                    );
                    
                    // Đảm bảo liên kết đến sách
                    $book->authors()->attach($author->id);
                }
            } else {
                // Nếu không có thông tin tác giả, tạo tác giả "Unknown"
                $author = Author::firstOrCreate(
                    ['name' => 'Unknown Author'],
                    ['gutendex_id' => 0]
                );
                $book->authors()->attach($author->id);
            }

            // Chuyển đổi subjects thành categories
            if (!empty($bookDetails['subjects'])) {
                foreach ($bookDetails['subjects'] as $subjectName) {
                    // Tạo category nếu chưa tồn tại
                    $category = Category::firstOrCreate(['name' => $subjectName]);
                    
                    // Liên kết sách với category
                    $book->categories()->attach($category->id);
                }
            }

            // Thêm categories từ Google Books nếu có
            if (!empty($additionalInfo['categories'])) {
                foreach ($additionalInfo['categories'] as $categoryName) {
                    $category = Category::firstOrCreate(['name' => $categoryName]);
                    
                    // Kiểm tra xem đã liên kết chưa để tránh trùng lặp
                    if (!$book->categories()->where('categories.id', $category->id)->exists()) {
                        $book->categories()->attach($category->id);
                    }
                }
            }

            // Chuyển đổi bookshelves thành categories nếu không có subjects
            if (empty($bookDetails['subjects']) && !empty($bookDetails['bookshelves'])) {
                foreach ($bookDetails['bookshelves'] as $bookshelfName) {
                    // Tạo category từ bookshelf
                    $category = Category::firstOrCreate(['name' => $bookshelfName]);
                    
                    // Liên kết sách với category
                    $book->categories()->attach($category->id);
                }
            }

            // Nếu sau tất cả, sách vẫn chưa có category nào, thêm category "Uncategorized"
            if ($book->categories()->count() === 0) {
                $category = Category::firstOrCreate(['name' => 'Uncategorized']);
                $book->categories()->attach($category->id);
            }

            DB::commit();

            return [
                'status' => 200,
                'message' => 'Book saved successfully',
                'data' => $book->load(['authors', 'categories'])
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'error' => 'Failed to save book: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy thông tin bổ sung từ Google Books API
     */
    protected function getAdditionalBookInfo(array $gutendexBook)
    {
        // Mặc định thông tin
        $defaultInfo = [
            'isbn' => null,
            'publisher' => 'Project Gutenberg',
            'published_date' => null,
            'description' => '',
            'page_count' => null,
            'cover_image' => null,
            'price' => mt_rand(0, 10) === 0 ? 0 : random_int(50, 200) * 1000, // 10% chance to be free
            'contact_for_price' => false,
            'categories' => []
        ];

        try {
            // Tìm kiếm trên Google Books bằng tiêu đề và tác giả đầu tiên (nếu có)
            $title = $gutendexBook['title'];
            $author = isset($gutendexBook['authors'][0]) ? $gutendexBook['authors'][0]['name'] : null;
            
            $googleBookData = $this->googleBooksService->searchBook($title, $author);
            
            // Nếu không tìm thấy, trả về thông tin mặc định
            if ($googleBookData['status'] !== 200) {
                return $defaultInfo;
            }
            
            // Trích xuất thông tin từ Google Books
            $bookInfo = $this->googleBooksService->extractBookInfo($googleBookData['data']);
            
            return $bookInfo;
        } catch (\Exception $e) {
            // Nếu có lỗi, trả về thông tin mặc định
            return $defaultInfo;
        }
    }

    /**
     * Xóa sách khỏi database local
     */
    public function deleteBook(int $id)
    {
        try {
            $book = Book::where('gutendex_id', $id)->firstOrFail();
            $book->authors()->detach();
            $book->categories()->detach();
            $book->delete();

            return [
                'status' => 200,
                'message' => 'Book deleted successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 404,
                'error' => 'Book not found: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy danh sách tác giả từ Gutendex API
     */
    public function getAuthors(?string $search = null, int $page = 1)
    {
        // Gutendex không có API riêng cho tác giả, nên chúng ta lấy sách và trích xuất tác giả
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$this->baseUrl}/books", [
            'search' => $search,
            'page' => $page
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $uniqueAuthors = [];
            
            // Trích xuất tác giả từ sách
            foreach ($data['results'] as $book) {
                foreach ($book['authors'] as $author) {
                    $authorId = $author['id'] ?? md5($author['name']); // Fallback nếu không có ID
                    if (!isset($uniqueAuthors[$authorId])) {
                        $uniqueAuthors[$authorId] = $author;
                    }
                }
            }
            
            return [
                'status' => 200,
                'data' => array_values($uniqueAuthors)
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch authors from Gutendex'
        ];
    }

    /**
     * Lấy danh sách sách theo tác giả từ Gutendex API
     */
    public function getBooksByAuthor(mixed $authorId)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$this->baseUrl}/books", [
            'author_id' => $authorId
        ]);

        if ($response->successful()) {
            return [
                'status' => 200,
                'data' => $response->json()
            ];
        }

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch books by author'
        ];
    }

    /**
     * Cập nhật thông tin sách trong database từ Gutendex
     */
    public function updateBook(int $bookId)
    {
        $bookData = $this->getBook($bookId);
        
        if (isset($bookData['error'])) {
            return $bookData;
        }

        try {
            DB::beginTransaction();

            // Lấy dữ liệu từ response
            $bookDetails = $bookData['data'];

            // Tìm sách trong database
            $book = Book::where('gutendex_id', $bookId)->first();
            if (!$book) {
                return [
                    'status' => 404,
                    'error' => 'Book not found in database'
                ];
            }

            // Tìm thông tin bổ sung từ Google Books API
            $additionalInfo = $this->getAdditionalBookInfo($bookDetails);
            
            // Cập nhật giá nếu cần - đảm bảo luôn có giá
            $price = $book->price;
            if (!empty($additionalInfo['price'])) {
                $price = $additionalInfo['price'];
            } else if ($book->price == 0 || $book->price === null) {
                // Nếu giá là 0 hoặc null, có 10% khả năng để sách miễn phí
                $makeBookFree = mt_rand(0, 9) === 0; // 10% chance for free books
                $price = $makeBookFree ? 0 : random_int(50, 200) * 1000;
            }
            
            // Nếu sách miễn phí (giá 0), thêm ghi chú
            $priceNote = null;
            if ($price == 0) {
                $priceNote = 'Sách miễn phí';
            }

            // Cập nhật thông tin sách 
            $book->update([
                'title' => $bookDetails['title'],
                'subjects' => $bookDetails['subjects'] ?? [],
                'bookshelves' => $bookDetails['bookshelves'] ?? [],
                'languages' => $bookDetails['languages'] ?? [],
                'download_count' => $bookDetails['download_count'] ?? 0,
                // Cập nhật các trường từ Google Books nếu có thông tin mới
                'isbn' => $additionalInfo['isbn'] ?: $book->isbn,
                'publisher' => $additionalInfo['publisher'] ?: $book->publisher,
                'description' => $additionalInfo['description'] ?: $book->description,
                'page_count' => $additionalInfo['page_count'] ?: $book->page_count,
                'cover_image' => $additionalInfo['cover_image'] ?: $book->cover_image,
                'price' => $price,
                'price_note' => $priceNote,
            ]);

            // Cập nhật thông tin tác giả
            if (!empty($bookDetails['authors'])) {
                // Xóa các liên kết tác giả cũ
                $book->authors()->detach();
                
                foreach ($bookDetails['authors'] as $authorData) {
                    // Tạo một unique ID nếu không có sẵn
                    $authorId = $authorData['id'] ?? md5($authorData['name']);
                    
                    // Tạo hoặc cập nhật tác giả
                    $author = Author::firstOrCreate(
                        ['gutendex_id' => $authorId],
                        [
                            'name' => $authorData['name'],
                            'birth_year' => $authorData['birth_year'] ?? null,
                            'death_year' => $authorData['death_year'] ?? null
                        ]
                    );
                    
                    // Liên kết tác giả với sách
                    $book->authors()->attach($author->id);
                }
            } else if ($book->authors()->count() === 0) {
                // Nếu không có thông tin tác giả và sách chưa có tác giả nào, tạo tác giả "Unknown"
                $author = Author::firstOrCreate(
                    ['name' => 'Unknown Author'],
                    ['gutendex_id' => 0]
                );
                $book->authors()->attach($author->id);
            }
            
            // Cập nhật categories nếu cần
            if ($book->categories()->count() === 0) {
                // Nếu sách chưa có category nào, thêm từ subjects hoặc bookshelves
                if (!empty($bookDetails['subjects'])) {
                    foreach ($bookDetails['subjects'] as $subjectName) {
                        $category = Category::firstOrCreate(['name' => $subjectName]);
                        $book->categories()->attach($category->id);
                    }
                } else if (!empty($bookDetails['bookshelves'])) {
                    foreach ($bookDetails['bookshelves'] as $bookshelfName) {
                        $category = Category::firstOrCreate(['name' => $bookshelfName]);
                        $book->categories()->attach($category->id);
                    }
                } else {
                    // Nếu vẫn không có category nào, thêm "Uncategorized"
                    $category = Category::firstOrCreate(['name' => 'Uncategorized']);
                    $book->categories()->attach($category->id);
                }
            }

            DB::commit();

            return [
                'status' => 200,
                'message' => 'Book updated successfully',
                'data' => $book->fresh()->load(['authors', 'categories'])
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'error' => 'Failed to update book: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import nhiều sách cùng lúc từ Gutendex
     */
    public function bulkImportBooks(array $bookIds)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($bookIds as $bookId) {
            $result = $this->saveBook($bookId);
            
            if ($result['status'] === 200) {
                $results['success'][] = [
                    'id' => $bookId,
                    'title' => $result['data']->title
                ];
            } else {
                $results['failed'][] = [
                    'id' => $bookId,
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
     * Tìm kiếm sách trong Gutendex theo tiêu đề và tác giả
     * Hữu ích cho việc tìm sách tương đương giữa Google Books và Gutendex
     */
    public function findBookByTitleAndAuthor(string $title, ?string $author = null)
    {
        // Xây dựng query tìm kiếm
        $searchQuery = $title;
        if ($author) {
            $searchQuery .= ' ' . $author;
        }
        
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$this->baseUrl}/books", [
            'search' => $searchQuery
        ]);

        if (!$response->successful()) {
            return [
                'status' => $response->status(),
                'error' => 'Failed to search books from Gutendex'
            ];
        }
        
        $data = $response->json();
        
        // Nếu không có kết quả
        if (empty($data['results'])) {
            return [
                'status' => 404,
                'error' => 'No matching books found in Gutendex'
            ];
        }
        
        // Tìm kết quả phù hợp nhất dựa trên tiêu đề và tác giả
        $bestMatch = null;
        $highestScore = 0;
        
        foreach ($data['results'] as $book) {
            $score = $this->calculateMatchScore($book, $title, $author);
            
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $book;
            }
        }
        
        // Nếu điểm khớp quá thấp, có thể sách không thực sự khớp
        if ($highestScore < 0.5) {
            return [
                'status' => 404,
                'error' => 'No sufficiently matching books found in Gutendex'
            ];
        }
        
        return [
            'status' => 200,
            'data' => $bestMatch,
            'match_score' => $highestScore
        ];
    }
    
    /**
     * Tính điểm khớp giữa sách Gutendex và tiêu đề/tác giả đã cho
     */
    private function calculateMatchScore(array $gutendexBook, string $title, ?string $author = null)
    {
        $score = 0;
        
        // So sánh tiêu đề (chiếm 70% điểm)
        $titleSimilarity = $this->calculateStringSimilarity($gutendexBook['title'], $title);
        $score += $titleSimilarity * 0.7;
        
        // So sánh tác giả nếu có (chiếm 30% điểm)
        if ($author && !empty($gutendexBook['authors'])) {
            $authorSimilarity = 0;
            
            foreach ($gutendexBook['authors'] as $bookAuthor) {
                $currentSimilarity = $this->calculateStringSimilarity($bookAuthor['name'], $author);
                $authorSimilarity = max($authorSimilarity, $currentSimilarity);
            }
            
            $score += $authorSimilarity * 0.3;
        }
        
        return $score;
    }
    
    /**
     * Tính độ tương đồng giữa hai chuỗi (0-1)
     */
    private function calculateStringSimilarity(string $str1, string $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));
        
        if ($str1 === $str2) {
            return 1.0;
        }
        
        // Sử dụng Levenshtein distance để tính toán độ tương đồng
        $levenshtein = levenshtein($str1, $str2);
        $maxLength = max(strlen($str1), strlen($str2));
        
        // Chuyển đổi khoảng cách thành điểm tương đồng (1 - normalized_distance)
        return 1 - ($levenshtein / $maxLength);
    }
} 