<?php

namespace App\Http\Middleware;

use App\Services\RedisCartService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class MergeCartItems
{
    protected $cartService;

    public function __construct(RedisCartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Nếu user đăng nhập và có session cart_id
        if ($request->user() && Session::has('cart_id')) {
            $guestCartId = Session::get('cart_id');
            $userCartId = 'cart:' . $request->user()->id;
            
            try {
                // Merge giỏ hàng khách vào giỏ hàng user
                $this->cartService->mergeGuestCart($guestCartId, $userCartId);
                
                // Xóa cart_id trong session
                Session::forget('cart_id');
                
                Log::info('Cart merged after login', [
                    'user_id' => $request->user()->id,
                    'guest_cart_id' => $guestCartId
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to merge cart after login', [
                    'user_id' => $request->user()->id,
                    'guest_cart_id' => $guestCartId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $next($request);
    }
} 