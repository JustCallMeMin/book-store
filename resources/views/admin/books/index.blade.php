@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Quản Lý Sách</h2>
                    <a href="{{ route('admin.books.create') }}" class="btn btn-primary">Thêm Sách Mới</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tiêu đề</th>
                                    <th>Tác giả</th>
                                    <th>Giá</th>
                                    <th>Tồn kho</th>
                                    <th style="width: 200px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($books as $book)
                                <tr>
                                    <td>{{ $book->id }}</td>
                                    <td>{{ $book->title }}</td>
                                    <td>
                                        @if($book->authors->count() > 0)
                                            {{ $book->authors->pluck('name')->join(', ') }}
                                        @else
                                            <span class="text-muted">Không có</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($book->price, 0, ',', '.') }} đ</td>
                                    <td>
                                        <span class="badge {{ $book->quantity_in_stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $book->quantity_in_stock }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.books.show', $book->id) }}" class="btn btn-sm btn-info">Chi tiết</a>
                                            <a href="{{ route('admin.books.edit', $book->id) }}" class="btn btn-sm btn-primary">Sửa</a>
                                            <a href="{{ route('admin.books.edit-stock', $book->id) }}" class="btn btn-sm btn-warning">Tồn kho</a>
                                            <form action="{{ route('admin.books.destroy', $book->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa sách này?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Không có sách nào.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $books->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 