<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Lấy danh sách tất cả người dùng (chỉ dành cho admin)
     */
    public function index(Request $request)
    {
        // Tham số phân trang
        $limit = $request->input('limit', 15);
        $page = $request->input('page', 1);
        
        // Tham số sắp xếp
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Tham số tìm kiếm
        $search = $request->input('search');
        
        // Bắt đầu query builder
        $query = User::with('roles');
        
        // Áp dụng tìm kiếm nếu có
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Áp dụng sắp xếp
        $query->orderBy($sortBy, $sortOrder);
        
        // Lấy kết quả phân trang
        $users = $query->paginate($limit, ['*'], 'page', $page);
        
        // Chuyển đổi dùng resource collection
        return UserResource::collection($users)
            ->additional([
                'meta' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ],
                'status' => 200,
                'message' => 'Lấy danh sách người dùng thành công'
            ]);
    }
} 