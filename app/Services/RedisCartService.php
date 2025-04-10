<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Models\Book;
use Illuminate\Support\Collection;

class RedisCartService
{
    protected string $prefix = 'cart:';
    protected int $expireTime = 7 * 24 * 60 * 60; // 7 days

    public function addItem(string $userId, int $bookId, int $quantity): bool
    {
        $key = $this->getKey($userId);
        $result = Redis::hset($key, $bookId, $quantity);
        Redis::expire($key, $this->expireTime);
        return (bool) $result;
    }

    public function updateQuantity(string $userId, int $bookId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $bookId);
        }
        return $this->addItem($userId, $bookId, $quantity);
    }

    public function removeItem(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::hdel($key, $bookId);
    }

    public function getCart(string $userId): Collection
    {
        $key = $this->getKey($userId);
        $items = Redis::hgetall($key);
        
        if (empty($items)) {
            return collect();
        }

        $bookIds = array_keys($items);
        $books = Book::whereIn('id', $bookIds)->get();
        
        return $books->map(function ($book) use ($items) {
            return [
                'book' => $book,
                'quantity' => (int) $items[$book->id]
            ];
        });
    }

    public function getItemCount(string $userId): int
    {
        $key = $this->getKey($userId);
        return Redis::hlen($key);
    }

    public function getTotalQuantity(string $userId): int
    {
        $key = $this->getKey($userId);
        $items = Redis::hgetall($key);
        return array_sum($items);
    }

    public function clear(string $userId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::del($key);
    }

    public function exists(string $userId, int $bookId): bool
    {
        $key = $this->getKey($userId);
        return (bool) Redis::hexists($key, $bookId);
    }

    public function getQuantity(string $userId, int $bookId): int
    {
        $key = $this->getKey($userId);
        return (int) Redis::hget($key, $bookId) ?: 0;
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
                'tax_amount'=>0,
                'shipping_fee'=>$orderData['shipping_fee'],
                'discount_amount' => $cartData['discount_amount'],
                'final_amount' => (float) $cartData['final_amount']+ (float)$orderData['shipping_fee'],
                'payment_method' => $orderData['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
                'note' => $orderData['note'] ?? null,
                'order_date' => now(),
                'payment_date' => null,
                'shipping_date' => null,
                'delivery_date'=>null
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
} 