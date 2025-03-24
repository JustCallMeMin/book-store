<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AutocompleteController extends Controller
{
    /**
     * Tìm kiếm và gợi ý sách, tác giả, thể loại với Redis cache
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q');
        if (!$query || strlen($query) < 2) {
            return response()->json([
                'status' => 200,
                'data' => []
            ]);
        }
        
        $cacheKey = "suggestions:{$query}";
        
        // Cache 30 phút
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        // Tìm kiếm suggested books (giới hạn 5)
        $books = Book::where('title', 'like', "%{$query}%")
                     ->take(5)
                     ->get(['id', 'title', 'cover_image']);
        
        // Tìm kiếm suggested authors (giới hạn 3)
        $authors = Author::where('name', 'like', "%{$query}%")
                        ->take(3)
                        ->get(['id', 'name']);
        
        // Tìm kiếm suggested categories (giới hạn 3)
        $categories = Category::where('name', 'like', "%{$query}%")
                             ->take(3)
                             ->get(['id', 'name']);
        
        $result = [
            'status' => 200,
            'data' => [
                'books' => $books,
                'authors' => $authors,
                'categories' => $categories
            ]
        ];
        
        // Lưu cache trong 30 phút
        Cache::put($cacheKey, $result, 1800);
        
        return response()->json($result);
    }

    /**
     * Xóa cache suggestion khi có dữ liệu thay đổi
     */
    public function clearSuggestionCache()
    {
        if (config('cache.default') === 'redis' && Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Cache::getRedis()->keys('suggestions:*');
            
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            // Thông báo khi không sử dụng Redis
            \Illuminate\Support\Facades\Log::info('Clear suggestion cache requested but not using Redis cache driver');
        }
        
        return response()->json([
            'status' => 200,
            'message' => 'Suggestion cache cleared successfully'
        ]);
    }
}
