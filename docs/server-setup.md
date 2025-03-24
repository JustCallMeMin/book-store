# Hướng dẫn Khởi tạo và Chạy Server Book Store

Tài liệu này hướng dẫn chi tiết các bước để thiết lập, cấu hình và khởi động server cho dự án Book Store.

## Yêu cầu hệ thống

- PHP >= 8.1
- Composer
- MySQL/MariaDB >= 5.7 hoặc PostgreSQL
- Nodejs >= 16 và NPM (cho việc compile assets)
- Git
- WSL2 (Windows Subsystem for Linux 2) với Ubuntu
- Redis (chạy trên WSL2)

## 1. Cài đặt dự án

### 1.1. Clone repository

```bash
git clone https://github.com/your-username/book-store.git
cd book-store
```

### 1.2. Cài đặt dependencies PHP

```bash
composer install
```

### 1.3. Cài đặt dependencies Javascript (nếu có)

```bash
npm install
npm run build
```

## 2. Cấu hình môi trường

### 2.1. Tạo file .env

```bash
cp .env.example .env
```

### 2.2. Cấu hình database trong file .env

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookstore
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 2.3. Cấu hình Redis và Queue

```
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=172.25.185.153  # Địa chỉ IP của WSL2 (thay đổi theo hệ thống)
REDIS_PASSWORD=Tnhmninh33
REDIS_PORT=6379
```

Để lấy địa chỉ IP của WSL2:
```bash
wsl -d Ubuntu -- hostname -I
```

### 2.4. Cấu hình API keys (nếu cần)

```
GOOGLE_BOOKS_API_KEY=your_api_key
```

### 2.5. Tạo application key

```bash
php artisan key:generate
```

## 3. Thiết lập Database

### 3.1. Tạo database

```bash
# MySQL
mysql -u root -p
CREATE DATABASE bookstore;
exit;

# PostgreSQL
createdb bookstore
```

### 3.2. Chạy migrations

```bash
php artisan migrate
```

### 3.3. Seed dữ liệu mẫu (nếu cần)

```bash
php artisan db:seed
```

## 4. Thiết lập Redis trên WSL2

### 4.1. Cài đặt Redis

```bash
# Trong WSL2 Ubuntu
sudo apt update
sudo apt install redis-server
```

### 4.2. Cấu hình Redis

Sửa file `/etc/redis/redis.conf`:
```bash
# Cho phép kết nối từ bên ngoài
sudo sed -i 's/bind 127.0.0.1/bind 0.0.0.0/' /etc/redis/redis.conf

# Thiết lập mật khẩu
sudo bash -c 'echo "requirepass Redis@123" >> /etc/redis/redis.conf'
```

Cập nhật file `.env`:
```
REDIS_CLIENT=predis
REDIS_HOST=172.25.185.153  # Địa chỉ IP của WSL2 (thay đổi theo hệ thống)
REDIS_PASSWORD=Redis@123
REDIS_PORT=6379
```

### 4.3. Khởi động Redis

```bash
sudo service redis-server restart
```

### 4.4. Kiểm tra Redis

```bash
# Kiểm tra kết nối với mật khẩu
redis-cli -h 172.25.185.153 -a "Tnhminh33" ping  # Phải trả về PONG
```

## 5. Khởi động Server

### 5.1. Development (môi trường phát triển)

Cách 1: Sử dụng script tự động
```powershell
.\start-dev.ps1
```

Cách 2: Khởi động thủ công, mở ít nhất 3 terminal khác nhau:

**Terminal 1: Redis trong WSL2**
```bash
wsl -d Ubuntu -- sudo service redis-server start
```

**Terminal 2: Queue Worker**
```bash
php artisan queue:work
```

**Terminal 3: Web Server**
```bash
php artisan serve
```

Bạn có thể truy cập ứng dụng tại: http://localhost:8000

### 5.2. Production (môi trường sản xuất)

#### Web Server

Cấu hình Nginx hoặc Apache để trỏ đến thư mục `public` của dự án.

**Mẫu cấu hình Nginx:**
```nginx
server {
    listen 80;
    server_name bookstore.example.com;
    root /path/to/book-store/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Queue Worker với Supervisor

Cài đặt supervisor:
```bash
sudo apt-get install supervisor
```

Tạo file cấu hình `/etc/supervisor/conf.d/bookstore-worker.conf`:
```ini
[program:bookstore-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/book-store/artisan queue:work --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/book-store/storage/logs/worker.log
stopwaitsecs=3600
```

Kích hoạt và khởi động supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bookstore-worker:*
```

#### Cấu hình Cron cho Scheduler

Thêm vào crontab:
```bash
* * * * * cd /path/to/book-store && php artisan schedule:run >> /dev/null 2>&1
```

## 6. Kiểm tra và Giám sát

### 6.1. Kiểm tra trạng thái server web

Truy cập: http://localhost:8000 (development) hoặc domain của bạn (production)

### 6.2. Kiểm tra trạng thái queue

```bash
php artisan queue:monitor
```

### 6.3. Dọn dẹp queue định kỳ

```bash
php artisan queue:cleanup
```

## 7. Xử lý sự cố

### 7.1. Lỗi kết nối Redis

- Kiểm tra Redis đang chạy trong WSL2:
```bash
wsl -d Ubuntu -- sudo service redis-server status
```

- Kiểm tra địa chỉ IP của WSL2:
```bash
wsl -d Ubuntu -- hostname -I
```

- Kiểm tra kết nối từ Windows đến Redis:
```bash
wsl -d Ubuntu -- redis-cli -h [IP_WSL2] ping
```

- Đảm bảo file .env có địa chỉ IP WSL2 chính xác

### 7.2. Lỗi kết nối database

- Kiểm tra thông tin trong file .env
- Đảm bảo database server đang chạy
- Kiểm tra quyền truy cập của user

### 7.3. Lỗi queue

- Khởi động lại queue worker: `php artisan queue:restart`
- Kiểm tra logs tại: `storage/logs/laravel.log`
- Dọn dẹp các jobs bị treo: `php artisan queue:cleanup`

### 7.4. Lỗi permissions

Đảm bảo các thư mục sau có quyền ghi:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Trên server Linux
```

## 8. Script hỗ trợ

### 8.1. start-dev.ps1

Script PowerShell để tự động hóa quá trình khởi động development server:
- Khởi động Redis trong WSL2
- Kiểm tra kết nối Redis
- Cài đặt dependencies nếu cần
- Tạo và cấu hình file .env
- Chạy migrations
- Khởi động queue worker
- Khởi động development server

### 8.2. Dừng các services

```powershell
# Dừng PHP development server và queue worker
taskkill /F /IM php.exe

# Dừng Redis trong WSL2
wsl -d Ubuntu -- sudo service redis-server stop
```

## 9. Cấu hình nâng cao

### 9.1. Horizontal Scaling

Để mở rộng quy mô theo chiều ngang, hãy xem xét:
- Cấu hình load balancer
- Redis cho cache và queue
- Database replication

### 9.2. Bảo mật

- Đảm bảo HTTPS được bật
- Cấu hình các HTTP headers bảo mật
- Cài đặt rate limiting 