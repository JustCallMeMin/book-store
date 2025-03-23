# Postman Collections for Book Store API

Thư mục này chứa các collection Postman để testing API của Book Store. Bạn có thể dùng chúng để kiểm tra các endpoints một cách thủ công hoặc tự động hóa quy trình testing.

## Các Collections

1. **Book Store API** (`book-store.postman_collection.json`): Collection chính chứa tất cả các API endpoints của hệ thống.
2. **Book Store API - Automation Flow** (`automate-flow.postman_collection.json`): Collection tự động hóa quy trình testing từ đăng ký, đăng nhập, tìm kiếm sách, import sách, và đăng xuất.

## Các Scripts Tự Động Hóa

### Pre-request Scripts

Các scripts được chạy trước mỗi request để chuẩn bị dữ liệu:

- **Tạo user ngẫu nhiên**: Tự động tạo email ngẫu nhiên cho việc đăng ký tài khoản testing.
- **Sử dụng biến môi trường**: Tự động sử dụng các biến môi trường như `baseUrl`, `authToken`.
- **Xác thực token**: Kiểm tra thời hạn của token và tự động làm mới nếu cần.

### Test Scripts

Các scripts chạy sau mỗi request để kiểm tra kết quả:

- **Kiểm tra status code**: Đảm bảo API trả về mã trạng thái đúng (200, 201, 404...).
- **Lưu thông tin**: Tự động lưu các giá trị quan trọng (token, book_id) vào biến môi trường.
- **Xác thực dữ liệu**: Kiểm tra cấu trúc và nội dung của response JSON.

## Cách Sử Dụng

### Thiết Lập Ban Đầu

1. Mở Postman và import các file collection và environment.
2. Chọn môi trường "Book Store Local" từ dropdown góc trên bên phải.
3. Đảm bảo Book Store API đang chạy tại `localhost:8000`.

### Sử Dụng Thủ Công

1. Dùng collection "Book Store API" để gọi các endpoints riêng lẻ.
2. Login trước để lấy token xác thực.
3. Các request sau đó sẽ tự động sử dụng token đã lưu.

### Sử Dụng Automation Flow

1. Chọn collection "Book Store API - Automation Flow".
2. Sử dụng tính năng "Collection Runner" của Postman.
3. Chọn tất cả các requests và nhấn "Run" để thực hiện toàn bộ quy trình testing.

## Quy Trình Testing Tự Động

Quy trình tự động hóa thực hiện các bước sau:

1. **Setup**:
   - Đăng ký tài khoản mới với email ngẫu nhiên
   - Đăng nhập và lưu token

2. **Book Management Flow**:
   - Tìm kiếm sách trong Gutendex
   - Import sách từ Gutendex vào hệ thống
   - Tìm kiếm sách trong Google Books
   - Import sách từ Google Books
   - Kiểm tra thông tin sách đã import

3. **Cleanup**:
   - Xóa sách đã import
   - Đăng xuất và dọn dẹp biến môi trường

## Biến Environment

Các biến được sử dụng trong collections:

- `baseUrl`: URL cơ sở của API (mặc định: http://localhost:8000)
- `authToken`: Token xác thực, được tự động lưu sau khi đăng nhập
- `email` và `password`: Thông tin đăng nhập
- `gutendexBookId`: ID của sách từ Gutendex
- `googleBookId`: ID của sách từ Google Books
- `importedBookId`: ID của sách đã import vào hệ thống

## Examples

Ngoài ra, file `combined_examples.json` chứa các ví dụ về data kết hợp từ cả Gutendex và Google Books, hữu ích cho việc kiểm tra cấu trúc dữ liệu. 