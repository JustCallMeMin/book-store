import customAxios from "../../../utils/customAxios";
import {
    GET_PROVINCES_REQUEST,
    GET_PROVINCES_SUCCESS,
    GET_PROVINCES_FAILURE,
    GET_DISTRICTS_REQUEST,
    GET_DISTRICTS_SUCCESS,
    GET_DISTRICTS_FAILURE,
    GET_WARDS_REQUEST,
    GET_WARDS_SUCCESS,
    GET_WARDS_FAILURE,
    SEND_OTP_REQUEST,
    SEND_OTP_SUCCESS,
    SEND_OTP_FAILURE,
    VERIFY_OTP_REQUEST,
    VERIFY_OTP_SUCCESS,
    VERIFY_OTP_FAILURE,
    GET_SHIPPING_FEE_REQUEST,
    GET_SHIPPING_FEE_SUCCESS,
    GET_SHIPPING_FEE_FAILURE,
    CREATE_ORDER_REQUEST,
    CREATE_ORDER_SUCCESS,
    CREATE_ORDER_FAILURE,
    INITIATE_MOMO_PAYMENT_REQUEST,
    INITIATE_MOMO_PAYMENT_SUCCESS,
    INITIATE_MOMO_PAYMENT_FAILURE,
    GET_USER_ORDERS_REQUEST,
    GET_USER_ORDERS_SUCCESS,
    GET_USER_ORDERS_FAILURE,
    GET_ORDERS_REQUEST,
    GET_ORDERS_SUCCESS,
    GET_ORDERS_FAILURE,
    CONFIRM_ORDER_REQUEST,
    CONFIRM_ORDER_SUCCESS,
    CONFIRM_ORDER_FAILURE,
} from "./orderTypes";

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const getProvincesRequest = () => ({ type: GET_PROVINCES_REQUEST });
const getProvincesSuccess = (provinces) => ({
    type: GET_PROVINCES_SUCCESS,
    payload: provinces,
});
const getProvincesFailure = (error) => ({
    type: GET_PROVINCES_FAILURE,
    payload: error,
});

export const getProvinces = () => async (dispatch) => {
    dispatch(getProvincesRequest());
    const apiUrl = BASE_URL + "orders/get-provinces";
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(getProvincesSuccess(res.data.data.provinces));
        } else {
            dispatch(
                getProvincesFailure(
                    res.data.message || "Failed to fetch provinces"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, getProvincesFailure, e);
    }
};

const getDistrictsRequest = () => ({ type: GET_DISTRICTS_REQUEST });
const getDistrictsSuccess = (districts) => ({
    type: GET_DISTRICTS_SUCCESS,
    payload: districts,
});
const getDistrictsFailure = (error) => ({
    type: GET_DISTRICTS_FAILURE,
    payload: error,
});

export const getDistricts = (provinceId) => async (dispatch) => {
    dispatch(getDistrictsRequest());
    const apiUrl = BASE_URL + `orders/get-districts/${provinceId}`;
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(getDistrictsSuccess(res.data.data.districts));
        } else {
            dispatch(
                getDistrictsFailure(
                    res.data.message || "Failed to fetch districts"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, getDistrictsFailure, e);
    }
};

const getWardsRequest = () => ({ type: GET_WARDS_REQUEST });
const getWardsSuccess = (wards) => ({
    type: GET_WARDS_SUCCESS,
    payload: wards,
});
const getWardsFailure = (error) => ({
    type: GET_WARDS_FAILURE,
    payload: error,
});

export const getWards = (districtId) => async (dispatch) => {
    dispatch(getWardsRequest());
    const apiUrl = BASE_URL + `orders/get-wards/${districtId}`;
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(getWardsSuccess(res.data.data));
        } else {
            dispatch(
                getWardsFailure(res.data.message || "Failed to fetch wards")
            );
        }
    } catch (e) {
        handleApiError(dispatch, getWardsFailure, e);
    }
};

const sendOtpRequest = () => ({ type: SEND_OTP_REQUEST });
const sendOtpSuccess = () => ({
    type: SEND_OTP_SUCCESS,
});
const sendOtpFailure = (error) => ({
    type: SEND_OTP_FAILURE,
    payload: error,
});

export const sendOtp = (orderDetails) => async (dispatch) => {
    dispatch(sendOtpRequest());
    const apiUrl = BASE_URL + "orders/send-otp";
    try {
        const res = await customAxios.post(apiUrl, orderDetails, {
            withCredentials: true,
        });
        if (res.status === 200 || res.status === 201) {
            dispatch(sendOtpSuccess());
        } else {
            dispatch(sendOtpFailure(res.data.message || "Failed to send OTP"));
        }
    } catch (e) {
        handleApiError(dispatch, sendOtpFailure, e);
    }
};

const verifyOtpRequest = () => ({ type: VERIFY_OTP_REQUEST });
const verifyOtpSuccess = () => ({
    type: VERIFY_OTP_SUCCESS,
});
const verifyOtpFailure = (error) => ({
    type: VERIFY_OTP_FAILURE,
    payload: error,
});

export const verifyOtp = (otpData) => async (dispatch) => {
    dispatch(verifyOtpRequest());
    const apiUrl = BASE_URL + "orders/verify-otp";
    try {
        const res = await customAxios.post(apiUrl, otpData, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(verifyOtpSuccess());
        } else {
            dispatch(
                verifyOtpFailure(res.data.message || "Failed to verify OTP")
            );
        }
    } catch (e) {
        handleApiError(dispatch, verifyOtpFailure, e);
    }
};

const getShippingFeeRequest = () => ({ type: GET_SHIPPING_FEE_REQUEST });
const getShippingFeeSuccess = (shippingFee) => ({
    type: GET_SHIPPING_FEE_SUCCESS,
    payload: shippingFee,
});
const getShippingFeeFailure = (error) => ({
    type: GET_SHIPPING_FEE_FAILURE,
    payload: error,
});

export const getShippingFee = (districtId, wardId) => async (dispatch) => {
    dispatch(getShippingFeeRequest());
    const apiUrl = BASE_URL + `orders/shipping-fee/${districtId}/${wardId}`;
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200 && res.data.success) {
            dispatch(getShippingFeeSuccess(res.data.data.shipping_fee.total));
        } else {
            dispatch(
                getShippingFeeFailure(
                    res.data.message || "Failed to fetch shipping fee"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, getShippingFeeFailure, e);
    }
};

const createOrderRequest = () => ({ type: CREATE_ORDER_REQUEST });
const createOrderSuccess = (orderData) => ({
    type: CREATE_ORDER_SUCCESS,
    payload: orderData,
});
const createOrderFailure = (error) => ({
    type: CREATE_ORDER_FAILURE,
    payload: error,
});

export const createOrder = (orderDetails) => async (dispatch) => {
    dispatch(createOrderRequest());
    const apiUrl = BASE_URL + "orders/add";
    try {
        const res = await customAxios.post(apiUrl, orderDetails, {
            withCredentials: true,
        });
        if (res.status === 200 || res.status === 201) {
            dispatch(createOrderSuccess(res.data.data));
        } else {
            dispatch(
                createOrderFailure(res.data.message || "Failed to create order")
            );
        }
    } catch (e) {
        handleApiError(dispatch, createOrderFailure, e);
    }
};

const initiateMomoPaymentRequest = () => ({
    type: INITIATE_MOMO_PAYMENT_REQUEST,
});
const initiateMomoPaymentSuccess = (payUrl) => ({
    type: INITIATE_MOMO_PAYMENT_SUCCESS,
    payload: payUrl,
});
const initiateMomoPaymentFailure = (error) => ({
    type: INITIATE_MOMO_PAYMENT_FAILURE,
    payload: error,
});

export const initiateMomoPayment = (orderCode) => async (dispatch) => {
    dispatch(initiateMomoPaymentRequest());
    const apiUrl = BASE_URL + "orders/payment-momo";
    try {
        const res = await customAxios.post(
            apiUrl,
            { order_code: orderCode },
            {
                withCredentials: true,
            }
        );
        if (res.status === 200 && res.data.success) {
            dispatch(initiateMomoPaymentSuccess(res.data.payUrl));
        } else {
            dispatch(
                initiateMomoPaymentFailure(
                    res.data.message || "Failed to initiate MoMo payment"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, initiateMomoPaymentFailure, e);
    }
};

const getUserOrdersRequest = () => ({ type: GET_USER_ORDERS_REQUEST });
const getUserOrdersSuccess = (orders) => ({
    type: GET_USER_ORDERS_SUCCESS,
    payload: orders,
});
const getUserOrdersFailure = (error) => ({
    type: GET_USER_ORDERS_FAILURE,
    payload: error,
});

export const getUserOrders = () => async (dispatch) => {
    dispatch(getUserOrdersRequest());
    const apiUrl = BASE_URL + "orders/orders-user";
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(getUserOrdersSuccess(res.data.data));
        } else {
            dispatch(
                getUserOrdersFailure(
                    res.data.message || "Failed to fetch user orders"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, getUserOrdersFailure, e);
    }
};

const getOrdersRequest = () => ({ type: GET_ORDERS_REQUEST });
const getOrdersSuccess = (orders) => ({
    type: GET_ORDERS_SUCCESS,
    payload: orders,
});
const getOrdersFailure = (error) => ({
    type: GET_ORDERS_FAILURE,
    payload: error,
});

export const getOrders = () => async (dispatch) => {
    dispatch(getOrdersRequest());
    const apiUrl = BASE_URL + "orders/get-orders";
    try {
        const res = await customAxios.get(apiUrl, {
            withCredentials: true,
        });
        if (res.status === 200) {
            dispatch(getOrdersSuccess(res.data.data));
        } else {
            dispatch(
                getOrdersFailure(res.data.message || "Failed to fetch orders")
            );
        }
    } catch (e) {
        handleApiError(dispatch, getOrdersFailure, e);
    }
};

const confirmOrderRequest = () => ({ type: CONFIRM_ORDER_REQUEST });
const confirmOrderSuccess = () => ({
    type: CONFIRM_ORDER_SUCCESS,
});
const confirmOrderFailure = (error) => ({
    type: CONFIRM_ORDER_FAILURE,
    payload: error,
});

export const confirmOrder = (orderCode) => async (dispatch) => {
    dispatch(confirmOrderRequest());
    const apiUrl = BASE_URL + "orders/confirm-orders";
    try {
        const res = await customAxios.post(
            apiUrl,
            { order_code: orderCode },
            {
                withCredentials: true,
            }
        );
        if (res.status === 200 || res.status === 201) {
            dispatch(confirmOrderSuccess());
        } else {
            dispatch(
                confirmOrderFailure(
                    res.data.message || "Failed to confirm order"
                )
            );
        }
    } catch (e) {
        handleApiError(dispatch, confirmOrderFailure, e);
    }
};
