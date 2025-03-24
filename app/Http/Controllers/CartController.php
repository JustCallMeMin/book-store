<?php

namespace App\Http\Controllers;

use App\Services\RedisCartService;
use Illuminate\Http\Request;
use App\Models\Book;

class CartController extends Controller
{
    protected RedisCartService $cartService;

    public function __construct(RedisCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $items = $this->cartService->getCart($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'total_items' => $this->cartService->getItemCount($request->user()->id),
                'total_quantity' => $this->cartService->getTotalQuantity($request->user()->id)
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $book = Book::findOrFail($request->book_id);
        $this->cartService->addItem(
            $request->user()->id,
            $book->id,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart successfully'
        ]);
    }

    public function update(Request $request, $bookId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $book = Book::findOrFail($bookId);
        $this->cartService->updateQuantity(
            $request->user()->id,
            $book->id,
            $request->quantity
        );

        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully'
        ]);
    }

    public function destroy(Request $request, $bookId)
    {
        $book = Book::findOrFail($bookId);
        $this->cartService->removeItem($request->user()->id, $book->id);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart successfully'
        ]);
    }

    public function clear(Request $request)
    {
        $this->cartService->clear($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully'
        ]);
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