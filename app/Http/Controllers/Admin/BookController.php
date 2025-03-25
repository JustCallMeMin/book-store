<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookStockHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::with('authors')->paginate(15);
        return view('admin.books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.books.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            // Add other validation rules as needed
        ]);

        $book = Book::create($validated);
        
        // Record the initial stock if not zero
        if ($book->quantity_in_stock > 0) {
            BookStockHistory::recordAdjustment(
                $book,
                0,
                $book->quantity_in_stock,
                'set',
                'Initial stock when book was created'
            );
        }
        
        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được tạo thành công.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $book = Book::with('authors', 'categories')->findOrFail($id);
        return view('admin.books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $book = Book::findOrFail($id);
        return view('admin.books.edit', compact('book'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|integer|min:0',
            // Add other validation rules as needed
        ]);

        $book = Book::findOrFail($id);
        $oldQuantity = $book->quantity_in_stock;
        $newQuantity = $validated['quantity_in_stock'];
        
        $book->update($validated);
        
        // Record stock change if quantity changed
        if ($oldQuantity != $newQuantity) {
            BookStockHistory::recordAdjustment(
                $book,
                $oldQuantity,
                $newQuantity,
                'set',
                'Changed during book edit'
            );
        }
        
        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được cập nhật thành công.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $book = Book::findOrFail($id);
        $book->delete();
        
        return redirect()->route('admin.books.index')
            ->with('success', 'Sách đã được xóa thành công.');
    }

    /**
     * Show the form for updating stock.
     */
    public function editStock(string $id)
    {
        $book = Book::findOrFail($id);
        return view('admin.books.stock', compact('book'));
    }

    /**
     * Update the book stock.
     */
    public function updateStock(Request $request, string $id)
    {
        $validated = $request->validate([
            'quantity_in_stock' => 'required|integer|min:0',
            'stock_action' => 'required|in:set,add,subtract',
            'adjustment_reason' => 'nullable|string|max:255',
        ]);

        $book = Book::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $oldQuantity = $book->quantity_in_stock;
            $newQuantity = $oldQuantity;
            
            switch ($validated['stock_action']) {
                case 'set':
                    $newQuantity = $validated['quantity_in_stock'];
                    break;
                case 'add':
                    $newQuantity = $oldQuantity + $validated['quantity_in_stock'];
                    break;
                case 'subtract':
                    $newQuantity = max(0, $oldQuantity - $validated['quantity_in_stock']);
                    break;
            }
            
            $book->quantity_in_stock = $newQuantity;
            $book->save();
            
            // Record the stock change in history
            BookStockHistory::recordAdjustment(
                $book,
                $oldQuantity,
                $newQuantity,
                $validated['stock_action'],
                $validated['adjustment_reason'] ?? 'Admin update'
            );
            
            // Log the stock change
            Log::info('Stock updated', [
                'book_id' => $book->id,
                'title' => $book->title,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'action' => $validated['stock_action'],
                'adjustment' => $validated['quantity_in_stock'],
                'reason' => $validated['adjustment_reason'] ?? 'Admin update',
                'admin_id' => auth()->id()
            ]);
            
            DB::commit();
            
            return redirect()->route('admin.books.show', $book->id)
                ->with('success', 'Số lượng tồn kho đã được cập nhật thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update stock', [
                'book_id' => $book->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors(['message' => 'Có lỗi xảy ra khi cập nhật số lượng tồn kho.']);
        }
    }

    /**
     * Display the stock history for a book.
     */
    public function stockHistory(string $id)
    {
        $book = Book::findOrFail($id);
        $histories = BookStockHistory::where('book_id', $book->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.books.stock_history', compact('book', 'histories'));
    }
}
