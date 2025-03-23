<?php

namespace App\Http\Controllers;

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
        return $this->gutendexService->getBooks($search, $page);
    }

    /**
     * Lấy chi tiết một cuốn sách
     */
    public function show($id)
    {
        return $this->gutendexService->getBook($id);
    }

    /**
     * Lưu sách vào database local
     */
    public function store(Request $request)
    {
        $bookId = $request->input('book_id');
        return $this->gutendexService->saveBook($bookId);
    }

    /**
     * Xóa sách khỏi database local
     */
    public function destroy($id)
    {
        return $this->gutendexService->deleteBook($id);
    }

    /**
     * Lấy danh sách tác giả
     */
    public function authors(Request $request)
    {
        $search = $request->query('search');
        $page = $request->query('page', 1);
        return $this->gutendexService->getAuthors($search, $page);
    }

    /**
     * Lấy danh sách sách theo tác giả
     */
    public function booksByAuthor($authorId)
    {
        return $this->gutendexService->getBooksByAuthor($authorId);
    }
} 