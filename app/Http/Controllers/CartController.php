<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Lấy thông tin giỏ hàng hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart();
            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin giỏ hàng thành công',
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cart', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'book_id' => 'required|exists:books,id',
                'quantity' => 'required|integer|min:1|max:10',
            ]);

            $bookId = $request->input('book_id');
            $quantity = $request->input('quantity');

            // Kiểm tra số lượng tồn kho
            $book = Book::findOrFail($bookId);
            if ($book->quantity_in_stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng sách trong kho không đủ',
                ], 400);
            }

            // Kiểm tra tổng số lượng trong giỏ hàng không vượt quá 10
            $currentCart = $this->cartService->getCart();
            $currentQuantity = 0;
            foreach ($currentCart['items'] as $item) {
                if ($item['book_id'] == $bookId) {
                    $currentQuantity = $item['quantity'];
                    break;
                }
            }

            if ($currentQuantity + $quantity > 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tổng số lượng sản phẩm không được vượt quá 10',
                ], 400);
            }

            $result = $this->cartService->addItem($bookId, $quantity);
            
            return response()->json([
                'success' => true,
                'message' => 'Thêm sản phẩm vào giỏ hàng thành công',
                'data' => $result,
                'total_quantity' => $this->cartService->getTotalQuantity()
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding item to cart', [
                'user_id' => auth()->id(),
                'book_id' => $request->input('book_id'),
                'quantity' => $request->input('quantity'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm sản phẩm vào giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function update(Request $request, $bookId): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:0|max:10',
            ]);

            $quantity = $request->input('quantity');
            
            // Kiểm tra số lượng tồn kho
            $book = Book::findOrFail($bookId);
            if ($quantity > 0 && $book->quantity_in_stock < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số lượng sách trong kho không đủ',
                ], 400);
            }

            $result = $this->cartService->updateItem($bookId, $quantity);
            
            return response()->json([
                'success' => true,
                'message' => $quantity > 0 ? 'Cập nhật giỏ hàng thành công' : 'Đã xóa sản phẩm khỏi giỏ hàng',
                'data' => $result,
                'total_quantity' => $this->cartService->getTotalQuantity()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating cart item', [
                'user_id' => auth()->id(),
                'book_id' => $bookId,
                'quantity' => $request->input('quantity'),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function destroy(): JsonResponse
    {
        try {
            $this->cartService->clearCart();
            
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa toàn bộ giỏ hàng'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cart', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa giỏ hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function check(Request $request, $bookId)
    {
        $book = Book::findOrFail($bookId);
        $exists = $this->cartService->exists($request->user()->id, $book->id);
        $quantity = $exists ? $this->cartService->getQuantity($request->user()->id, $book->id) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'quantity' => $quantity
            ]
        ]);
    }
} 