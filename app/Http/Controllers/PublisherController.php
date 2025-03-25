<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Http\Resources\PublisherResource;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublisherController extends Controller
{
    /**
     * Lấy danh sách nhà xuất bản với số lượng sách
     */
    public function index(Request $request)
    {
        $cacheKey = "publishers:all";
        
        // Cache 1 giờ vì publishers thay đổi ít
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        $publishers = Publisher::withCount('books')->get();
        
        $result = [
            'status' => 200,
            'data' => PublisherResource::collection($publishers)
        ];
        
        // Lưu cache trong 1 giờ
        Cache::put($cacheKey, $result, 3600);
        
        return response()->json($result);
    }
    
    /**
     * Lấy chi tiết nhà xuất bản
     */
    public function show($id)
    {
        try {
            $publisher = Publisher::withCount('books')->findOrFail($id);
            
            return response()->json([
                'status' => 200,
                'data' => new PublisherResource($publisher)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Publisher not found',
                'error' => 'The requested publisher with ID ' . $id . ' does not exist.'
            ], 404);
        }
    }
    
    /**
     * Lấy danh sách sách theo nhà xuất bản
     */
    public function books(Request $request, $id)
    {
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);
        
        // Tạo cache key
        $cacheKey = "publishers:{$id}:books:page:{$page}:perPage:{$perPage}";
        
        // Kiểm tra cache (15 phút)
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        
        try {
            $publisher = Publisher::findOrFail($id);
            $books = $publisher->books()->with(['authors', 'categories', 'publisher'])->paginate($perPage);
            
            $result = [
                'status' => 200,
                'data' => [
                    'publisher' => $publisher->name,
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
                'message' => 'Publisher not found',
                'error' => 'The requested publisher with ID ' . $id . ' does not exist.'
            ], 404);
        }
    }
    
    /**
     * Tạo mới nhà xuất bản (chỉ cho admin)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:publishers,name'
        ]);
        
        $publisher = Publisher::create([
            'name' => $request->input('name')
        ]);
        
        // Xóa cache danh sách publishers
        Cache::forget('publishers:all');
        
        return response()->json([
            'status' => 201,
            'message' => 'Publisher created successfully',
            'data' => new PublisherResource($publisher)
        ], 201);
    }
    
    /**
     * Cập nhật thông tin nhà xuất bản (chỉ cho admin)
     */
    public function update(Request $request, $id)
    {
        try {
            $publisher = Publisher::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255|unique:publishers,name,' . $id
            ]);
            
            $publisher->update([
                'name' => $request->input('name')
            ]);
            
            // Xóa cache liên quan
            Cache::forget('publishers:all');
            $cacheKey = "publishers:{$id}:books:*";
            
            if (config('cache.default') === 'redis' && Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Cache::getRedis()->keys($cacheKey);
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
            
            return response()->json([
                'status' => 200,
                'message' => 'Publisher updated successfully',
                'data' => new PublisherResource($publisher)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Publisher not found',
                'error' => 'The requested publisher with ID ' . $id . ' does not exist.'
            ], 404);
        }
    }
    
    /**
     * Xóa nhà xuất bản (chỉ cho admin và chỉ nếu không có sách liên kết)
     */
    public function destroy($id)
    {
        try {
            $publisher = Publisher::withCount('books')->findOrFail($id);
            
            // Kiểm tra xem có sách liên kết không
            if ($publisher->books_count > 0) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Cannot delete this publisher',
                    'error' => 'This publisher has ' . $publisher->books_count . ' books associated with it. Remove the books first.'
                ], 400);
            }
            
            $publisher->delete();
            
            // Xóa cache liên quan
            Cache::forget('publishers:all');
            
            return response()->json([
                'status' => 200,
                'message' => 'Publisher deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Publisher not found',
                'error' => 'The requested publisher with ID ' . $id . ' does not exist.'
            ], 404);
        }
    }
} 