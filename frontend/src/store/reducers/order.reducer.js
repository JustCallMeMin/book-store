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
} from "../actions/order/orderTypes";

const initialState = {
    provinces: [],
    districts: [],
    wards: [],
    loadingProvinces: false,
    loadingDistricts: false,
    loadingWards: false,
    sendingOtp: false,
    verifyingOtp: false,
    otpSent: false,
    otpVerified: false,
    shippingFee: 0,
    loadingShippingFee: false,
    creatingOrder: false,
    orderCreated: false,
    orderData: null,
    initiatingMomoPayment: false,
    momoPayUrl: null,
    userOrders: [],
    loadingUserOrders: false,
    orders: [],
    loadingOrders: false,
    confirmingOrder: false,
    orderConfirmed: false,
    error: null,
    otpError: null,
    shippingFeeError: null,
    createOrderError: null,
    momoPaymentError: null,
    userOrdersError: null,
    ordersError: null,
    confirmOrderError: null,
};

const orderReducer = (state = initialState, action) => {
    switch (action.type) {
        case GET_PROVINCES_REQUEST:
            return { ...state, loadingProvinces: true, error: null };
        case GET_PROVINCES_SUCCESS:
            return {
                ...state,
                loadingProvinces: false,
                provinces: action.payload,
            };
        case GET_PROVINCES_FAILURE:
            return { ...state, loadingProvinces: false, error: action.payload };
        case GET_DISTRICTS_REQUEST:
            return {
                ...state,
                loadingDistricts: true,
                error: null,
                districts: [],
                shippingFee: 0,
                shippingFeeError: null,
            };
        case GET_DISTRICTS_SUCCESS:
            return {
                ...state,
                loadingDistricts: false,
                districts: action.payload,
            };
        case GET_DISTRICTS_FAILURE:
            return { ...state, loadingDistricts: false, error: action.payload };
        case GET_WARDS_REQUEST:
            return {
                ...state,
                loadingWards: true,
                error: null,
                wards: [],
                shippingFee: 0,
                shippingFeeError: null,
            };
        case GET_WARDS_SUCCESS:
            return { ...state, loadingWards: false, wards: action.payload };
        case GET_WARDS_FAILURE:
            return { ...state, loadingWards: false, error: action.payload };
        case SEND_OTP_REQUEST:
            return {
                ...state,
                sendingOtp: true,
                otpError: null,
                otpSent: false,
            };
        case SEND_OTP_SUCCESS:
            return { ...state, sendingOtp: false, otpSent: true };
        case SEND_OTP_FAILURE:
            return {
                ...state,
                sendingOtp: false,
                otpError: action.payload,
                otpSent: false,
            };
        case VERIFY_OTP_REQUEST:
            return {
                ...state,
                verifyingOtp: true,
                otpError: null,
                otpVerified: false,
            };
        case VERIFY_OTP_SUCCESS:
            return { ...state, verifyingOtp: false, otpVerified: true };
        case VERIFY_OTP_FAILURE:
            return {
                ...state,
                verifyingOtp: false,
                otpError: action.payload,
                otpVerified: false,
            };
        case GET_SHIPPING_FEE_REQUEST:
            return {
                ...state,
                loadingShippingFee: true,
                shippingFeeError: null,
            };
        case GET_SHIPPING_FEE_SUCCESS:
            return {
                ...state,
                loadingShippingFee: false,
                shippingFee: action.payload,
            };
        case GET_SHIPPING_FEE_FAILURE:
            return {
                ...state,
                loadingShippingFee: false,
                shippingFeeError: action.payload,
                shippingFee: 0,
            };
        case CREATE_ORDER_REQUEST:
            return {
                ...state,
                creatingOrder: true,
                createOrderError: null,
                orderCreated: false,
            };
        case CREATE_ORDER_SUCCESS:
            return {
                ...state,
                creatingOrder: false,
                orderCreated: true,
                orderData: action.payload,
            };
        case CREATE_ORDER_FAILURE:
            return {
                ...state,
                creatingOrder: false,
                createOrderError: action.payload,
                orderCreated: false,
            };
        case INITIATE_MOMO_PAYMENT_REQUEST:
            return {
                ...state,
                initiatingMomoPayment: true,
                momoPaymentError: null,
            };
        case INITIATE_MOMO_PAYMENT_SUCCESS:
            return {
                ...state,
                initiatingMomoPayment: false,
                momoPayUrl: action.payload,
            };
        case INITIATE_MOMO_PAYMENT_FAILURE:
            return {
                ...state,
                initiatingMomoPayment: false,
                momoPaymentError: action.payload,
            };
        case GET_USER_ORDERS_REQUEST:
            return { ...state, loadingUserOrders: true, userOrdersError: null };
        case GET_USER_ORDERS_SUCCESS:
            return {
                ...state,
                loadingUserOrders: false,
                userOrders: action.payload,
            };
        case GET_USER_ORDERS_FAILURE:
            return {
                ...state,
                loadingUserOrders: false,
                userOrdersError: action.payload,
            };
        case GET_ORDERS_REQUEST:
            return { ...state, loadingOrders: true, ordersError: null };
        case GET_ORDERS_SUCCESS:
            return {
                ...state,
                loadingOrders: false,
                orders: action.payload,
            };
        case GET_ORDERS_FAILURE:
            return {
                ...state,
                loadingOrders: false,
                ordersError: action.payload,
            };
        case CONFIRM_ORDER_REQUEST:
            return {
                ...state,
                confirmingOrder: true,
                confirmOrderError: null,
                orderConfirmed: false,
            };
        case CONFIRM_ORDER_SUCCESS:
            return {
                ...state,
                confirmingOrder: false,
                orderConfirmed: true,
            };
        case CONFIRM_ORDER_FAILURE:
            return {
                ...state,
                confirmingOrder: false,
                confirmOrderError: action.payload,
                orderConfirmed: false,
            };
        default:
            return state;
    }
};

export default orderReducer;
