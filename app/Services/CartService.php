<?php

namespace App\Services;

use App\Models\Cart;

interface CartService
{
    /**
     * Lấy thông tin giỏ hàng hiện tại
     */
    public function getCart(): array;

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addItem(int $bookId, int $quantity): array;

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function updateItem(int $bookId, int $quantity): array;

    /**
     * Lấy tổng số lượng sản phẩm trong giỏ hàng
     */
    public function getTotalQuantity(): int;

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clearCart(): void;

    /**
     * Lấy ID của giỏ hàng
     */
    public function getCartId(): string;

    /**
     * Chuyển đổi giỏ hàng thành đơn hàng
     */
    public function convertToOrder(array $orderData): mixed;

    /**
     * Kiểm tra sản phẩm có trong giỏ hàng không
     */
    public function exists(string $userId, int $bookId): bool;

    /**
     * Lấy số lượng sản phẩm trong giỏ hàng
     */
    public function getQuantity(string $userId, int $bookId): int;

    /**
     * Transfer cart from Redis to database
     */
    public function transferToDatabase(string $cartId, bool $forceIsGuest = false): ?Cart;
} 