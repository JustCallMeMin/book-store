<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Redis;
use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cart;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
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


}