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
     * @return array
     */
    public function getProvinces(): array
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

            $data = json_decode($response, true);

            return collect($data['data'] ?? [])
                ->filter(fn($d) => ($d['IsEnable'] ?? 0) == 1)
                ->map(fn($d) => [
                    'province_id' => $d['ProvinceID'],
                    'name' => $d['ProvinceName'],
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting provinces', ['error' => $e->getMessage()]);
            return [];
        }
    }


    /**
     * API Lấy mã Quận/Huyện theo mã Tỉnh/Thành phố
     * @param int $provinceId
     * @return array
     */
    public function getDistricts($provinceId): array
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

            $data = json_decode($response, true);

            return collect($data['data'] ?? [])
                ->filter(fn($d) => ($d['IsEnable'] ?? 0) == 1)
                ->map(fn($d) => [
                    'district_id' => $d['DistrictID'],
                    'name' => $d['DistrictName'],
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting districts', ['error' => $e->getMessage()]);
            return [];
        }

    }

    /**
     * API Lấy mã Phường/Xã theo mã Quận/Huyện
     * @param int $districtId
     * @return array
     */
    public function getWards(int $districtId): array
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

            $data = json_decode($response, true);

            return collect($data['data'] ?? [])
                ->filter(fn($d) => ($d['Status'] ?? 0) == 1)
                ->map(fn($d) => [
                    'ward_id' => $d['WardCode'],
                    'ward_name' => $d['WardName'],
                ])
                ->values()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error getting wards', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Tính phí vận chuyển
     *
     */
    public function calculateShippingFee($insurance_value, $to_ward_code, $to_district_id, $weight, $length, $width, $height): array
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
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($data),
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            $data = json_decode($response, true);

            return collect($data['data'])->toArray();
        } catch (\Exception $e) {
            Log::error('Error calculating shipping fee', ['error' => $e->getMessage()]);
            return [];
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
     * thanh toán đơn hàng
     */

    /**
     * update đã thanh toán
     */
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
    public function getOrderDetail(string $orderCode): ?array
    {
        $url = 'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/detail';
        $order = Order::where('order_code', $orderCode)->first();
        Log::info('Order: ' . $order->order_code . ' order_ship: ' . $order->ship_code);
        if (!$order) {
            return null;
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



        $data = json_decode($response, true); // Chuyển JSON string thành mảng

        if (isset($data['code']) && $data['code'] == 200) {
            return collect($data)->toArray(); // Trả về chi tiết đơn hàng
        }

        return null;
    }

    /**
     * Cập nhật trạng thái vận chuyển
     */
    public function updateStatus($orderCode, $logs): ?JsonResponse
    {
        $order = Order::where('order_code', $orderCode)->first();
        if (!$order) {
            return null;
        }
        $latestLog = collect($logs)
            ->sortByDesc('updated_date')
            ->first();

        //'picking','picked','storing','transporting','sorting','delivering','delivered','completed','cancelled','refunded','delivery_fail','returning','returned'
        $status = ['picking', 'picked', 'storing', 'transporting', 'sorting', 'delivering', 'delivered', 'completed', 'cancelled', 'refunded', 'delivery_fail', 'returning', 'returned'];
        if (in_array($latestLog['status'], $status)) {
            $order->status = $latestLog['status'];
            if ($order->status === 'picked') {
                $carbon = Carbon::parse($latestLog['updated_date'])->setTimezone('Asia/Ho_Chi_Minh');
                $order->shipping_date = $carbon;
            } elseif ($order->status === 'delivered') {
                $carbon = Carbon::parse($latestLog['updated_date'])->setTimezone('Asia/Ho_Chi_Minh');
                $order->delivery_date = $carbon;
            }
            $order->save();
            return response()->json([
                'message' => "Cập nhật trạng thái thành công"
            ]);
        }
        return null;
    }

    /**
     * Lấy danh sách Order
     */
    public function getOrders(): ?JsonResponse
    {
        $orders = Order::all();
        $result = [];

        foreach ($orders as $order) {
            $order_ship = $this->getOrderDetail($order->order_code);

            $result[] = [
                'order' => $order,
                'logs' => $order_ship['data']['log'] ?? null,
                'lead_time' => $order_ship['data']['leadtime'] ?? null,
            ];
        }

        return response()->json([
            "message" => "Lấy danh sách thành công",
            'data' => $result
        ], 200);
    }

    /**
     * Lấy Order theo order_code
     */
    public function getOrderByCode($order_code): ?JsonResponse
    {
        $order = Order::where('order_code', $order_code)->first();
        $order_ship = $this->getOrderDetail($order_code);
        if (!$order) {
            return response()->json(["error: Lỗi không tìm thấy đơn hàng"], 404);
        }
        if (!$order_ship) {
            return response()->json(["error: Lỗi không tìm thấy đơn vận chuyển"], 404);
        }
        return response()->json([
            "id" => $order->id,
            "user_id" => $order->user_id,
            "order_code" => $order->order_code,
            "ship_code" => $order->shipcode,
            "recipient_name" => $order->recipient_name,
            "recipient_email" => $order->recipient_email,
            "recipient_phone" => $order->recipient_phone,
            "recipient_address" => $order->recipient_address,
            "total_amount" => (int) $order->total_amount,
            "shipping_fee" => (int) $order->shipping_fee,
            "tax_amount" => $order->tax_amount,
            "discount_amount" => (int) $order->dicount_amount,
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
            "logs" => $order_ship["data"]["log"], // mảng ghi lại trạng thái đơn ship
            "lead_time" => Carbon::parse($order_ship["data"]["leadtime"])->format('d-m-Y H:i'), //thời gian giao hàng dự kiến
        ], 200);
    }

    /**
     * get orders by user
     */
    public function getOrderByUser($user_id): JsonResponse
    {

        $orders = Order::where('user_id', $user_id)->get();
        if (!$orders) {
            return response()->json([
                "message" => "Không tìm thấy danh sách đơn hàng"
            ], 404);
        }
        $result = [];
        foreach ($orders as $order) {
            $order_ship = $this->getOrderDetail($order->order_code);

            $result[] = [
                'order' => $order,
                'logs' => $order_ship['data']['log'] ?? null,
                'lead_time' => $order_ship['data']['leadtime'] ?? null,
            ];
        }

        return response()->json([
            "message" => "Lấy danh sách thành công",
            'data' => $result
        ], 200);
    }

    /**
     * admin xác nhận đơn hàng
     */
    public function confirmOrder($order_code): JsonResponse
    {
        $order = Order::where('order_code', $order_code)->first();
        if ($order->status == "paid") {
            $order->status = "confirmed";
            $order->save();
            return response()->json([
                "message" => "Đã xác nhận đơn hàng",
            ], 200);
        }
        return response()->json([
            "message"=>"Đơn hàng không hợp lệ"
        ],404);
    }
}