@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h2>Cập Nhật Tồn Kho Sách</h2>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h4>{{ $book->title }}</h4>
                        <p><strong>ID:</strong> {{ $book->id }}</p>
                        <p><strong>Tồn kho hiện tại:</strong> {{ $book->quantity_in_stock }} cuốn</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.books.update-stock', $book->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="stock_action">Thao tác:</label>
                            <select name="stock_action" id="stock_action" class="form-control" required>
                                <option value="set">Đặt giá trị cụ thể</option>
                                <option value="add">Thêm vào tồn kho</option>
                                <option value="subtract">Trừ từ tồn kho</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="quantity_in_stock">Số lượng:</label>
                            <input type="number" name="quantity_in_stock" id="quantity_in_stock" class="form-control" value="0" min="0" required>
                            <small class="form-text text-muted stock-help">Số lượng sẽ được đặt thành giá trị này.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="adjustment_reason">Lý do điều chỉnh:</label>
                            <textarea name="adjustment_reason" id="adjustment_reason" class="form-control" rows="3"></textarea>
                            <small class="form-text text-muted">Ghi chú nội bộ về việc tại sao tồn kho được điều chỉnh.</small>
                        </div>

                        <div class="form-group d-flex justify-content-between">
                            <a href="{{ route('admin.books.show', $book->id) }}" class="btn btn-secondary">Hủy</a>
                            <button type="submit" class="btn btn-primary">Cập Nhật Tồn Kho</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('stock_action').addEventListener('change', function() {
        const helpText = document.querySelector('.stock-help');
        switch(this.value) {
            case 'set':
                helpText.textContent = 'Số lượng sẽ được đặt thành giá trị này.';
                break;
            case 'add':
                helpText.textContent = 'Số lượng này sẽ được thêm vào tồn kho hiện tại.';
                break;
            case 'subtract':
                helpText.textContent = 'Số lượng này sẽ được trừ từ tồn kho hiện tại.';
                break;
        }
    });
</script>
@endpush
@endsection 