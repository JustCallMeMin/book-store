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
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Support\Jsonable;

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
            if (!$cart['items']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có sản phẩm',
                ], 404);
            }
            $insurance_value = $cart['final_amount'] ?? 0;
            $result = $this->orderService->calculateDimensions($cart['items']);
            $shippingFee = $this->orderService->calculateShippingFee($insurance_value, $to_ward_code, $to_district_id, $result['weight'], $result['length'], $result['width'], $result['height']);
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

        $amount = (int) $order->final_amount;
        $requestId = $orderCode . '_' . time();
        $orderInfo = "Thanh toán đơn hàng #$orderCode";
        $requestType = "captureWallet";
        $extraData = base64_encode(json_encode(['orderCode' => $orderCode]));


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
        $data = $request->all();

        Log::info("MoMo IPN Received: ", $data);

        // Kiểm tra chữ ký để đảm bảo an toàn
        $signature = $data['signature'] ?? '';
        $rawHash = "accessKey=" . env('MOMO_ACCESS_KEY') .
            "&amount=" . $data['amount'] .
            "&extraData=" . $data['extraData'] .
            "&message=" . $data['message'] .
            "&orderId=" . $data['orderId'] .
            "&orderInfo=" . $data['orderInfo'] .
            "&orderType=" . $data['orderType'] .
            "&partnerCode=" . $data['partnerCode'] .
            "&payType=" . $data['payType'] .
            "&requestId=" . $data['requestId'] .
            "&responseTime=" . $data['responseTime'] .
            "&resultCode=" . $data['resultCode'] .
            "&transId=" . $data['transId'];

        $expectedSignature = hash_hmac('sha256', $rawHash, env('MOMO_SECRET_KEY'));

        if ($signature !== $expectedSignature) {
            Log::info("MoMo IPN Signature mismatch!");
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Cập nhật trạng thái đơn hàng
        if ($data['resultCode'] == 0) {
            // Thanh toán thành công
            Log::info("MoMo IPN - Thành công cho đơn hàng: " . $data['orderId']);

            // cập nhật order status trong DB tại đây
            // Giải mã extraData
            $extraData = json_decode(base64_decode($data['extraData']), true);
            $orderCode = $extraData['orderCode'] ?? null;

            // Tìm đơn hàng theo orderCode
            $order = Order::where('order_code', $orderCode)->first();

            if (!$order) {
                Log::warning("MoMo IPN - Không tìm thấy đơn hàng: $orderCode");
                return response()->json(['message' => 'Order not found'], 404);
            }
            $order->status = "paid";
            $order->payment_status = "paid";
            $order->payment_date = now();
            $order->save();
            Log::info('Cập nhật đơn hàng ' . $order->order_code . ' thành công');

        } else {
            Log::info("MoMo IPN - Giao dịch thất bại. Mã đơn: {$data['orderId']}, Lý do: {$data['message']}");
        }

        // Trả về HTTP 204 OK
        return response()->json("", 204);
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

    /**
     * Tạo đơn vận chuyển
     */
    public function orderShip(Request $request): JsonResponse
    {
        try {
            $order_code = $request->input('order_code');

            if (!$order_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã đơn hàng không được để trống'
                ], 400);
            }

            $result = $this->orderService->createOrderShip($order_code);
            if ($result)
                return response()->json([
                    'success' => true,
                    'message' => 'Tạo đơn giao hàng thành công',
                    'data' => $result
                ]);
            else
                return response()->json([
                    'success' => false,
                    'message' => 'Tạo đơn giao hàng thất bại',
                    'data' => $result
                ]);
        } catch (\Exception $e) {
            Log::error('Lỗi khi tạo đơn giao hàng: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo đơn giao hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary of getOrderShipDetail
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed
     */
    public function getOrderShipDetail(Request $request): JsonResponse
    {
        $orderCode = $request->input('order_code');

        $result = $this->orderService->getOrderDetail($orderCode);

        if ($result && isset($result['code']) && $result['code'] == 200) {
            $status = $this->orderService->updateStatus($orderCode, $result['data']['log']);
            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết đơn hàng thành công',
                'data' => $result['data']
            ]);

        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Không thể lấy thông tin đơn hàng',
            'data' => []
        ]);
    }

    /**
     * Lấy Order theo order_code
     */
    public function getOrderByCode(Request $request): JsonResponse
    {
        try {
            $order_code = $request->input("order_code");
            $order = $this->orderService->getOrderByCode($order_code);
            return $order;
        }
        catch(\Exception $e){
            Log::error("Lỗi khi lấy đơn hàng: ".$e);
            return response()->json(["error"=>"Lỗi khi lấy đơn hàng: ".$e],500);
        }
    }

    public function getOrderAll(Request $request): JsonResponse
    {
        try{
            $orders = $this->orderService->getOrders();

        return $orders;
        }catch(\Exception $e){
            Log::error("Lỗi khi lấy danh sách đơn hàng: ".$e);
            return response()->json(["error"=>"Lỗi khi lấy danh sách đơn hàng: ".$e],500);
        }
    }
}