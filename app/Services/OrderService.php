<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\OrderItem;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Client\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Js;
use Laravel\Pail\ValueObjects\Origin\Console;

class OrderService
{
    /**
     * API Lấy mã Tỉnh/Thành phố
     * @return JsonResponse
     */
    public function getProvinces(): JsonResponse
    {
        try {
            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/master-data/province';
            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN')
            ];

            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if (!$response) {
                throw new \Exception('Không nhận được phản hồi từ API');
            }

            $data = json_decode($response, true);

            if (!isset($data['data'])) {
                throw new \Exception('Dữ liệu trả về không hợp lệ');
            }

            $provinces = collect($data['data'])
                ->filter(fn($d) => ($d['IsEnable'] ?? 0) == 1)
                ->map(fn($d) => [
                    'province_id' => $d['ProvinceID'],
                    'name' => $d['ProvinceName'],
                ])
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'total_items' => count($provinces),
                    'provinces' => $provinces,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting provinces', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách tỉnh/thành phố',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API Lấy mã Quận/Huyện theo mã Tỉnh/Thành phố
     * @param int $provinceId
     * @return JsonResponse
     */
    public function getDistricts($provinceId): JsonResponse
    {
        try {
            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/master-data/district?province_id=' . $provinceId;
            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN')
            ];

            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if (!$response) {
                throw new \Exception('Không nhận được phản hồi từ API');
            }

            $data = json_decode($response, true);

            if (!isset($data['data'])) {
                throw new \Exception('Dữ liệu trả về không hợp lệ');
            }

            $districts = collect($data['data'])
                ->filter(fn($d) => ($d['IsEnable'] ?? 0) == 1)
                ->map(fn($d) => [
                    'district_id' => $d['DistrictID'],
                    'name' => $d['DistrictName'],
                ])
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'total_items' => count($districts),
                    'districts' => $districts,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting districts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách quận/huyện',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * API Lấy mã Phường/Xã theo mã Quận/Huyện
     * @param int $districtId
     * @return JsonResponse
     */
    public function getWards(int $districtId): JsonResponse
    {
        try {
            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/master-data/ward?district_id=' . $districtId;
            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN')
            ];

            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if (!$response) {
                throw new \Exception('Không nhận được phản hồi từ API');
            }

            $data = json_decode($response, true);

            if (!isset($data['data'])) {
                throw new \Exception('Dữ liệu trả về không hợp lệ');
            }

            $wards = collect($data['data'])
                ->filter(fn($d) => ($d['Status'] ?? 0) == 1)
                ->map(fn($d) => [
                    'ward_id' => $d['WardCode'],
                    'ward_name' => $d['WardName'],
                ])
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Lấy danh sách phường/xã thành công',
                'data' => [
                    'total_items' => count($wards),
                    'wards' => $wards,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error getting wards', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách phường/xã',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Tính phí vận chuyển
     *
     */
    public function calculateShippingFee($insurance_value, $to_ward_code, $to_district_id, $weight, $length, $width, $height): JsonResponse
    {
        try {
            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee';
            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN'),
                "ShopId: " . env('GHN_SHOP_ID'),
            ];

            $data = [
                'service_type_id' => 2,
                'insurance_value' => $insurance_value,
                'to_district_id' => (int) $to_district_id,
                'to_ward_code' => $to_ward_code,
                'weight' => $weight,
                'length' => $length,
                'height' => $height,
                'width' => $width,
            ];
            Log::info('GHN Request Data', $data);

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if (!$response) {
                throw new \Exception('Không nhận được phản hồi từ API');
            }

            $data = json_decode($response, true);

            if (!isset($data['data'])) {
                throw new \Exception('Dữ liệu trả về không hợp lệ');
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Tính phí vận chuyển thành công',
                'data' => [
                    'shipping_fee' => $data['data'],
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error calculating shipping fee', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tính phí vận chuyển',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * tìm kiếm đơn hàng theo mã đơn hàng
     * @param string $orderCode
     */
    public function findOrderByCode(string $orderCode): ?Order
    {
        return Order::where('order_code', $orderCode)->first();
    }

    /**
     * Cập nhật trạng thái đã thanh toán
     */
    public function updateStatusPaid(string $orderCode)
    {
        return Order::where('order_code', $orderCode)
            ->update([
                'status' => 'paid',
                'payment_status' => 'paid'
            ]);
    }

    /**
     * Tính chiều cao, chiều rộng, chiều dài, cân nặng
     */
    public function calculateDimensions($items): array
    {
        $quantity = collect($items)->sum('quantity') ?? 0;// Số lượng sản phẩm trong giỏ hàng

        $bookIds = collect($items)->pluck('book_id')->unique();  // Lấy danh sách ID sách từ giỏ hàng
        $books = Book::whereIn('id', $bookIds)->get(); // Lấy thông tin sách từ cơ sở dữ liệu
        $totalPages = $books->sum('page_count'); // Tổng số trang của tất cả các sản phẩm trong giỏ hàng

        // Tính toán kích thước và trọng lượng
        $weight = 50 * $totalPages; // // Giả sử mỗi trang nặng 50 gram
        $length = 30; // Chiều dài 30cm cho mỗi sản phẩm
        $width = 25;  // Chiều rộng 25cm cho mỗi sản phẩm
        $height = (int) ceil(0.01 * $totalPages + 0.1 * $quantity); // Chiều cao 0.01cm cho mỗi trang và 0.05cm cho mỗi tờ bìa mỗi sản phẩm

        return compact('weight', 'height', 'length', 'width');
    }

    /**
     * Summary of sendOtp
     * @param mixed $user
     * @param mixed $data
     * @return JsonResponse|mixed
     */
    public function sendOtp($user, $data): JsonResponse
    {
        try {
            $otp = mt_rand(100000, 999999);
            Redis::set("otp:{$user->id}", $otp);
            Redis::expire("otp:{$user->id}", 300);

            $fullAddress = "{$data['address']}, {$data['ward']}, {$data['district']}, {$data['province']}";
            Mail::to($user->email)->send(new OtpMail($data['name'], $data['phone'], $fullAddress, $otp, $user->email));

            return response()->json([
                'success' => true,
                'message' => 'Mã OTP đã được gửi qua email.',
                'data' => ['email' => $user->email, 'phone' => $data['phone']]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error sending OTP', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Summary of verifyOtp
     * @param mixed $user
     * @param mixed $otp
     * @return JsonResponse|mixed
     */
    public function verifyOtp($user, $otp): JsonResponse
    {
        try {
            $storedOtp = Redis::get("otp:{$user->id}");
            if (!$storedOtp) {
                return response()->json(['success' => false, 'message' => 'Mã OTP đã hết hạn hoặc không tồn tại'], 400);
            }

            if ($otp == $storedOtp) {
                Redis::del("otp:{$user->id}");
                return response()->json(['success' => true, 'message' => 'Xác thực mã OTP thành công'], 200);
            }

            return response()->json(['success' => false, 'message' => 'Mã OTP không đúng'], 400);
        } catch (\Exception $e) {
            Log::error('Error verifying OTP', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi xác thực mã OTP', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Summary of processIpn Momo
     * @param array $data
     * @return JsonResponse|mixed
     */
    public function processIpn(array $data): JsonResponse
    {
        try {
            Log::info("MoMo IPN Received: ", $data);

            // Kiểm tra chữ ký để đảm bảo tính an toàn
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
                Log::warning("MoMo IPN Signature mismatch!");
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
            }

            // Xử lý trạng thái đơn hàng
            if ($data['resultCode'] == 0) {
                Log::info("MoMo IPN - Thành công cho đơn hàng: " . $data['orderId']);

                $extraData = json_decode(base64_decode($data['extraData']), true);
                $orderCode = $extraData['orderCode'] ?? null;

                $order = Order::where('order_code', $orderCode)->first();

                if (!$order) {
                    Log::warning("MoMo IPN - Không tìm thấy đơn hàng: $orderCode");
                    return response()->json(['success' => false, 'message' => 'Order not found'], 404);
                }

                $order->status = "paid";
                $order->payment_status = "paid";
                $order->payment_date = now();
                $order->save();

                Log::info('Cập nhật đơn hàng ' . $order->order_code . ' thành công');
            } else {
                Log::warning("MoMo IPN - Giao dịch thất bại. Mã đơn: {$data['orderId']}, Lý do: {$data['message']}");
            }

            return response()->json(['success' => true, 'message' => 'IPN processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error processing MoMo IPN', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Tạo đơn giao hàng
     */
    public function createOrderShip($order_code)
    {
        try {
            $order = Order::where('order_code', $order_code)->first();
            $items = OrderItem::where('order_id', $order->id)
                ->select('book_id', 'quantity')
                ->get();
            $results = $this->calculateDimensions($items);
            $weight = $results['weight'];
            $height = $results['height'];
            $width = $results['width'];
            $length = $results['length'];

            $insurance_value = $order->total_amount;

            $fullAddress = $order->recipient_address;

            // Tách chuỗi theo dấu phẩy và loại bỏ khoảng trắng thừa
            $parts = array_map('trim', explode(',', $fullAddress));

            // Gán vào các biến tương ứng
            // $address = $parts[0] ?? '';
            $ward = $parts[1] ?? '';
            $district = $parts[2] ?? '';
            $province = $parts[3] ?? '';

            $to_name = $order->recipient_name;
            $to_phone = $order->recipient_phone;

            // Lấy danh sách ID sách
            $bookIds = $items->pluck('book_id');

            // Truy vấn tên sách theo ID
            $books = Book::whereIn('id', $bookIds)->pluck('title', 'id'); // key là book_id, value là title

            // Tạo nội dung đơn hàng
            $content = $items->map(function ($item) use ($books) {
                $title = $books[$item->book_id] ?? 'Không rõ sách';
                return "{$title} (SL: {$item->quantity})";
            })->implode("\n");

            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/create';
            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN'),
                "ShopId: " . env('GHN_SHOP_ID'),
            ];

            $data = [
                'to_name' => $to_name,
                'to_phone' => $to_phone,
                'to_address' => $fullAddress,
                'to_ward_name' => $ward,
                'to_district_name' => $district,
                'to_province_name' => $province,
                'cod_amount' => 0,
                'content' => $content,
                'weight' => $weight,
                'length' => $length,
                'height' => $height,
                'width' => $width,
                'service_type_id' => 2,
                'insurance_value' => (int) $insurance_value,
                'payment_type_id' => 1,
                'required_note' => 'KHONGCHOXEMHANG'
            ];
            Log::info('GHN Request Data', $data);
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            Log::info('GHN Response', ['response' => $response]);
            $data = json_decode($response, true);
            if ($data['code'] == 200) {
                $order->ship_code = $data['data']['order_code'];
                $order->shipping_method = "Car";
                $order->save();
            }

            return collect($data)->toArray();
        } catch (\Exception $e) {
            Log::error('Error creating order ship', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Lấy chi tiết đơn ship
     */
    public function getOrderDetail(string $orderCode): JsonResponse
    {
        try {
            $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/detail';
            $order = Order::where('order_code', $orderCode)->first();

            if (!$order || !$order->ship_code) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc đơn vận chuyển'], 404);
            }

            $headers = [
                "Content-Type: application/json",
                "Token: " . env('GHN_API_TOKEN'),
            ];
            $data = ["order_code" => $order->ship_code];

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            $data = json_decode($response, true);

            if (!isset($data['code']) || $data['code'] != 200) {
                throw new \Exception('Dữ liệu phản hồi không hợp lệ');
            }

            return response()->json(['success' => true, 'data' => $data['data']], 200);
        } catch (\Exception $e) {
            Log::error('Error getting order details', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi lấy chi tiết đơn hàng', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Cập nhật trạng thái vận chuyển
     */
    public function updateStatus(string $orderCode): JsonResponse
    {
        try {
            // Lấy chi tiết đơn hàng để lấy logs
            $orderDetailResponse = $this->getOrderDetail($orderCode);
            $orderDetail = $orderDetailResponse->getData(true);

            if (!$orderDetail || empty($orderDetail['data']['log'])) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy logs vận chuyển'], 404);
            }

            $logs = $orderDetail['data']['log'];
            $latestLog = collect($logs)->sortByDesc('updated_date')->first();

            $order = Order::where('order_code', $orderCode)->first();
            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy đơn hàng'], 404);
            }

            $validStatuses = ['picking', 'picked', 'storing', 'transporting', 'sorting', 'delivering', 'delivered', 'completed', 'cancelled', 'refunded', 'delivery_fail', 'returning', 'returned'];

            if (!in_array($latestLog['status'], $validStatuses)) {
                return response()->json(['success' => false, 'message' => 'Trạng thái không hợp lệ'], 400);
            }

            $order->status = $latestLog['status'];
            if ($order->status === 'picked') {
                $order->shipping_date = Carbon::parse($latestLog['updated_date'])->setTimezone('Asia/Ho_Chi_Minh');
            } elseif ($order->status === 'delivered') {
                $order->delivery_date = Carbon::parse($latestLog['updated_date'])->setTimezone('Asia/Ho_Chi_Minh');
            }
            $order->save();
            return response()->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công','data'=>$order], 200);
        } catch (\Exception $e) {
            Log::error('Error updating order status', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật trạng thái đơn hàng', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Lấy danh sách Order
     */
    public function getOrders(): JsonResponse
    {
        try {
            $orders = Order::all();
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đơn hàng nào',
                    'data' => []
                ], 404);
            }

            $result = $orders->map(function ($order) {
                $order_ship = $order->ship_code ? $this->getOrderDetail($order->order_code)->getData(true) : null;
                if($order->ship_code){
                    $updateResponse = $this->updateStatus($order->order_code);
                    $updatedOrder = data_get($updateResponse->getData(true), 'data');
                }
                return [
                    'order' => $updatedOrder,
                    'logs' => data_get($order_ship, 'data.log', []),
                    'lead_time' => data_get($order_ship, 'data.leadtime'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách đơn hàng thành công',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching orders', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy Order theo order_code
     */
    public function getOrderByCode(string $orderCode): JsonResponse
    {
        try {
            $order = Order::where('order_code', $orderCode)->first();
            if($order->ship_code){
                $updateResponse = $this->updateStatus($order->order_code);
                $order = data_get($updateResponse->getData(true), 'data');
            }
            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            // Lấy thông tin vận chuyển nếu có mã vận chuyển
            $orderShipResponse = $order->ship_code ? $this->getOrderDetail($orderCode)->getData(true) : null;

            if (!$orderShipResponse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn vận chuyển'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy đơn hàng thành công',
                'data' => [
                    "id" => $order->id,
                    "user_id" => $order->user_id,
                    "order_code" => $order->order_code,
                    "ship_code" => $order->ship_code,
                    "recipient_name" => $order->recipient_name,
                    "recipient_email" => $order->recipient_email,
                    "recipient_phone" => $order->recipient_phone,
                    "recipient_address" => $order->recipient_address,
                    "total_amount" => (int) $order->total_amount,
                    "shipping_fee" => (int) $order->shipping_fee,
                    "tax_amount" => $order->tax_amount,
                    "discount_amount" => (int) $order->discount_amount,
                    "final_amount" => (int) $order->final_amount,
                    "order_date" => $order->order_date,
                    "payment_date" => $order->payment_date,
                    "shipping_date" => $order->shipping_date,
                    "delivery_date" => $order->delivery_date,
                    "status" => $order->status,
                    "payment_method" => $order->payment_method,
                    "payment_status" => $order->payment_status,
                    "shipping_method" => $order->shipping_method,
                    "notes" => $order->note,
                    "created_at" => Carbon::parse($order->created_at)->format('d-m-Y H:i'),
                    "updated_at" => Carbon::parse($order->updated_at)->format('d-m-Y H:i'),
                    "logs" => data_get($orderShipResponse, 'data.log', []),
                    "lead_time" => Carbon::parse(data_get($orderShipResponse, 'data.leadtime'))->format('d-m-Y H:i'),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching order by code', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * get orders by user
     */
    public function getOrderByUser(int $userId): JsonResponse
    {
        try {
            $orders = Order::where('user_id', $userId)->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy danh sách đơn hàng',
                    'data' => []
                ], 404);
            }

            $result = $orders->map(function ($order) {
                $orderShipResponse = $order->ship_code ? $this->getOrderDetail($order->order_code)->getData(true) : null;
                if($order->ship_code){
                    $updateResponse = $this->updateStatus($order->order_code);
                    $order = data_get($updateResponse->getData(true), 'data');
                }
                return [
                    'order' => $order,
                    'logs' => data_get($orderShipResponse, 'data.log', []),
                    'lead_time' => data_get($orderShipResponse, 'data.leadtime'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách đơn hàng thành công',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching orders by user', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * admin xác nhận đơn hàng
     */
    public function confirmOrder(string $orderCode): JsonResponse
    {
        try {
            $order = Order::where('order_code', $orderCode)->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy đơn hàng'
                ], 404);
            }

            if ($order->status !== "paid") {
                return response()->json([
                    'success' => false,
                    'message' => 'Đơn hàng không hợp lệ để xác nhận'
                ], 400);
            }

            $order->status = "confirmed";
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Đã xác nhận đơn hàng thành công'
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