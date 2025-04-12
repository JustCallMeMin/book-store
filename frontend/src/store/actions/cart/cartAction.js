import customAxios from "src/utils/customAxios";

const {
    ADD_TO_CART_FAILURE,
    ADD_TO_CART_REQUEST,
    ADD_TO_CART_SUCCESS,
    GET_CART_ITEMS_FAILURE,
    GET_CART_ITEMS_REQUEST,
    GET_CART_ITEMS_SUCCESS,
    UPDATE_CART_ITEM_QUANTITY_FAILURE,
    UPDATE_CART_ITEM_QUANTITY_REQUEST,
    UPDATE_CART_ITEM_QUANTITY_SUCCESS,
    CLEAR_CART_FAILURE,
    CLEAR_CART_REQUEST,
    CLEAR_CART_SUCCESS,
} = require("./../cart/cartTypes");

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const getCartItemsRequest = () => ({
    type: GET_CART_ITEMS_REQUEST,
});
const getCartItemsSuccess = (cartItems) => ({
    type: GET_CART_ITEMS_SUCCESS,
    payload: cartItems,
});
const getCartItemsFailure = (error) => ({
    type: GET_CART_ITEMS_FAILURE,
    payload: error,
});

export const getCartItems = () => {
    return async (dispatch) => {
        dispatch(getCartItemsRequest());
        try {
            const response = await customAxios.get(`${BASE_URL}cart`);
            dispatch(getCartItemsSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, getCartItemsFailure, error);
        }
    };
};

const addToCartRequest = () => ({
    type: ADD_TO_CART_REQUEST,
});
const addToCartSuccess = (cartItem) => ({
    type: ADD_TO_CART_SUCCESS,
    payload: cartItem,
});
const addToCartFailure = (error) => ({
    type: ADD_TO_CART_FAILURE,
    payload: error,
});
export const addToCart = (book_id, quantity) => {
    return async (dispatch) => {
        dispatch(addToCartRequest());
        try {
            const response = await customAxios.post(`${BASE_URL}cart`, {
                book_id,
                quantity,
            });
            dispatch(addToCartSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, addToCartFailure, error);
        }
    };
};

const updateCartItemQuantityRequest = () => ({
    type: UPDATE_CART_ITEM_QUANTITY_REQUEST,
});
const updateCartItemQuantitySuccess = (cartItem) => ({
    type: UPDATE_CART_ITEM_QUANTITY_SUCCESS,
    payload: cartItem,
});
const updateCartItemQuantityFailure = (error) => ({
    type: UPDATE_CART_ITEM_QUANTITY_FAILURE,
    payload: error,
});
export const updateCartItemQuantity = (bookId, quantity) => {
    return async (dispatch) => {
        dispatch(updateCartItemQuantityRequest());
        try {
            const response = await customAxios.put(
                `${BASE_URL}cart/${bookId}`,
                {
                    quantity,
                }
            );
            dispatch(updateCartItemQuantitySuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, updateCartItemQuantityFailure, error);
        }
    };
};

const clearCartRequest = () => ({
    type: CLEAR_CART_REQUEST,
});
const clearCartSuccess = () => ({
    type: CLEAR_CART_SUCCESS,
});
const clearCartFailure = (error) => ({
    type: CLEAR_CART_FAILURE,
    payload: error,
});
export const clearCart = () => {
    return async (dispatch) => {
        dispatch(clearCartRequest());
        try {
            await customAxios.delete(`${BASE_URL}cart`);
            dispatch(clearCartSuccess());
        } catch (error) {
            handleApiError(dispatch, clearCartFailure, error);
        }
    };
};
