<x-mail::message>
# Xác thực liên kết tài khoản {{ ucfirst($verification->provider) }}

Xin chào {{ $user->name }},

Chúng tôi nhận thấy bạn đang cố gắng liên kết tài khoản email của bạn với tài khoản {{ $verification->provider }}.

Để xác nhận việc liên kết này, vui lòng nhấn vào nút bên dưới:

<x-mail::button :url="$verificationUrl">
Xác thực liên kết
</x-mail::button>

Nếu bạn không yêu cầu liên kết này, bạn có thể bỏ qua email này.

Liên kết xác thực này sẽ hết hạn sau 60 phút.

Trân trọng,<br>
{{ config('app.name') }}

<x-mail::subcopy>
Nếu bạn gặp vấn đề với nút "Xác thực liên kết", hãy sao chép và dán URL sau vào trình duyệt web của bạn: {{ $verificationUrl }}
</x-mail::subcopy>
</x-mail::message> 