# 📦 Book Store – Order & Shipping Flow Documentation

## 🧾 Tổng quan
Tài liệu này mô tả quy trình đặt hàng và vận chuyển trong hệ thống Book Store, bao gồm các bước từ lúc người dùng nhập thông tin nhận hàng đến khi tạo đơn vận chuyển qua GHN và theo dõi trạng thái.

## 🚀 Quy trình chi tiết

### 1. Nhập thông tin người nhận
Người dùng nhập:
- **name**
- **phone**


### 2. Lựa chọn địa chỉ giao hàng
#### 2.1. Gọi API lấy danh sách tỉnh/thành phố
- **Request:** `GET /api/ghn/provinces`
- Người dùng chọn 1 tỉnh thành từ danh sách trả về.

#### 2.2. Gọi API lấy danh sách quận/huyện theo tỉnh
- **Request:** `GET /api/ghn/districts?province_id=`
- Người dùng chọn 1 quận/huyện từ danh sách trả về.

#### 2.3. Gọi API lấy danh sách phường/xã theo quận
- **Request:** `GET /api/ghn/wards?district_id=`
- Người dùng chọn 1 phường/xã từ danh sách trả về.

#### 2.4. Nhập địa chỉ cụ thể
Người dùng nhập thêm:
- **Số nhà**
- **Tên đường**

⚠️ **Không kiểm tra chính xác địa chỉ cụ thể – chỉ kiểm tra không để trống.**

### 3. Tính phí vận chuyển
- **Request:** `POST /api/ghn/calculate-fee/{district_id}/{ward_code}`

**Phản hồi:** Trả về phí vận chuyển dự kiến.

### 4. Xác thực thông tin bằng OTP
#### 4.1. Gửi OTP qua email
- **Request:** `POST /api/otp/send`

#### 4.2. Nhập và xác thực mã OTP
- **Request:** `POST /api/otp/verify`


### 5. Tạo đơn hàng
- **Request:** `POST /api/orders`
- **Nội dung bao gồm:**
  - Thông tin người nhận
  - Địa chỉ giao hàng
  - Danh sách sản phẩm
  - Phí ship

✅ **Trạng thái đơn khởi tạo là pending.**

### 6. Thanh toán đơn hàng
- **Nếu Chuyển khoản/MoMo:**
  - Chuyển hướng người dùng đến cổng thanh toán.
  - Sau khi thanh toán xong, nhận callback từ phía MoMo để cập nhật trạng thái đơn hàng.

### 7. Tạo đơn vận chuyển (GHN)
- **Request:** `POST /api/ghn/create-order`
- Chỉ thực hiện nếu đơn hàng đã được thanh toán.

✅ **Gửi kèm order_code, địa chỉ nhận, địa chỉ giao, danh sách hàng hóa...**

### 8. Lấy chi tiết đơn vận chuyển
- **Request:** `GET /api/ghn/order-detail/{ship_code}`
- **Phản hồi:** 
  - Mã đơn GHN
  - Trạng thái hiện tại (picking, delivering, delivered, ...)
  - Lịch sử trạng thái (log)
  - Ngày giao hàng dự kiến, ngày giao thực tế...


## 🔍 Tham khảo nhanh
- 📬 **Postman Collection:** [Link Postman](https://www.postman.com/phatkk/workspace/book-store/folder/34154359-ebe6a1e7-4200-45fb-8660-4eeb651b48a3)
- ⏰ **Múi giờ xử lý ngày:** Asia/Ho_Chi_Minh

