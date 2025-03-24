# Hướng dẫn sử dụng bộ lọc sách trong Book Store API

## Giới thiệu

API của Book Store cung cấp khả năng lọc sách linh hoạt với nhiều tiêu chí khác nhau. Tài liệu này mô tả cách sử dụng các tham số filter trong API GET `/api/gutendex/books`.

## Tham số filter có sẵn

| Tham số | Kiểu dữ liệu | Mô tả | Ví dụ |
|---------|--------------|-------|-------|
| `search` | string | Tìm kiếm theo tên sách hoặc tên tác giả | `?search=adventure` |
| `category` | string/integer | Lọc theo tên thể loại hoặc ID thể loại | `?category=fiction` hoặc `?category=1` |
| `author_id` | integer | Lọc theo ID tác giả | `?author_id=1` |
| `language` | string | Lọc theo ngôn ngữ (mã ISO) | `?language=en` |
| `is_featured` | boolean | Lọc sách nổi bật | `?is_featured=true` |
| `is_active` | boolean | Lọc sách đang hoạt động | `?is_active=true` |
| `price_min` | number | Giá tối thiểu | `?price_min=10` |
| `price_max` | number | Giá tối đa | `?price_max=50` |
| `published_year_min` | integer | Năm xuất bản tối thiểu | `?published_year_min=1900` |
| `published_year_max` | integer | Năm xuất bản tối đa | `?published_year_max=2023` |
| `sort_by` | string | Sắp xếp theo trường | `?sort_by=download_count` |
| `sort_direction` | string | Hướng sắp xếp (asc/desc) | `?sort_direction=desc` |
| `per_page` | integer | Số lượng kết quả trên mỗi trang | `?per_page=15` |
| `page` | integer | Số trang hiện tại | `?page=1` |

## Các trường có thể sắp xếp

API hỗ trợ sắp xếp theo các trường sau:
- `id` (mặc định)
- `title` (tiêu đề sách)
- `price` (giá)
- `published_date` (ngày xuất bản)
- `created_at` (ngày tạo trong hệ thống)
- `download_count` (số lượt tải)

## Ví dụ request đầy đủ

```
GET /api/gutendex/books?search=adventure&category=fiction&author_id=1&language=en&is_featured=true&is_active=true&price_min=10&price_max=50&published_year_min=1900&published_year_max=2023&sort_by=download_count&sort_direction=desc&per_page=10&page=1
```

## Cấu trúc response

```json
{
  "status": 200,
  "data": {
    "books": [...],
    "pagination": {
      "total": 100,
      "per_page": 10,
      "current_page": 1,
      "last_page": 10
    },
    "filters": {
      "search": "adventure",
      "category": "fiction",
      "author_id": 1,
      "language": "en",
      "is_featured": true,
      "is_active": true,
      "price_min": 10,
      "price_max": 50,
      "published_year_min": 1900,
      "published_year_max": 2023,
      "sort_by": "download_count",
      "sort_direction": "desc"
    }
  }
}
```

## Best Practices

1. **Kết hợp nhiều filter**: Bạn có thể kết hợp nhiều tham số để lọc chính xác hơn.
   
2. **Phân trang**: Luôn sử dụng `per_page` và `page` để tránh tải quá nhiều dữ liệu cùng lúc.

3. **Sắp xếp hợp lý**: Sử dụng `sort_by` và `sort_direction` để hiển thị dữ liệu theo thứ tự phù hợp với trường hợp sử dụng.

4. **Sử dụng cache**: API này đã được cache tự động trong 10 phút, giúp tăng tốc cho các truy vấn lặp lại.

## Giới hạn và lưu ý

- Số lượng kết quả tối đa trên mỗi trang là 100.
- Giá trị boolean như `is_featured` và `is_active` nên được truyền dưới dạng "true" hoặc "false".
- Để tìm kiếm nhiều ngôn ngữ, hãy sử dụng nhiều request khác nhau.
- Cache sẽ tự động bị xóa khi có thay đổi dữ liệu như thêm/sửa/xóa sách. 