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
            return $this->orderService->getProvinces();
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
    public function getDistricts(Request $request, $provinceId): JsonResponse
    {
        try {
            return $this->orderService->getDistricts($provinceId);
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
            return $this->orderService->getWards($districtId);
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
            if (empty($cart['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chưa có sản phẩm',
                ], 404);
            }

            $insurance_value = $cart['final_amount'] ?? 0;
            $result = $this->orderService->calculateDimensions($cart['items']);
            return $this->orderService->calculateShippingFee(
                $insurance_value,
                $to_ward_code,
                $to_district_id,
                $result['weight'],
                $result['length'],
                $result['width'],
                $result['height']
            );
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
        $validatedData = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|regex:/^0[3-9]\d{8}$/',
            'address' => 'required|string',
            'ward' => 'required|string',
            'district' => 'required|string',
            'province' => 'required|string',
        ]);

        return $this->orderService->sendOtp($request->user(), $validatedData);
    }

    /**
     * Xác thực mã OTP
     */
    public function verifyOTP(Request $request): JsonResponse
    {
        $otp = $request->input('otp');
        if (!$otp) {
            return response()->json(['success' => false, 'message' => 'OTP không được để trống'], 400);
        }

        return $this->orderService->verifyOtp($request->user(), $otp);
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
        $orderId = $orderCode . '_' . time();
        $orderInfo = "Thanh toán đơn hàng #$orderCode";
        $requestType = "captureWallet";
        $extraData = base64_encode(json_encode(['orderCode' => $orderCode]));


        $rawHash = "accessKey={$accessKey}&amount={$amount}&extraData={$extraData}&ipnUrl={$ipnUrl}&orderId={$orderId}&orderInfo={$orderInfo}&partnerCode={$partnerCode}&redirectUrl={$redirectUrl}&requestId={$requestId}&requestType={$requestType}";
        $signature = hash_hmac('sha256', $rawHash, $secretKey);

        $payload = [
            'partnerCode' => $partnerCode,
            'accessKey' => $accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
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
    public function momoIpn(Request $request): JsonResponse
    {
        return $this->orderService->processIpn($request->all());
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
        try {
            $orderCode = $request->input('order_code');

            if (!$orderCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã đơn hàng không được để trống'
                ], 400);
            }

            $orderDetailResponse = $this->orderService->getOrderDetail($orderCode);
            $orderDetail = $orderDetailResponse->getData(true);

            if (!$orderDetail || !isset($orderDetail['code']) || $orderDetail['code'] !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => $orderDetail['message'] ?? 'Không thể lấy thông tin đơn hàng',
                    'data' => []
                ], 404);
            }

            // Cập nhật trạng thái đơn hàng nếu có log dữ liệu
            if (!empty($orderDetail['data']['log'])) {
                $this->orderService->updateStatus($orderCode);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết đơn hàng thành công',
                'data' => $orderDetail['data']
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching order details', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
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
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy đơn hàng: " . $e);
            return response()->json(["error" => "Lỗi khi lấy đơn hàng: " . $e], 500);
        }
    }

    /**
     * Lấy danh sách tất cả order
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed|null
     */
    public function getOrderAll(Request $request): JsonResponse
    {
        try {
            $orders = $this->orderService->getOrders();

            return $orders;
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy danh sách đơn hàng: " . $e);
            return response()->json(["error" => "Lỗi khi lấy danh sách đơn hàng: " . $e], 500);
        }
    }

    /**
     * Lấy danh sách order của user
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse|mixed
     */
    public function getOdersByUser(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $orders = $this->orderService->getOrderByUser($user->id);
            return $orders;
        } catch (\Exception $e) {
            Log::error("Lỗi khi lấy danh sách đơn hàng: " . $e);
            return response()->json(["error" => "Lỗi khi lấy danh sách đơn hàng: " . $e], 500);
        }

    }

    public function confirmOrder(Request $request): JsonResponse
    {
        try {
            $orderCode = $request->input('order_code');

            if (!$orderCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã đơn hàng không được để trống'
                ], 400);
            }

            $result = $this->orderService->confirmOrder($orderCode);

            if ($result->status() !== 200) {
                return $result;
            }

            $orderShip = $this->orderService->createOrderShip($orderCode);

            if (!isset($orderShip['code']) || $orderShip['code'] !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tạo đơn giao hàng thất bại'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã tạo đơn giao hàng thành công',
                'data' => $orderShip
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error confirming order', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xác nhận đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}