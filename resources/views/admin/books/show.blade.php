@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Chi Tiết Sách</h2>
                    <div>
                        <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">Quay Lại</a>
                        <a href="{{ route('admin.books.edit', $book->id) }}" class="btn btn-primary">Sửa</a>
                    </div>
                </div>
                
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            @if($book->cover_image)
                                <img src="{{ $book->cover_image }}" alt="{{ $book->title }}" class="img-fluid mb-3">
                            @else
                                <div class="placeholder-image bg-light d-flex align-items-center justify-content-center mb-3" style="height: 200px; width: 100%;">
                                    <span class="text-muted">Không có ảnh</span>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            <h3>{{ $book->title }}</h3>
                            <p>
                                <strong>Tác giả:</strong>
                                @if($book->authors->count() > 0)
                                    {{ $book->authors->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-muted">Không có</span>
                                @endif
                            </p>
                            
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0">Thông Tin Tồn Kho</h5>
                                        <p class="mb-0">
                                            <strong>Số lượng:</strong> 
                                            <span class="badge {{ $book->quantity_in_stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $book->quantity_in_stock }}
                                            </span>
                                        </p>
                                    </div>
                                    <a href="{{ route('admin.books.edit-stock', $book->id) }}" class="btn btn-warning">Cập Nhật Tồn Kho</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h4>Thông Tin Chi Tiết</h4>
                            <table class="table">
                                <tr>
                                    <th>ID:</th>
                                    <td>{{ $book->id }}</td>
                                </tr>
                                <tr>
                                    <th>Gutendex ID:</th>
                                    <td>{{ $book->gutendex_id ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>ISBN:</th>
                                    <td>{{ $book->isbn ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Nhà xuất bản:</th>
                                    <td>{{ $book->publisher ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Năm xuất bản:</th>
                                    <td>{{ $book->publication_year ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Số trang:</th>
                                    <td>{{ $book->page_count ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h4>Thông Tin Giá</h4>
                            <table class="table">
                                <tr>
                                    <th>Giá bán:</th>
                                    <td>{{ number_format($book->price, 0, ',', '.') }} đ</td>
                                </tr>
                                <tr>
                                    <th>Giá gốc:</th>
                                    <td>{{ $book->original_price ? number_format($book->original_price, 0, ',', '.') . ' đ' : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Giảm giá:</th>
                                    <td>{{ $book->discount_percent }}%</td>
                                </tr>
                                <tr>
                                    <th>Ghi chú giá:</th>
                                    <td>{{ $book->price_note ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Nổi bật:</th>
                                    <td>{{ $book->is_featured ? 'Có' : 'Không' }}</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>{{ $book->is_active ? 'Đang bán' : 'Ngừng bán' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if($book->description)
                    <div class="mt-4">
                        <h4>Mô Tả</h4>
                        <div class="card">
                            <div class="card-body">
                                {{ $book->description }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 