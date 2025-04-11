<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Events\Logout;
use App\Services\CartService;
use Illuminate\Support\Facades\Log;

class SaveCartOnLogout
{
    protected $cartService;

    /**
     * Create the event listener.
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            // Get the user who is logging out
            $user = $event->user;
            
            if (!$user) {
                Log::info('No user found in logout event');
                return;
            }
            
            // Get the cart ID before it's cleared
            $cartId = $this->cartService->getCartId();
            
            // Transfer the cart data to the database
            if ($cartId) {
                Log::info('Saving cart on logout', [
                    'user_id' => $user->id,
                    'cart_id' => $cartId
                ]);
                
                // If the cartService is RedisCartService, use the transferToDatabase method
                if (method_exists($this->cartService, 'transferToDatabase')) {
                    $cart = $this->cartService->transferToDatabase($cartId);
                    
                    if ($cart) {
                        Log::info('Cart saved to database on logout', [
                            'user_id' => $user->id,
                            'cart_id' => $cart->id,
                            'total_items' => $cart->total_items
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error saving cart on logout', [
                'user_id' => $event->user ? $event->user->id : null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
