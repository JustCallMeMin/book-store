<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleBooksService;
use App\Services\GutendexService;
use App\Http\Resources\GoogleBookResource;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Validator;

class GoogleBooksController extends Controller
{
    protected $googleBooksService;
    protected $gutendexService;

    public function __construct(GoogleBooksService $googleBooksService, GutendexService $gutendexService)
    {
        $this->googleBooksService = $googleBooksService;
        $this->gutendexService = $gutendexService;
    }

    /**
     * Tìm kiếm sách từ Google Books API
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        // Kiểm tra xem có API key cho Google Books không
        if (!$this->googleBooksService->hasFallback()) {
            return response()->json([
                'error' => 'Google Books API key not configured. Please use Gutendex API instead.',
                'api_url' => '/api/gutendex/books'
            ], 400);
        }

        $result = $this->googleBooksService->search($query, $page, $perPage);

        if ($result['status'] !== 200) {
            return response()->json([
                'error' => $result['error']
            ], $result['status']);
        }

        return response()->json($result['data']);
    }

    /**
     * Lấy chi tiết một cuốn sách từ Google Books API
     */
    public function show($id)
    {
        // Kiểm tra xem có API key cho Google Books không
        if (!$this->googleBooksService->hasFallback()) {
            return response()->json([
                'error' => 'Google Books API key not configured. Please use Gutendex API instead.',
                'api_url' => '/api/gutendex/books'
            ], 400);
        }

        $result = $this->googleBooksService->getBook($id);

        if ($result['status'] !== 200) {
            return response()->json([
                'error' => $result['error']
            ], $result['status']);
        }

        return response()->json($result['data']);
    }

    /**
     * Import một cuốn sách từ Google Books vào database
     */
    public function import($id)
    {
        // Kiểm tra xem có API key cho Google Books không
        if (!$this->googleBooksService->hasFallback()) {
            // Fallback sang Gutendex nếu không có Google Books API key
            // Giả sử rằng $id có thể chuyển đổi thành Gutendex ID
            $result = $this->gutendexService->saveBook((int) $id);

            if ($result['status'] === 200) {
                return response()->json([
                    'message' => 'Book imported successfully using Gutendex API',
                    'data' => new BookResource($result['data'])
                ]);
            }

            return response()->json([
                'error' => $result['error'] ?? 'Failed to import book'
            ], $result['status']);
        }

        $result = $this->googleBooksService->importBook($id);

        if ($result['status'] !== 200) {
            return response()->json([
                'error' => $result['error']
            ], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => new GoogleBookResource($result['data'])
        ]);
    }

    /**
     * Import nhiều sách từ Google Books vào database
     */
    public function bulkImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_ids' => 'required|array',
            'book_ids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 400);
        }

        // Kiểm tra xem có API key cho Google Books không
        if (!$this->googleBooksService->hasFallback()) {
            // Fallback sang Gutendex nếu không có Google Books API key
            // Chuyển đổi các ID từ Google Books sang Gutendex
            $gutendexIds = array_map('intval', $request->input('book_ids'));
            $result = $this->gutendexService->bulkImportBooks($gutendexIds);

            return response()->json($result);
        }

        $result = $this->googleBooksService->bulkImportBooks($request->input('book_ids'));

        return response()->json($result);
    }

    /**
     * Tìm kiếm sách bằng ISBN
     */
    public function searchByISBN(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'isbn' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 400);
        }

        // Kiểm tra xem có API key cho Google Books không
        if (!$this->googleBooksService->hasFallback()) {
            return response()->json([
                'error' => 'Google Books API key not configured. ISBN search is not available with Gutendex API.'
            ], 400);
        }

        $result = $this->googleBooksService->searchByISBN($request->input('isbn'));

        if ($result['status'] !== 200) {
            return response()->json([
                'error' => $result['error']
            ], $result['status']);
        }

        return response()->json($result['data']);
    }
}
