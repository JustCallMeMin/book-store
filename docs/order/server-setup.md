# Thiết lập server
## 1. Tải ngrok : https://ngrok.com/downloads/windows?tab=download
## 2. Cài đặt
## 3. Chạy file exe
## 4. Chạy lệnh: ngrok config add-authtoken 2vXNjiBvKQD5jwwGGOJuais9U8T_MxRy7psHmV7XnXwVefY3
## 5. Chạy lệnh: ngrok http 8000
## 6. Hiện ra link, copy link, đến MOMO_IPN_URL file .env, dán cho nó vào thêm /api/orders/momo-ipn (vd: MOMO_IPN_URL = https://ngrok.com/info/kubecon-2025-ngrok-user-meetup/api/orders/momo-ipn)