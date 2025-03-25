@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Lịch Sử Tồn Kho - {{ $book->title }}</h2>
                    <div>
                        <a href="{{ route('admin.books.show', $book->id) }}" class="btn btn-secondary">Chi Tiết Sách</a>
                        <a href="{{ route('admin.books.edit-stock', $book->id) }}" class="btn btn-primary">Cập Nhật Tồn Kho</a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5>Thông Tin Tồn Kho Hiện Tại</h5>
                        <p class="mb-0">
                            <strong>Số lượng hiện tại:</strong> 
                            <span class="badge {{ $book->quantity_in_stock > 0 ? 'bg-success' : 'bg-danger' }}">
                                {{ $book->quantity_in_stock }}
                            </span>
                        </p>
                    </div>
                    
                    <div class="table-responsive mt-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Thời Gian</th>
                                    <th>Số Lượng Trước</th>
                                    <th>Số Lượng Sau</th>
                                    <th>Thay Đổi</th>
                                    <th>Hành Động</th>
                                    <th>Người Thực Hiện</th>
                                    <th>Lý Do</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($histories as $history)
                                <tr>
                                    <td>{{ $history->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td>{{ $history->previous_quantity }}</td>
                                    <td>{{ $history->new_quantity }}</td>
                                    <td>
                                        @if ($history->adjustment > 0)
                                            <span class="text-success">+{{ $history->adjustment }}</span>
                                        @elseif ($history->adjustment < 0)
                                            <span class="text-danger">{{ $history->adjustment }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($history->action)
                                            @case('set')
                                                <span class="badge bg-primary">Đặt giá trị</span>
                                                @break
                                            @case('add')
                                                <span class="badge bg-success">Thêm</span>
                                                @break
                                            @case('subtract')
                                                <span class="badge bg-warning">Trừ</span>
                                                @break
                                            @case('order')
                                                <span class="badge bg-info">Đơn hàng</span>
                                                @break
                                            @case('import')
                                                <span class="badge bg-secondary">Nhập hàng</span>
                                                @break
                                            @case('return')
                                                <span class="badge bg-danger">Trả hàng</span>
                                                @break
                                            @default
                                                <span class="badge bg-dark">Khác</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $history->user ? $history->user->name : 'Hệ thống' }}</td>
                                    <td>{{ $history->reason ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Không có dữ liệu lịch sử.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $histories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 