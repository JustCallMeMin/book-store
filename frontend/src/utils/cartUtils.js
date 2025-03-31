import Cookies from "js-cookie";

const CART_COOKIE_NAME = "bookstore_cart";
const CART_EXPIRY_DAYS = 7;

export const getCartItems = () => {
    const cartItems = Cookies.get(CART_COOKIE_NAME);
    return cartItems ? JSON.parse(cartItems) : [];
};

const dispatchCartEvent = (cartItems) => {
    window.dispatchEvent(
        new CustomEvent("cartUpdated", {
            detail: { cartItems },
        })
    );
};

export const addToCart = (book) => {
    const currentCart = getCartItems();
    const existingItem = currentCart.find((item) => item.id === book.id);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        currentCart.push({ ...book, quantity: 1 });
    }

    Cookies.set(CART_COOKIE_NAME, JSON.stringify(currentCart), {
        expires: CART_EXPIRY_DAYS,
    });

    // Emit event after updating cart
    dispatchCartEvent(currentCart);
    return currentCart;
};

export const removeFromCart = (bookId) => {
    const currentCart = getCartItems();
    const newCart = currentCart.filter((item) => item.id !== bookId);

    Cookies.set(CART_COOKIE_NAME, JSON.stringify(newCart), {
        expires: CART_EXPIRY_DAYS,
    });

    // Emit event after removing item
    dispatchCartEvent(newCart);
    return newCart;
};

export const updateCartItemQuantity = (bookId, quantity) => {
    const currentCart = getCartItems();
    const item = currentCart.find((item) => item.id === bookId);

    if (item) {
        item.quantity = Math.max(1, quantity);
        Cookies.set(CART_COOKIE_NAME, JSON.stringify(currentCart), {
            expires: CART_EXPIRY_DAYS,
        });

        // Emit event after updating quantity
        dispatchCartEvent(currentCart);
    }

    return currentCart;
};
