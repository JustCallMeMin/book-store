<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\OrderService;
use App\Services\RedisCartService;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\AuthService;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;

class OrderController extends Controller
{
    protected $orderService;
    protected $cartService;
    protected $authService;

    public function __construct(OrderService $orderService, RedisCartService $cartService, AuthService $authService)
    {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
        $this->authService = $authService;
    }

    /**
     * Lấy danh sách tỉnh/thành phố 
     */
    public function getProvinces(Request $request): JsonResponse
    {
        try {
            $provinces = $this->orderService->getProvinces();
            return response()->json([
                'status' => 200,
                'data' => [
                    'total_items' => $provinces['total_items'] ?? 0,
                    'provinces' => $provinces ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting provinces', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách tỉnh/thành phố',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách quận/huyện theo mã tỉnh/thành phố
     */
    public function getDistricts(Request $request, $provinceId)
    {
        try {
            $districts = $this->orderService->getDistricts($provinceId);
            return response()->json([
                'status' => true,
                'data' => [
                    'total_items' => $districts['total_items'] ?? 0,
                    'districts' => $districts ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting districts', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách quận/huyện',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Lấy danh sách phường/xã theo mã quận/huyện
     */
    public function getWards(Request $request, int $districtId): JsonResponse
    {
        try {
            $wards = $this->orderService->getWards($districtId);
            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách phường/xã thành công',
                'data' => $wards
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting wards', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách phường/xã',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tính phí vận chuyển
     */
    public function calculateShippingFee(Request $request, $to_district_id, $to_ward_code): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart();
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng không tồn tại',
                ], 404);
            }
            $insurance_value = $cart['final_amount'] ?? 0;
            $quantity = collect($cart['items'])->sum('quantity') ?? 0;// Số lượng sản phẩm trong giỏ hàng

            $bookIds = collect($cart['items'])->pluck('book_id')->unique();  // Lấy danh sách ID sách từ giỏ hàng
            $books = Book::whereIn('id', $bookIds)->get(); // Lấy thông tin sách từ cơ sở dữ liệu
            $totalPages = $books->sum('page_count'); // Tổng số trang của tất cả các sản phẩm trong giỏ hàng

            $weight = 50 * $totalPages; // Giả sử mỗi trang nặng 50g
            $length = 30; // Chiều dài 30cm cho mỗi sản phẩm
            $width = 25; // Chiều rộng 25cm cho mỗi sản phẩm
            $height = (int) ceil(0.01 * $totalPages + 0.1 * $quantity);// Chiều cao 0.01cm cho mỗi trang và 0.05cm cho mỗi tờ bìa mỗi sản phẩm
            $shippingFee = $this->orderService->calculateShippingFee($insurance_value, $to_ward_code, $to_district_id, $weight, $length, $width, $height);
            $result = response()->json([
                'success' => true,
                'message' => 'Tính phí vận chuyển thành công',
                'data' => [
                    'shipping_fee' => $shippingFee,
                ]
            ]);
            if ($shippingFee == null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tính phí vận chuyển',
                ], 500);
            }
            return $result;
        } catch (\Exception $e) {
            Log::error('Error calculating shipping fee', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tính phí vận chuyển',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gửi OTP xác thực thông tin người nhận hàng qua email
     */
    public function sendOTP(Request $request): JsonResponse
    {
        try {
            $name = $request->input('name');
            $phone = $request->input('phone');
            $address = $request->input('address');
            $ward = $request->input('ward');
            $district = $request->input('district');
            $province = $request->input('province');

            $user = $request->user();
            $to_email = $user['email'];
            if (!preg_match("/^0[3-9]\d{8}$/", $phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số điện thoại không hợp lệ',
                ], 400);
            }
            $randomNumber = mt_rand(100000, 999999); // Tạo mã OTP ngẫu nhiên
            Redis::set((string) "otp:" . $user['id'], $randomNumber); // Lưu mã OTP vào Redis 
            Redis::expire((string) "otp:" . $phone, 300); // Đặt thời gian sống cho mã OTP là 5 phút
            // Gửi email cho user...
            $str_district = $address . ', ' . $ward . ', ' . $district . ', ' . $province;
            Mail::to($to_email)->send(new OtpMail($name, $phone, $str_district, $randomNumber, $to_email));


            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi qua email của bạn thành công. Vui lòng kiểm tra email của bạn.',
                'data' => [
                    'email' => $to_email,
                    'otp' => $randomNumber,
                    'phone' => $phone,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error validating phone number', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra ',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Xác thực mã OTP
     */
    public function verifyOTP(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $otp = $request->input('otp');
            // Kiểm tra OTP nhập vào hợp lệ
            if (!$otp) {
                throw new Error('otp is required');
            }
            // Lấy OTP từ Redis
            $storedOtp = Redis::get('otp:' . $user['id']);
            // Kiểm tra nếu OTP không tồn tại hoặc đã hết hạn

            if (!$storedOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã OTP đã hết hạn hoặc không tồn tại.',
                ], 400);
            }

            // Kiểm tra nếu OTP nhập vào đúng
            if ($otp == $storedOtp) {
                // Xóa OTP khỏi Redis sau khi xác thực thành công
                Redis::del('otp:' . $user['id']);

                return response()->json([
                    'success' => true,
                    'message' => 'Xác thực mã OTP thành công.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Mã OTP không đúng.',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error verifying OTP', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xác thực mã OTP.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Checkout MOMO
     */
    public function checkout(Request $request): JsonResponse
    {
        $user = $request->user();
        $cart = $this->cartService->getCart();
        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy giỏ hàng.'
            ]);
        }

        $orderCode = $request->input('order_code');
        $order = $this->orderService->findOrderByCode($orderCode);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng.'
            ]);
        }

        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = env('MOMO_PARTNER_CODE');
        $accessKey = env('MOMO_ACCESS_KEY');
        $secretKey = env('MOMO_SECRET_KEY');
        $redirectUrl = env('MOMO_REDIRECT_URL');
        $ipnUrl = env('MOMO_IPN_URL');

        $amount = (float) $order->final_amount;
        $requestId = $orderCode . '_' . time();
        $orderInfo = "Thanh toán đơn hàng #$orderCode";
        $requestType = "captureWallet";
        $extraData = $orderCode;

        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderCode}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderCode,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $response = json_decode($result, true);

        if ($statusCode === 200 && isset($response['payUrl'])) {
            return response()->json([
                'success' => true,
                'payUrl' => $response['payUrl']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Lỗi tạo thanh toán MoMo.',
            'response' => $response
        ]);
    }


    /**
     * Lấy thông tin đơn hàng momo trả về
     */
    public function momoIpn(Request $request)
    {
        try {
            http_response_code(204); // MoMo expects 204

            $data = $request->all();
            Log::info('MoMo IPN Received:', $data);

            $accessKey = env('MOMO_ACCESS_KEY');
            $secretKey = env('MOMO_SECRET_KEY');

            $rawHash = "accessKey={$accessKey}"
                . "&amount={$data['amount']}"
                . "&extraData={$data['extraData']}"
                . "&message={$data['message']}"
                . "&orderId={$data['orderId']}"
                . "&orderInfo={$data['orderInfo']}"
                . "&orderType={$data['orderType']}"
                . "&partnerCode={$data['partnerCode']}"
                . "&payType={$data['payType']}"
                . "&requestId={$data['requestId']}"
                . "&responseTime={$data['responseTime']}"
                . "&resultCode={$data['resultCode']}"
                . "&transId={$data['transId']}";

            $generatedSignature = hash_hmac('sha256', $rawHash, $secretKey);

            if ($generatedSignature !== $data['signature']) {
                Log::error('MoMo IPN - Chữ ký không hợp lệ', [
                    'generated' => $generatedSignature,
                    'received' => $data['signature'],
                    'raw' => $rawHash,
                ]);
                return response('', 204);
            }

            $orderCode = $data['extraData'] ?? null;
            if (!$orderCode) {
                Log::error('MoMo IPN - Không có mã đơn hàng trong extraData');
                return response('', 204);
            }

            $order = $this->orderService->findOrderByCode($orderCode);
            if (!$order) {
                Log::error("MoMo IPN - Đơn hàng không tồn tại: {$orderCode}");
                return response('', 204);
            }

            if ($data['resultCode'] == 0) {
                $this->orderService->updateStatusPaid($orderCode);
                Log::info("MoMo IPN - Thanh toán thành công đơn hàng: {$orderCode}");
            } else {
                Log::warning("MoMo IPN - Giao dịch thất bại. Mã đơn: {$data['orderId']}, Lý do: {$data['message']}");
            }

            return response('', 204);
        } catch (\Throwable $e) {
            Log::error('MoMo IPN - Lỗi xử lý IPN', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('', 204); // MoMo requires 204 regardless of error
        }
    }



    /**
     * Tạo đơn hàng
     */
    public function addOrder(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:15',
            'recipient_address' => 'required|string|max:255',
            'province_name' => 'required|string|max:255',
            'district_name' => 'required|string|max:255',
            'ward_name' => 'required|string|max:255',
            'shipping_fee' => 'required|numeric',
            'notes' => 'nullable|string|max:255',
        ]);
        try {
            $cart = $this->cartService->getCart();
            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng không tồn tại',
                ], 404);
            }

            $orderData = [
                'recipient_name' => $request->input('recipient_name'),
                'recipient_email' => $request->user()->email,
                'recipient_phone' => $request->input('recipient_phone'),
                'recipient_address' => $request->input('recipient_address') . ', ' . $request->input('ward_name') . ', ' . $request->input('district_name') . ', ' . $request->input('province_name'),
                'shipping_fee' => $request->input('shipping_fee'),
                'payment_method' => 'MoMo',
                'notes' => $request->input('notes'),
            ];

            $order = $this->cartService->convertToOrder($orderData);

            if ($order) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo đơn hàng thành công',
                    'data' => $order,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tạo đơn hàng',
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error creating order', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}