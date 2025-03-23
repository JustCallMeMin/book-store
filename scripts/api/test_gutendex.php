<?php

/**
 * Script kiểm tra Gutendex API và lưu dữ liệu mẫu
 * 
 * Sử dụng: php scripts/api/test_gutendex.php [endpoint] [id]
 * - endpoint: books (mặc định) | book
 * - id: ID của sách khi endpoint là book
 * 
 * Ví dụ:
 * - php scripts/api/test_gutendex.php           # Lấy danh sách sách
 * - php scripts/api/test_gutendex.php book 84   # Lấy thông tin của sách có ID 84
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Thư mục lưu output
$outputDir = __DIR__ . '/output';

// Đảm bảo thư mục output tồn tại
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Xác định endpoint từ tham số dòng lệnh
$endpoint = isset($argv[1]) ? $argv[1] : 'books';
$id = isset($argv[2]) ? $argv[2] : null;

// Xây dựng URL
$baseUrl = 'https://gutendex.com';
$url = $baseUrl . '/' . $endpoint;
if ($endpoint === 'book' && $id) {
    $url = $baseUrl . '/books/' . $id;
}

try {
    echo "Đang gửi request đến: $url\n";
    
    // Lấy dữ liệu từ Gutendex API
    $response = file_get_contents($url);
    
    // Decode JSON
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON error: " . json_last_error_msg());
    }
    
    // Xác định tên file từ endpoint
    $filename = $endpoint;
    if ($endpoint === 'book' && $id) {
        $filename .= "_$id";
    }
    
    // Lưu kết quả vào file
    $outputFile = "$outputDir/{$filename}_sample.json";
    file_put_contents($outputFile, json_encode($data, JSON_PRETTY_PRINT));
    
    // In ra thông báo
    echo "Đã lưu dữ liệu vào file $outputFile\n\n";
    
    // Phân tích và hiển thị thông tin
    if ($endpoint === 'books') {
        // Trường hợp danh sách sách
        if (isset($data['results']) && !empty($data['results'])) {
            $firstBook = $data['results'][0];
            echo "Thông tin sách đầu tiên:\n";
            echo "-------------------------\n";
            echo "ID: " . $firstBook['id'] . "\n";
            echo "Tiêu đề: " . $firstBook['title'] . "\n";
            echo "Tác giả: " . implode(', ', array_column($firstBook['authors'], 'name')) . "\n\n";
            
            echo "Cấu trúc dữ liệu sách:\n";
            echo "---------------------\n";
            foreach (array_keys($firstBook) as $key) {
                echo "- $key: " . gettype($firstBook[$key]);
                if (is_array($firstBook[$key])) {
                    echo " (" . count($firstBook[$key]) . " phần tử)";
                    if (!empty($firstBook[$key]) && is_array($firstBook[$key][0])) {
                        echo " [" . implode(', ', array_keys($firstBook[$key][0])) . "]";
                    }
                }
                echo "\n";
            }
            
            echo "\nCấu trúc tác giả:\n";
            echo "---------------\n";
            if (!empty($firstBook['authors'])) {
                foreach (array_keys($firstBook['authors'][0]) as $key) {
                    echo "- $key: " . gettype($firstBook['authors'][0][$key]) . "\n";
                }
            }
        }
    } elseif ($endpoint === 'book' && $id) {
        // Trường hợp chi tiết sách
        echo "Chi tiết sách (ID: $id):\n";
        echo "-------------------------\n";
        echo "ID: " . $data['id'] . "\n";
        echo "Tiêu đề: " . $data['title'] . "\n";
        echo "Tác giả: " . implode(', ', array_column($data['authors'], 'name')) . "\n\n";
        
        echo "Cấu trúc dữ liệu sách:\n";
        echo "---------------------\n";
        foreach (array_keys($data) as $key) {
            echo "- $key: " . gettype($data[$key]);
            if (is_array($data[$key])) {
                echo " (" . count($data[$key]) . " phần tử)";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
} 