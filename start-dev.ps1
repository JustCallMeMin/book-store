# Khởi động Redis trong WSL
Write-Host "Starting Redis in WSL..." -ForegroundColor Green
wsl -d Ubuntu -- sudo service redis-server start

# Kiểm tra Redis
Write-Host "Checking Redis connection..." -ForegroundColor Green
$redisCheck = wsl -d Ubuntu -- redis-cli ping
if ($redisCheck -eq "PONG") {
    Write-Host "Redis is running!" -ForegroundColor Green
} else {
    Write-Host "Redis is not responding!" -ForegroundColor Red
    exit 1
}

# Cài đặt dependencies nếu cần
if (-not (Test-Path "vendor")) {
    Write-Host "Installing PHP dependencies..." -ForegroundColor Green
    composer install
}

# Kiểm tra và tạo file .env
if (-not (Test-Path ".env")) {
    Write-Host "Creating .env file..." -ForegroundColor Green
    Copy-Item ".env.example" ".env"
    php artisan key:generate
}

# Chạy migrations
Write-Host "Running database migrations..." -ForegroundColor Green
php artisan migrate

# Khởi động queue worker
Write-Host "Starting queue worker..." -ForegroundColor Green
Start-Process -NoNewWindow php -ArgumentList "artisan queue:work"

# Khởi động development server
Write-Host "Starting development server..." -ForegroundColor Green
php artisan serve 