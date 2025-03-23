<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\BookResource;
use App\Http\Resources\CategoryResource;
use App\Models\Book;
use App\Models\Category;
use App\Services\GutendexService;
use Illuminate\Http\Request;

class GutendexController extends Controller
{
    protected GutendexService $gutendexService;

    public function __construct(GutendexService $gutendexService)
    {
        $this->gutendexService = $gutendexService;
    }

    /**
     * Lấy danh sách sách với phân trang và tìm kiếm
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);
        $result = $this->gutendexService->getBooks($search, $page);
        
        return response()->json($result, $result['status']);
    }

    /**
     * Lấy chi tiết một cuốn sách
     */
    public function show($id)
    {
        $result = $this->gutendexService->getBook($id);
        
        return response()->json($result, $result['status']);
    }

    /**
     * Lưu sách vào database local
     */
    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|integer'
        ]);
        
        $result = $this->gutendexService->saveBook($request->input('book_id'));
        
        if (isset($result['data']) && $result['data'] instanceof Book) {
            $result['data'] = new BookResource($result['data']);
        }
        
        return response()->json($result, $result['status']);
    }

    /**
     * Xóa sách khỏi database local
     */
    public function destroy($id)
    {
        $result = $this->gutendexService->deleteBook($id);
        
        return response()->json($result, $result['status']);
    }

    /**
     * Cập nhật thông tin sách từ Gutendex API
     */
    public function update($id)
    {
        $result = $this->gutendexService->updateBook($id);
        
        if (isset($result['data']) && $result['data'] instanceof Book) {
            $result['data'] = new BookResource($result['data']);
        }
        
        return response()->json($result, $result['status']);
    }

    /**
     * Import nhiều sách cùng lúc từ Gutendex API
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
     * Lấy danh sách tác giả
     */
    public function authors(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);
        $result = $this->gutendexService->getAuthors($search, $page);
        
        return response()->json($result, $result['status']);
    }

    /**
     * Lấy danh sách sách theo tác giả
     */
    public function booksByAuthor($authorId)
    {
        $result = $this->gutendexService->getBooksByAuthor($authorId);
        
        return response()->json($result, $result['status']);
    }
    
    /**
     * Lấy danh sách categories từ database
     */
    public function categories(Request $request)
    {
        $search = $request->query('search');
        $limit = $request->query('limit', 20);
        
        $query = Category::query();
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        
        $categories = $query->orderBy('name')->paginate($limit);
        
        return response()->json([
            'status' => 200,
            'data' => [
                'categories' => CategoryResource::collection($categories),
                'pagination' => [
                    'total' => $categories->total(),
                    'per_page' => $categories->perPage(),
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage()
                ]
            ]
        ]);
    }
    
    /**
     * Lấy danh sách sách theo category
     */
    public function booksByCategory($categoryId)
    {
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
    }
} 