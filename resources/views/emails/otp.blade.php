<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h2 {
            color: #007bff;
        }
        ul {
            padding-left: 20px;
        }
        .otp {
            background-color: #f0f8ff;
            padding: 15px;
            border: 1px dashed #007bff;
            font-size: 20px;
            text-align: center;
            margin-top: 20px;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📦 Xác nhận thông tin người nhận</h2>
        <p>Vui lòng kiểm tra lại các thông tin dưới đây:</p>
        <ul>
            <li><strong>Tên người nhận:</strong> {{ $name }}</li>
            <li><strong>Số điện thoại:</strong> {{ $phone }}</li>
            <li><strong>Địa chỉ:</strong> {{ $address }}</li>
        </ul>

        <p>Mã OTP để xác thực đơn hàng của bạn là:</p>
        <div class="otp">
            {{ $otp }}
        </div>

        <p style="margin-top: 30px;">Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
    </div>
</body>
</html>
