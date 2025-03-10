#  Authentication Documentation - Book Store API

*(Version: 1.0 - Updated: 10/03/2025)*

## Yêu Cầu Chức Năng

| Mã chức năng | Chức năng                 | Mô tả                                          |
| ------------ | ------------------------- | ---------------------------------------------- |
| **AUTH01**   | Đăng ký                   | Người dùng tạo tài khoản với email & mật khẩu  |
| **AUTH02**   | Đăng nhập                 | Xác thực bằng email & mật khẩu, trả về JWT     |
| **AUTH03**   | "Remember Me"             | Hỗ trợ đăng nhập tự động bằng `remember_token` |
| **AUTH04**   | Làm mới Token             | Cung cấp API refresh token khi token hết hạn   |
| **AUTH05**   | Đăng xuất                 | Xóa token khi đăng xuất                        |
| **AUTH06**   | Quên mật khẩu             | Gửi email đặt lại mật khẩu                     |
| **AUTH07**   | Xác thực người dùng       | Middleware bảo vệ API theo `auth:api`          |
| **AUTH08**   | Cập nhật hồ sơ            | Người dùng cập nhật thông tin cá nhân          |
| **AUTH09**   | Đổi mật khẩu              | Người dùng thay đổi mật khẩu                   |
| **AUTH10**   | Xác thực Remember Token   | Kiểm tra Remember Token hợp lệ                 |
| **AUTH11**   | Gửi OTP                   | Gửi OTP qua email để đặt lại mật khẩu          |
| **AUTH12**   | Đặt lại mật khẩu bằng OTP | Xác thực OTP và đặt lại mật khẩu               |
