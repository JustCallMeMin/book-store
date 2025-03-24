# Redis Caching trong Book Store API

## Giới thiệu

Tài liệu này mô tả việc triển khai Redis Cache trong Book Store API để cải thiện hiệu suất và khả năng mở rộng của hệ thống. Redis cache được sử dụng để lưu trữ kết quả của các API calls phổ biến, giảm tải cho database và cải thiện thời gian phản hồi.

## Các endpoint có Redis cache

| Endpoint | Cache Time | Cache Key | Mô tả |
|----------|------------|-----------|-------|
| GET /api/gutendex/books | 10 phút | books:list:search:{search}:category:{category}:page:{page}:perPage:{perPage} | Danh sách sách với tìm kiếm và phân trang |
| GET /api/gutendex/books/{id} | 30 phút | books:detail:{id} | Chi tiết một cuốn sách |
| GET /api/gutendex/authors | 60 phút | authors:all | Danh sách tác giả |
| GET /api/gutendex/categories | 60 phút | categories:all | Danh sách thể loại |
| GET /api/gutendex/suggestions | 30 phút | suggestions:{query} | Gợi ý tìm kiếm cho sách, tác giả, thể loại |

## Invalidation Cache Strategy

Cache sẽ tự động được xóa trong các trường hợp sau:

1. **Thêm sách mới**: Xóa cache danh sách sách, suggestions
2. **Cập nhật sách**: Xóa cache chi tiết sách, danh sách sách, suggestions
3. **Xóa sách**: Xóa cache chi tiết sách, danh sách sách, suggestions
4. **Thay đổi tác giả**: Xóa cache danh sách tác giả, danh sách sách, suggestions
5. **Thay đổi thể loại**: Xóa cache danh sách thể loại, danh sách sách, suggestions

## API để quản lý cache

Có một endpoint dành riêng cho admin để quản lý cache:

- **DELETE /api/gutendex/suggestions/clear**: Xóa cache suggestions
  - Yêu cầu quyền admin
  - Authorization: Bearer token

## Cấu hình Redis

Redis được cấu hình trong file `.env`:

```
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=predis

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

## Chức năng Autocomplete

Hệ thống hỗ trợ tính năng autocomplete/suggestions thông qua API endpoint `/api/gutendex/suggestions?q={query}`.

Endpoint này trả về:
- Danh sách 5 sách phù hợp nhất
- Danh sách 3 tác giả phù hợp nhất
- Danh sách 3 thể loại phù hợp nhất

Kết quả được cache trong 30 phút, giúp giảm tải cho database và tăng tốc đáng kể cho các truy vấn phổ biến.

## Tối ưu hóa với Redis Queue

Hệ thống cũng sử dụng Redis làm queue driver cho các tác vụ nặng như import sách. Điều này giúp:
1. Xử lý công việc bất đồng bộ
2. Tránh timeout cho người dùng
3. Có khả năng mở rộng với nhiều worker

## Testing

Sử dụng Postman collection để test hiệu năng Redis cache:
1. Gửi request đầu tiên và ghi lại thời gian phản hồi
2. Gửi request thứ hai cùng tham số và so sánh thời gian (cached)
3. Xóa cache và gửi request lần thứ ba để xác nhận invalidation hoạt động đúng 