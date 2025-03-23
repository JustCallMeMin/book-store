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
    public function getBooks(?string $search = null, int $page = 1)
    {
        $response = Http::withOptions([
            'verify' => false,
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

        return [
            'status' => $response->status(),
            'error' => 'Failed to fetch books from Gutendex'
        ];
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

            // Xác định giá bán và thông báo liên hệ
            $price = null;
            $priceNote = null;

            if (!empty($additionalInfo['price'])) {
                $price = $additionalInfo['price'];
                $priceNote = null;
            } else if ($additionalInfo['contact_for_price'] ?? true) {
                // Nếu cần liên hệ để biết giá
                $price = 0; // Giá 0 để biểu thị cần liên hệ
                
                // Tạo thông báo liên hệ
                $contactInfo = [];
                if (!empty($additionalInfo['publisher']) && $additionalInfo['publisher'] !== 'Unknown Publisher') {
                    $contactInfo[] = "publisher ({$additionalInfo['publisher']})";
                }
                
                // Tìm tác giả đầu tiên nếu có
                $authorName = isset($bookDetails['authors'][0]) ? $bookDetails['authors'][0]['name'] : null;
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

            // Lưu thông tin tác giả
            foreach ($bookDetails['authors'] as $authorData) {
                $author = Author::firstOrCreate(
                    ['gutendex_id' => $authorData['id']],
                    [
                        'name' => $authorData['name'],
                        'birth_year' => $authorData['birth_year'],
                        'death_year' => $authorData['death_year']
                    ]
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
            'price' => random_int(50, 200) * 1000,
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
        $response = Http::get("{$this->baseUrl}/books", [
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
    public function getBooksByAuthor(int $authorId)
    {
        $response = Http::get("{$this->baseUrl}/books", [
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
            
            // Xác định giá bán và thông báo liên hệ nếu đã thay đổi
            $price = $book->price;
            $priceNote = $book->price_note;
            
            if (!empty($additionalInfo['price']) && $additionalInfo['price'] != $book->price) {
                $price = $additionalInfo['price'];
                $priceNote = null;
            } else if ($book->price == 0 && ($additionalInfo['contact_for_price'] ?? true)) {
                // Cập nhật thông báo liên hệ nếu cần
                $contactInfo = [];
                if (!empty($additionalInfo['publisher']) && $additionalInfo['publisher'] !== 'Unknown Publisher') {
                    $contactInfo[] = "publisher ({$additionalInfo['publisher']})";
                }
                
                // Tìm tác giả đầu tiên nếu có
                $authorName = isset($bookDetails['authors'][0]) ? $bookDetails['authors'][0]['name'] : null;
                if (!empty($authorName)) {
                    $contactInfo[] = "author ($authorName)";
                }
                
                if (!empty($contactInfo)) {
                    $priceNote = "Please contact " . implode(" or ", $contactInfo) . " for pricing information.";
                }
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
} 