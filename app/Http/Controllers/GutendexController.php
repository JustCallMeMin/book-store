<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\CategoryResource;
use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use App\Services\GutendexService;
use Illuminate\Http\Request;
use App\Jobs\ImportGutendexBooks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\RedisImportLogService;

class GutendexController extends Controller
{
    protected GutendexService $gutendexService;
    protected RedisImportLogService $importLogService;

    public function __construct(GutendexService $gutendexService, RedisImportLogService $importLogService)
    {
        $this->gutendexService = $gutendexService;
        $this->importLogService = $importLogService;
    }

    /**
     * Lấy danh sách sách với phân trang và tìm kiếm từ database
     */
    public function index(Request $request)
    {
        // Lấy tất cả các tham số filter
        $search = $request->query('search');
        $category = $request->query('category');
        $authorId = $request->query('author_id');
        $language = $request->query('language');
        $isFeatured = $request->query('is_featured');
        $isActive = $request->query('is_active');
        $priceMin = $request->query('price_min');
        $priceMax = $request->query('price_max');
        $publishedYearMin = $request->query('published_year_min');
        $publishedYearMax = $request->query('published_year_max');
        $sortBy = $request->query('sort_by', 'id');
        $sortDirection = $request->query('sort_direction', 'desc');
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        
        // Tạo cache key dựa trên tất cả query parameters
        $cacheKey = "books:list:search:{$search}:category:{$category}:author:{$authorId}:language:{$language}";
        $cacheKey .= ":featured:{$isFeatured}:active:{$isActive}:price:{$priceMin}-{$priceMax}";
        $cacheKey .= ":year:{$publishedYearMin}-{$publishedYearMax}:sort:{$sortBy}-{$sortDirection}";
        $cacheKey .= ":page:{$page}:perPage:{$perPage}";
        
        // Kiểm tra cache (10 phút)
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        // Logic tìm kiếm và query hiện tại
        $query = Book::with(['authors', 'categories']);
        
        // Filter theo search term
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('authors', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filter theo thể loại
        if ($category) {
            $query->whereHas('categories', function($q) use ($category) {
                $q->where('name', 'like', "%{$category}%")
                  ->orWhere('id', $category);
            });
        }
        
        // Filter theo tác giả
        if ($authorId) {
            $query->whereHas('authors', function($q) use ($authorId) {
                $q->where('id', $authorId);
            });
        }
        
        // Filter theo ngôn ngữ (JSON column)
        if ($language) {
            $query->whereJsonContains('languages', $language);
        }
        
        // Filter featured books
        if ($isFeatured !== null) {
            $isFeaturedBool = filter_var($isFeatured, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isFeaturedBool !== null) {
                $query->where('is_featured', $isFeaturedBool);
            }
        }
        
        // Filter active books
        if ($isActive !== null) {
            $isActiveBool = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActiveBool !== null) {
                $query->where('is_active', $isActiveBool);
            }
        }
        
        // Filter theo giá
        if ($priceMin !== null) {
            $query->where('price', '>=', (float)$priceMin);
        }
        
        if ($priceMax !== null) {
            $query->where('price', '<=', (float)$priceMax);
        }
        
        // Filter theo năm xuất bản
        if ($publishedYearMin !== null) {
            $query->whereYear('published_date', '>=', (int)$publishedYearMin);
        }
        
        if ($publishedYearMax !== null) {
            $query->whereYear('published_date', '<=', (int)$publishedYearMax);
        }
        
        // Sắp xếp kết quả
        $allowedSortFields = ['id', 'title', 'price', 'published_date', 'created_at', 'download_count'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'id';
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';
        
        $query->orderBy($sortBy, $sortDirection);
        
        // Lấy kết quả với phân trang
        $books = $query->paginate($perPage);
        
        $result = [
            'status' => 200,
            'data' => [
                'books' => BookResource::collection($books),
                'pagination' => [
                    'total' => $books->total(),
                    'per_page' => $books->perPage(),
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage()
                ],
                'filters' => [
                    'search' => $search,
                    'category' => $category,
                    'author_id' => $authorId,
                    'language' => $language,
                    'is_featured' => $isFeatured,
                    'is_active' => $isActive,
                    'price_min' => $priceMin,
                    'price_max' => $priceMax,
                    'published_year_min' => $publishedYearMin,
                    'published_year_max' => $publishedYearMax,
                    'sort_by' => $sortBy,
                    'sort_direction' => $sortDirection
                ]
            ]
        ];
        
        // Lưu kết quả vào cache (10 phút)
        Cache::put($cacheKey, $result, 600);
        
        return response()->json($result);
    }

    /**
     * Lấy chi tiết một cuốn sách từ database
     */
    public function show($id)
    {
        // Tạo cache key
        $cacheKey = "books:detail:{$id}";
        
        // Kiểm tra cache (30 phút)
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        $book = Book::with(['authors', 'categories'])
                    ->where('gutendex_id', $id)
                    ->orWhere('id', $id)
                    ->first();
        
        if (!$book) {
            return response()->json([
                'status' => 404,
                'error' => 'Book not found in database'
            ], 404);
        }
        
        $result = [
            'status' => 200,
            'data' => new BookResource($book)
        ];
        
        // Lưu kết quả vào cache (30 phút)
        Cache::put($cacheKey, $result, 1800);
        
        return response()->json($result);
    }

    /**
     * Lưu sách vào database (chỉ dành cho import)
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer'
        ]);
        
        // Chỉ sử dụng GutendexService để import sách mới
        $result = $this->gutendexService->saveBook($request->input('book_id'));
        
        if (isset($result['data']) && $result['data'] instanceof Book) {
            $result['data'] = new BookResource($result['data']);
            
            // Xóa cache liên quan khi thêm sách mới
            $this->clearListCaches();
        }
        
        return response()->json($result, $result['status']);
    }

    /**
     * Xóa sách khỏi database
     */
    public function destroy($id)
    {
        try {
            $book = Book::where('gutendex_id', $id)
                        ->orWhere('id', $id)
                        ->first();
            
            if (!$book) {
                return response()->json([
                    'status' => 404,
                    'error' => 'Book not found in database'
                ], 404);
            }
            
            // Xóa các liên kết trước khi xóa sách
            $book->authors()->detach();
            $book->categories()->detach();
            $book->delete();
            
            // Xóa cache liên quan khi xóa sách
            Cache::forget("books:detail:{$id}");
            $this->clearListCaches();
            
            return response()->json([
                'status' => 200,
                'message' => 'Book deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Failed to delete book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin sách trong database
     */
    public function update(Request $request, $id)
    {
        $book = Book::where('gutendex_id', $id)
                    ->orWhere('id', $id)
                    ->first();
        
        if (!$book) {
            return response()->json([
                'status' => 404,
                'error' => 'Book not found in database'
            ], 404);
        }
        
        try {
            $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string',
                'published_year' => 'sometimes|nullable|integer',
                'is_featured' => 'sometimes|boolean',
                'authors' => 'sometimes|array',
                'authors.*.id' => 'exists:authors,id',
                'categories' => 'sometimes|array',
                'categories.*.id' => 'exists:categories,id',
            ]);
            
            // Cập nhật thông tin sách
            $book->fill($request->only([
                'title', 'description', 'published_year', 'is_featured'
            ]));
            
            $book->save();
            
            // Cập nhật tác giả nếu có
            if ($request->has('authors')) {
                $authorIds = collect($request->input('authors'))->pluck('id')->toArray();
                $book->authors()->sync($authorIds);
            }
            
            // Cập nhật thể loại nếu có
            if ($request->has('categories')) {
                $categoryIds = collect($request->input('categories'))->pluck('id')->toArray();
                $book->categories()->sync($categoryIds);
            }
            
            // Xóa cache liên quan khi cập nhật sách
            Cache::forget("books:detail:{$id}");
            $this->clearListCaches();
            
            return response()->json([
                'status' => 200,
                'message' => 'Book updated successfully',
                'data' => new BookResource($book->fresh(['authors', 'categories']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Failed to update book: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import nhiều sách cùng lúc từ Gutendex API (chỉ dành cho import)
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'book_ids' => 'required|array',
            'book_ids.*' => 'integer'
        ]);
        
        $result = $this->gutendexService->bulkImportBooks($request->input('book_ids'));
        
        return response()->json($result, $result['status']);
    }

    /**
     * Lấy danh sách tác giả từ database
     */
    public function authors(Request $request)
    {
        $cacheKey = "authors:all";
        
        // Cache 1 giờ vì authors thay đổi ít
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        $authors = Author::withCount('books')->get();
        
        $result = [
            'status' => 200,
            'data' => AuthorResource::collection($authors)
        ];
        
        // Lưu cache trong 1 giờ
        Cache::put($cacheKey, $result, 3600);
        
        return response()->json($result);
    }

    /**
     * Lấy danh sách sách theo tác giả từ database
     */
    public function booksByAuthor(Request $request, $authorId)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Tạo cache key
        $cacheKey = "authors:{$authorId}:books:page:{$page}:perPage:{$perPage}";
        
        // Kiểm tra cache (15 phút)
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        try {
            $author = Author::findOrFail($authorId);
            $books = $author->books()->with(['authors', 'categories'])->paginate($perPage);
            
            $result = [
                'status' => 200,
                'data' => [
                    'author' => $author->name,
                    'books' => BookResource::collection($books),
                    'pagination' => [
                        'total' => $books->total(),
                        'per_page' => $books->perPage(),
                        'current_page' => $books->currentPage(),
                        'last_page' => $books->lastPage()
                    ]
                ]
            ];
            
            // Lưu kết quả vào cache (15 phút)
            Cache::put($cacheKey, $result, 900);
            
            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Author not found',
                'error' => 'The requested author with ID ' . $authorId . ' does not exist.'
            ], 404);
        }
    }
    
    /**
     * Lấy danh sách categories từ database
     */
    public function categories(Request $request)
    {
        $cacheKey = "categories:all";
        
        // Cache 1 giờ vì categories thay đổi ít
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        $categories = Category::withCount('books')->get();
        
        $result = [
            'status' => 200,
            'data' => CategoryResource::collection($categories)
        ];
        
        // Lưu cache trong 1 giờ
        Cache::put($cacheKey, $result, 3600);
        
        return response()->json($result);
    }
    
    /**
     * Lấy danh sách sách theo category từ database
     */
    public function booksByCategory(Request $request, $categoryId)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Tạo cache key
        $cacheKey = "categories:{$categoryId}:books:page:{$page}:perPage:{$perPage}";
        
        // Kiểm tra cache (15 phút)
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        try {
            $category = Category::findOrFail($categoryId);
            $books = $category->books()->with(['authors', 'categories'])->paginate($perPage);
            
            $result = [
                'status' => 200,
                'data' => [
                    'category' => $category->name,
                    'books' => BookResource::collection($books),
                    'pagination' => [
                        'total' => $books->total(),
                        'per_page' => $books->perPage(),
                        'current_page' => $books->currentPage(),
                        'last_page' => $books->lastPage()
                    ]
                ]
            ];
            
            // Lưu kết quả vào cache (15 phút)
            Cache::put($cacheKey, $result, 900);
            
            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found',
                'error' => 'The requested category with ID ' . $categoryId . ' does not exist.'
            ], 404);
        }
    }

    /**
     * Import tất cả sách từ Gutendex API (chỉ dành cho import)
     * Chỉ admin mới có quyền sử dụng API này
     */
    public function importAllBooks(Request $request)
    {
        // Chỉ cho phép admin import tất cả sách
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can import all books.',
            ], 403);
        }

        // Khởi tạo các tham số
        $batchSize = $request->input('batch_size', 5); // Số lượng sách trong mỗi batch
        $startPage = $request->input('start_page', 1); // Trang bắt đầu
        $maxPages = $request->input('max_pages', 10);  // Số trang tối đa sẽ import

        // Ghi log trước khi dispatch job
        \Illuminate\Support\Facades\Log::info('Admin requested to import books', [
            'user_id' => auth()->id(),
            'start_page' => $startPage,
            'max_pages' => $maxPages,
            'batch_size' => $batchSize
        ]);

        // Import books thông qua queue job
        try {
            ImportGutendexBooks::dispatch(
                $startPage,
                $maxPages,
                $batchSize
            );

            $this->importLogService->log(
                'import_all',
                'queued',
                'Import all books job queued successfully',
                [
                    'start_page' => $startPage,
                    'max_pages' => $maxPages,
                    'batch_size' => $batchSize,
                    'user_id' => auth()->id()
                ]
            );

            return response()->json([
                'message' => 'Import job has been queued',
                'data' => [
                    'start_page' => $startPage,
                    'max_pages' => $maxPages,
                    'batch_size' => $batchSize
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to dispatch import job', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->importLogService->log(
                'import_all',
                'error',
                $e->getMessage(),
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'start_page' => $startPage,
                    'max_pages' => $maxPages,
                    'batch_size' => $batchSize,
                    'user_id' => auth()->id()
                ]
            );

            return response()->json([
                'message' => 'Failed to queue import job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test imports a small batch of books from Gutendex API (chỉ dành cho import)
     */
    public function testImport(Request $request)
    {
        // Chỉ cho phép user đã xác thực
        if (!auth()->user()) {
            return response()->json([
                'message' => 'Unauthorized. Please login to use this feature.',
            ], 403);
        }

        // Khởi tạo các tham số với giá trị nhỏ để test
        $batchSize = $request->input('batch_size', 2); // Chỉ import 2 sách một lần
        $startPage = $request->input('start_page', 1); // Trang bắt đầu
        $maxPages = $request->input('max_pages', 1);  // Chỉ import 1 trang

        // Import books thông qua queue job
        ImportGutendexBooks::dispatch(
            $startPage,
            $maxPages,
            $batchSize
        );

        $this->importLogService->log(
            'test_import',
            'queued',
            'Test import job queued successfully',
            [
                'start_page' => $startPage,
                'max_pages' => $maxPages,
                'batch_size' => $batchSize,
                'user_id' => auth()->id()
            ]
        );

        return response()->json([
            'message' => 'Test import job has been queued',
            'data' => [
                'start_page' => $startPage,
                'max_pages' => $maxPages,
                'batch_size' => $batchSize
            ]
        ]);
    }
    
    /**
     * Directly imports a single book from Gutendex API (chỉ dành cho import)
     */
    public function directImport(Request $request)
    {
        // Chỉ cho phép user đã xác thực
        if (!auth()->user()) {
            return response()->json([
                'message' => 'Unauthorized. Please login to use this feature.',
            ], 403);
        }
        
        // Sử dụng book ID cụ thể nếu được cung cấp, hoặc mặc định là 1
        $bookId = $request->input('book_id', 1);
        
        try {
            // Sử dụng service để lưu sách
            $result = $this->gutendexService->saveBook($bookId);
            
            if (isset($result['error'])) {
                return response()->json([
                    'message' => 'Failed to import book',
                    'error' => $result['error']
                ], $result['status'] ?? 500);
            }
            
            // Lưu log về việc import sách
            $importLog = \App\Models\ImportLog::create([
                'type' => 'gutendex_direct_import',
                'user_id' => auth()->id(),
                'data' => [
                    'processed' => 1,
                    'success' => 1,
                    'failed' => 0,
                    'book_id' => $bookId,
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
            
            $this->importLogService->log(
                'direct_import',
                'success',
                'Direct import completed successfully',
                [
                    'book_id' => $bookId,
                    'user_id' => auth()->id(),
                    'result' => json_encode($result)
                ]
            );

            return response()->json([
                'message' => 'Book imported successfully',
                'data' => $result['data'] ?? $result,
                'log_id' => $importLog->id
            ]);
            
        } catch (\Exception $e) {
            // Log the exception for debugging
            \Illuminate\Support\Facades\Log::error('Direct import failed: ' . $e->getMessage(), [
                'book_id' => $bookId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Lưu log thất bại
            \App\Models\ImportLog::create([
                'type' => 'gutendex_direct_import_failed',
                'user_id' => auth()->id(),
                'data' => [
                    'processed' => 1,
                    'success' => 0,
                    'failed' => 1,
                    'book_id' => $bookId,
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
            
            $this->importLogService->log(
                'direct_import',
                'error',
                $e->getMessage(),
                [
                    'book_id' => $bookId,
                    'user_id' => auth()->id(),
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa cache danh sách
     */
    private function clearListCaches()
    {
        if (config('cache.default') === 'redis' && Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            // Pattern matching với Redis
            $redis = Cache::getRedis();
            
            // Xóa cache danh sách sách 
            $booksListKeys = $redis->keys('books:list:*');
            foreach ($booksListKeys as $key) {
                Cache::forget($key);
            }
            
            // Xóa cache danh sách tác giả và sách theo tác giả
            Cache::forget('authors:all');
            $authorBooksKeys = $redis->keys('authors:*:books:*');
            foreach ($authorBooksKeys as $key) {
                Cache::forget($key);
            }
            
            // Xóa cache danh sách thể loại và sách theo thể loại
            Cache::forget('categories:all');
            $categoryBooksKeys = $redis->keys('categories:*:books:*');
            foreach ($categoryBooksKeys as $key) {
                Cache::forget($key);
            }
            
            // Xóa cache gợi ý tìm kiếm
            $suggestionKeys = $redis->keys('suggestions:*');
            foreach ($suggestionKeys as $key) {
                Cache::forget($key);
            }
        } else {
            // Fallback cho non-Redis cache
            Cache::forget('categories:all');
            Cache::forget('authors:all');
            // Không thể làm pattern matching với non-Redis stores
        }
    }

    public function getImportLogs(Request $request)
    {
        $type = $request->input('type');
        $status = $request->input('status');
        $limit = $request->input('limit', 100);

        if ($type) {
            $logs = $this->importLogService->getByType($type, $limit);
        } elseif ($status) {
            $logs = $this->importLogService->getByStatus($status, $limit);
        } else {
            $logs = $this->importLogService->getRecent($limit);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'logs' => $logs,
                'stats' => $this->importLogService->getStats()
            ]
        ]);
    }
} 