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

class GutendexController extends Controller
{
    protected GutendexService $gutendexService;

    public function __construct(GutendexService $gutendexService)
    {
        $this->gutendexService = $gutendexService;
    }

    /**
     * Lấy danh sách sách với phân trang và tìm kiếm từ database
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);
        
        $query = Book::with(['authors', 'categories']);
        
        if ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhereHas('authors', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
        }
        
        $books = $query->paginate($perPage);
        
        return response()->json([
            'status' => 200,
            'data' => [
                'books' => BookResource::collection($books),
                'pagination' => [
                    'total' => $books->total(),
                    'per_page' => $books->perPage(),
                    'current_page' => $books->currentPage(),
                    'last_page' => $books->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Lấy chi tiết một cuốn sách từ database
     */
    public function show($id)
    {
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
        
        return response()->json([
            'status' => 200,
            'data' => new BookResource($book)
        ]);
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
                'title' => 'sometimes|string',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'quantity_in_stock' => 'sometimes|integer|min:0',
                'is_featured' => 'sometimes|boolean',
                'is_active' => 'sometimes|boolean',
                'discount_percent' => 'sometimes|numeric|min:0|max:100',
            ]);
            
            DB::beginTransaction();
            
            // Cập nhật thông tin cơ bản
            $updateData = $request->only([
                'title', 'description', 'price', 'quantity_in_stock',
                'is_featured', 'is_active', 'discount_percent'
            ]);
            
            if (!empty($updateData)) {
                $book->update($updateData);
            }
            
            // Cập nhật tác giả nếu có
            if ($request->has('authors')) {
                $authorIds = [];
                foreach ($request->input('authors') as $authorName) {
                    $author = Author::firstOrCreate(['name' => $authorName]);
                    $authorIds[] = $author->id;
                }
                
                if (!empty($authorIds)) {
                    $book->authors()->sync($authorIds);
                }
            }
            
            // Cập nhật danh mục nếu có
            if ($request->has('categories')) {
                $categoryIds = [];
                foreach ($request->input('categories') as $categoryName) {
                    $category = Category::firstOrCreate(['name' => $categoryName]);
                    $categoryIds[] = $category->id;
                }
                
                if (!empty($categoryIds)) {
                    $book->categories()->sync($categoryIds);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 200,
                'message' => 'Book updated successfully',
                'data' => new BookResource($book->fresh(['authors', 'categories']))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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
        $search = $request->query('search');
        $perPage = $request->query('per_page', 20);
        
        $query = Author::withCount('books');
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $authors = $query->paginate($perPage);
        
        return response()->json([
            'status' => 200,
            'data' => [
                'authors' => AuthorResource::collection($authors),
                'pagination' => [
                    'total' => $authors->total(),
                    'per_page' => $authors->perPage(),
                    'current_page' => $authors->currentPage(),
                    'last_page' => $authors->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Lấy danh sách sách theo tác giả từ database
     */
    public function booksByAuthor($authorId)
    {
        try {
            $author = Author::findOrFail($authorId);
            $books = $author->books()->with(['authors', 'categories'])->paginate(10);
            
            return response()->json([
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
            ]);
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
        $categories = Category::withCount('books')->get();
        
        return response()->json([
            'status' => 200,
            'data' => CategoryResource::collection($categories)
        ]);
    }
    
    /**
     * Lấy danh sách sách theo category từ database
     */
    public function booksByCategory($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            $books = $category->books()->with(['authors', 'categories'])->paginate(10);
            
            return response()->json([
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
            ]);
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
            
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 