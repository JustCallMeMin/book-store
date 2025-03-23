# Hướng dẫn quản lý Queue trong Book Store

Dự án Book Store sử dụng Laravel Queue để xử lý các tác vụ nặng như nhập dữ liệu sách từ Gutendex và Google Books API. Hướng dẫn này giải thích cách quản lý và tối ưu hóa hệ thống queue để tránh tiêu tốn tài nguyên.

## Tổng quan về Queue

Dự án sử dụng Queue cho các tác vụ sau:
- Import sách từ Gutendex API
- Import sách từ Google Books API
- Các tác vụ xử lý dài khác

## Chạy Queue Worker

### Cách thủ công

```bash
# Chạy queue worker cơ bản
php artisan queue:work

# Chạy queue worker với timeout dài hơn (phù hợp cho các tác vụ import)
php artisan queue:work-long --timeout=3600
```

### Sử dụng Supervisor (khuyến nghị cho môi trường production)

Tạo file cấu hình supervisor tại `/etc/supervisor/conf.d/bookstore-worker.conf`:

```ini
[program:bookstore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
stopwaitsecs=3600
```

Sau đó, cập nhật supervisor:
```bash
supervisorctl reread
supervisorctl update
supervisorctl start bookstore-worker:*
```

## Quản lý Queue để tránh tốn tài nguyên

### 1. Sử dụng lệnh dọn dẹp tự động

Dự án đã tích hợp lệnh dọn dẹp queue tự động:

```bash
# Dọn dẹp queue với các thông số mặc định
php artisan queue:cleanup

# Tùy chỉnh thông số
php artisan queue:cleanup --days=7 --timeout=120 --queue=default
```

Các tham số:
- **days**: Số ngày để giữ lại các failed jobs (mặc định: 1)
- **timeout**: Thời gian (phút) để xác định job bị treo (mặc định: 60)
- **queue**: Queue cần dọn dẹp (mặc định: default)

### 2. Lịch trình tự động

Các tác vụ sau đã được lên lịch tự động trong `app/Console/Kernel.php`:
- Dọn dẹp queue mỗi giờ
- Khởi động lại queue workers mỗi ngày để tránh rò rỉ bộ nhớ

Đảm bảo cron đang chạy để scheduler Laravel hoạt động:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Tốt nhất quản lý queue

- **Sử dụng timeout phù hợp**: Các tác vụ import sách có thể mất nhiều thời gian, hãy thiết lập timeout đủ lớn.
- **Giới hạn số lần thử lại**: Đã thiết lập là 3 lần. Điều này ngăn jobs lỗi liên tục được thử lại vô hạn.
- **Giới hạn bộ nhớ**: Workers được giới hạn ở 1024MB để tránh sử dụng quá nhiều RAM.
- **Dọn dẹp regularly**: Sử dụng lệnh `queue:cleanup` định kỳ để xóa các jobs bị treo hoặc lỗi.

## Xử lý sự cố

### Jobs bị treo hoặc không hoàn thành

```bash
# Kiểm tra jobs trong queue
php artisan queue:monitor

# Dọn dẹp các jobs bị treo
php artisan queue:cleanup --timeout=30

# Khởi động lại queue workers
php artisan queue:restart
```

### Khắc phục sự cố rò rỉ bộ nhớ

Nếu các queue workers tiêu thụ quá nhiều bộ nhớ:

```bash
# Khởi động lại queue workers
php artisan queue:restart

# Hoặc khởi động lại supervisor nếu đang sử dụng
supervisorctl restart bookstore-worker:*
```

## Quy ước về Job trong dự án

1. Tất cả các Jobs phải implement phương thức `failed()` để xử lý trường hợp job thất bại sau tất cả các lần thử lại.
2. Sử dụng `middleware()` và `backoff()` để tránh các job trùng lặp và cấu hình thời gian retry.
3. Luôn ghi nhật ký kết quả của job vào database (ImportLog).

## Thông tin thêm

Đọc thêm về Laravel Queue tại: [Laravel Queue Documentation](https://laravel.com/docs/10.x/queues) 