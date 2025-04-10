<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class RedisCartService implements CartService
{
    protected string $prefix = 'cart:';
    protected int $expireTime = 7 * 24 * 60 * 60; // 7 days

    /**
     * Lấy thông tin giỏ hàng hiện tại
     */
    public function getCart(): array
    {
        $cartId = $this->getCartId();
        $cartData = $this->getCartData($cartId);

        if (empty($cartData)) {
            return [
                'items' => [],
                'total_items' => 0,
                'total_amount' => 0,
                'discount_amount' => 0,
                'final_amount' => 0
            ];
        }

        $items = [];
        $totalAmount = 0;
        $discountAmount = 0;
        $finalAmount = 0;

        foreach ($cartData['items'] as $bookId => $item) {
            $book = Book::find($bookId);
            if ($book) {
                $itemData = [
                    'book_id' => (int)$bookId,
                    'title' => $book->title,
                    'author' => $book->authors->count() > 0 ? $book->authors->first()->name : 'Unknown',
                    'cover_image' => $book->cover_image,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'final_price' => $item['unit_price'] - $item['discount_amount'],
                    'subtotal' => $item['quantity'] * ($item['unit_price'] - $item['discount_amount'])
                ];

                $totalAmount += $item['quantity'] * $item['unit_price'];
                $discountAmount += $item['quantity'] * $item['discount_amount'];
                $finalAmount += $item['quantity'] * ($item['unit_price'] - $item['discount_amount']);

                $items[] = $itemData;
            }
        }

        return [
            'items' => $items,
            'total_items' => count($items),
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount
        ];
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addItem(int $bookId, int $quantity): array
    {
        $cartId = $this->getCartId();
        $cartKey = $this->getCartKey($cartId);
        $cartData = $this->getCartData($cartId);

        if (empty($cartData)) {
            $cartData = [
                'items' => [],
                'last_activity' => now()->timestamp
            ];
        }

        $book = Book::findOrFail($bookId);
        $existingQuantity = 0;

        if (isset($cartData['items'][$bookId])) {
            $existingQuantity = $cartData['items'][$bookId]['quantity'];
            $cartData['items'][$bookId]['quantity'] += $quantity;
        } else {
            $cartData['items'][$bookId] = [
                'quantity' => $quantity,
                'unit_price' => (float)$book->price,
                'discount_amount' => (float)($book->discount_price ?? 0)
            ];
        }

        $cartData['last_activity'] = now()->timestamp;
        Redis::set($cartKey, json_encode($cartData));
        Redis::expire($cartKey, $this->expireTime);

        // Return updated cart
        return $this->getCart();
    }

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function updateItem(int $bookId, int $quantity): array
    {
        $cartId = $this->getCartId();
        $cartKey = $this->getCartKey($cartId);
        $cartData = $this->getCartData($cartId);

        if (empty($cartData) || !isset($cartData['items'][$bookId])) {
            throw new \Exception('Item not found in cart');
        }

        if ($quantity > 0) {
            $book = Book::findOrFail($bookId);
            $cartData['items'][$bookId]['quantity'] = $quantity;
        } else {
            unset($cartData['items'][$bookId]);
        }

        $cartData['last_activity'] = now()->timestamp;
        Redis::set($cartKey, json_encode($cartData));
        Redis::expire($cartKey, $this->expireTime);

        // Return updated cart
        return $this->getCart();
    }

    /**
     * Lấy tổng số lượng sản phẩm trong giỏ hàng
     */
    public function getTotalQuantity(): int
    {
        $cartId = $this->getCartId();
        $cartData = $this->getCartData($cartId);

        if (empty($cartData) || empty($cartData['items'])) {
            return 0;
        }

        $total = 0;
        foreach ($cartData['items'] as $item) {
            $total += $item['quantity'];
        }

        return $total;
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clearCart(): void
    {
        $cartId = $this->getCartId();
        $cartKey = $this->getCartKey($cartId);
        Redis::del($cartKey);

        Log::info('Cart cleared', [
            'cart_id' => $cartId,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Lấy ID của giỏ hàng
     */
    public function getCartId(): string
    {
        if (Auth::check()) {
            // User is logged in, use user ID
            return $this->getUserCartId(Auth::id());
        } else {
            // Guest user, use session ID
            if (!Session::has('cart_id')) {
                Session::put('cart_id', Str::uuid()->toString());
            }
            return Session::get('cart_id');
        }
    }

    /**
     * Chuyển đổi giỏ hàng thành đơn hàng
     */
    public function convertToOrder(array $orderData): mixed
    {
        $cartData = $this->getCart();
        if (empty($cartData['items'])) {
            throw new \Exception('Cart is empty');
        }

        DB::beginTransaction();
        try {
            // Kiểm tra tồn kho
            foreach ($cartData['items'] as $item) {
                $book = Book::find($item['book_id']);
                if (!$book || $book->quantity_in_stock < $item['quantity']) {
                    throw new \Exception("Not enough stock for book: {$item['title']}");
                }
            }

            // Tạo order
            $order = new Order([
                'order_code' => 'ORD-' . strtoupper(Str::random(8)),
                'user_id' => Auth::id(),
                'recipient_name' => $orderData['recipient_name'],
                'recipient_address' => $orderData['recipient_address'],
                'recipient_phone' => $orderData['recipient_phone'],
                'recipient_email' => $orderData['recipient_email'] ?? Auth::user()->email,
                'total_amount' => $cartData['total_amount'],
                'discount_amount' => $cartData['discount_amount'],
                'final_amount' => $cartData['final_amount'],
                'payment_method' => $orderData['payment_method'],
                'payment_status' => 'pending',
                'status' => 'new',
                'note' => $orderData['note'] ?? null
            ]);

            $order->save();

            // Tạo order items và cập nhật tồn kho
            foreach ($cartData['items'] as $item) {
                $book = Book::find($item['book_id']);
                
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'book_id' => $item['book_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'final_price' => $item['final_price']
                ]);
                
                $orderItem->save();
                
                // Giảm số lượng tồn kho
                $book->decrement('quantity_in_stock', $item['quantity']);
            }

            // Xóa giỏ hàng
            $this->clearCart();
            
            DB::commit();
            
            Log::info('Order created from cart', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'user_id' => Auth::id(),
                'total_amount' => $order->final_amount
            ]);
            
            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order from cart', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get cart data from Redis
     */
    protected function getCartData(string $cartId): array
    {
        $cartKey = $this->getCartKey($cartId);
        $cartJson = Redis::get($cartKey);
        
        if (!$cartJson) {
            return [];
        }
        
        return json_decode($cartJson, true) ?: [];
    }

    /**
     * Get cart key for Redis
     */
    protected function getCartKey(string $cartId): string
    {
        return $this->prefix . $cartId;
    }

    /**
     * Get user cart ID
     */
    protected function getUserCartId(string $userId): string
    {
        return $this->prefix . $userId;
    }

    /**
     * Check if cart ID belongs to a user
     */
    protected function isUserCart(string $cartId): bool
    {
        return Str::startsWith($cartId, $this->prefix) && Str::after($cartId, $this->prefix) !== '';
    }

    /**
     * Transfer cart from Redis to database
     */
    public function transferToDatabase(string $cartId, bool $forceIsGuest = false): ?Cart
    {
        $cartData = $this->getCartData($cartId);
        if (empty($cartData) || empty($cartData['items'])) {
            return null;
        }

        DB::beginTransaction();
        try {
            // Extract user ID from cart ID if it's a user cart
            $userId = null;
            if ($this->isUserCart($cartId) && !$forceIsGuest) {
                $userId = Str::after($cartId, $this->prefix);
            }
            
            // Tạo cart mới trong database
            $cart = Cart::create([
                'id' => $cartId,
                'user_id' => $userId,
                'session_id' => $userId ? null : $cartId,
                'total_items' => array_sum(array_column($cartData['items'], 'quantity')),
                'total_amount' => array_sum(array_map(function($item) {
                    return $item['quantity'] * $item['unit_price'];
                }, $cartData['items'])),
                'discount_amount' => array_sum(array_column($cartData['items'], 'discount_amount')),
                'final_amount' => array_sum(array_map(function($item) {
                    return $item['quantity'] * ($item['unit_price'] - $item['discount_amount']);
                }, $cartData['items'])),
                'expires_at' => now()->addDays(7),
                'last_activity' => $cartData['last_activity'] ?? now(),
                'is_guest' => !$userId || $forceIsGuest
            ]);

            // Tạo cart items
            foreach ($cartData['items'] as $bookId => $item) {
                $cart->items()->create([
                    'book_id' => $bookId,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'final_price' => $item['unit_price'] - $item['discount_amount']
                ]);
            }

            DB::commit();
            Log::info('Cart transferred to database', [
                'cart_id' => $cartId,
                'total_items' => $cart->total_items,
                'final_amount' => $cart->final_amount
            ]);

            return $cart;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to transfer cart to database', [
                'cart_id' => $cartId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function mergeGuestCart(string $guestCartId, string $userCartId): void
    {
        $guestCart = $this->getCartData($guestCartId);
        if (empty($guestCart) || empty($guestCart['items'])) {
            return;
        }

        // Lưu guest cart vào database trước khi merge
        $this->transferToDatabase($guestCartId, true);

        // Merge items vào user cart
        foreach ($guestCart['items'] as $bookId => $item) {
            $this->addItem($bookId, $item['quantity']);
        }

        // Xóa guest cart
        Redis::del($this->getCartKey($guestCartId));

        Log::info('Guest cart merged with user cart', [
            'guest_cart_id' => $guestCartId,
            'user_cart_id' => $userCartId
        ]);
    }

    /**
     * Kiểm tra sản phẩm có trong giỏ hàng không
     */
    public function exists(string $userId, int $bookId): bool
    {
        $cartId = $this->getUserCartId($userId);
        $cartData = $this->getCartData($cartId);
        
        if (empty($cartData) || empty($cartData['items'])) {
            return false;
        }
        
        return isset($cartData['items'][$bookId]);
    }
    
    /**
     * Lấy số lượng sản phẩm trong giỏ hàng
     */
    public function getQuantity(string $userId, int $bookId): int
    {
        $cartId = $this->getUserCartId($userId);
        $cartData = $this->getCartData($cartId);
        
        if (empty($cartData) || empty($cartData['items']) || !isset($cartData['items'][$bookId])) {
            return 0;
        }
        
        return $cartData['items'][$bookId]['quantity'];
    }
} 