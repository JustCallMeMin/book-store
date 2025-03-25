<?php

namespace App\Http\Controllers;

use App\Services\RedisFavoriteService;
use App\Services\RedisActivityService;
use Illuminate\Http\Request;
use App\Models\Book;

class FavoriteController extends Controller
{
    protected RedisFavoriteService $favoriteService;
    protected RedisActivityService $activityService;

    public function __construct(
        RedisFavoriteService $favoriteService,
        RedisActivityService $activityService
    ) {
        $this->favoriteService = $favoriteService;
        $this->activityService = $activityService;
    }

    public function index(Request $request)
    {
        $bookIds = $this->favoriteService->getAll($request->user()->id);
        $books = Book::whereIn('id', $bookIds)->get();

        return response()->json([
            'success' => true,
            'data' => $books
        ]);
    }

    public function store(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $this->favoriteService->add($request->user()->id, $book->id);
        
        // Log activity
        $this->activityService->log(
            $request->user()->id,
            'add_favorite',
            'Added book to favorites: ' . $book->title,
            ['book_id' => $book->id, 'book_title' => $book->title],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Book added to favorites'
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $this->favoriteService->remove($request->user()->id, $book->id);
        
        // Log activity
        $this->activityService->log(
            $request->user()->id,
            'remove_favorite',
            'Removed book from favorites: ' . $book->title,
            ['book_id' => $book->id, 'book_title' => $book->title],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'success' => true,
            'message' => 'Book removed from favorites'
        ]);
    }

    public function check(Request $request, $id)
    {
        $book = Book::findOrFail($id);
        $isFavorite = $this->favoriteService->check($request->user()->id, $book->id);

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite
        ]);
    }
}
