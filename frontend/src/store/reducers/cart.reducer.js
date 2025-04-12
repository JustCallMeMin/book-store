const {
    ADD_TO_CART_FAILURE,
    ADD_TO_CART_REQUEST,
    ADD_TO_CART_SUCCESS,
    CLEAR_CART_FAILURE,
    CLEAR_CART_REQUEST,
    CLEAR_CART_SUCCESS,
    GET_CART_ITEMS_FAILURE,
    GET_CART_ITEMS_REQUEST,
    GET_CART_ITEMS_SUCCESS,
    UPDATE_CART_ITEM_QUANTITY_FAILURE,
    UPDATE_CART_ITEM_QUANTITY_REQUEST,
    UPDATE_CART_ITEM_QUANTITY_SUCCESS,
} = require("./../actions/cart/cartTypes");
const initialState = {
    cartItems: [],
    loading: false,
    error: null,
    addToCartSuccess: false,
};
const cartReducer = (state = initialState, action) => {
    switch (action.type) {
        case GET_CART_ITEMS_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case GET_CART_ITEMS_SUCCESS:
            return {
                ...state,
                loading: false,
                cartItems: action.payload,
            };
        case GET_CART_ITEMS_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        case ADD_TO_CART_REQUEST:
            return {
                ...state,
                loading: true,
                addToCartSuccess: false,
            };
        case ADD_TO_CART_SUCCESS:
            return {
                ...state,
                loading: false,
                addToCartSuccess: true,
                cartItems: action.payload,
            };
        case ADD_TO_CART_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        case UPDATE_CART_ITEM_QUANTITY_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case UPDATE_CART_ITEM_QUANTITY_SUCCESS:
            return {
                ...state,
                loading: false,
                cartItems: action.payload,
            };
        case UPDATE_CART_ITEM_QUANTITY_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        case CLEAR_CART_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case CLEAR_CART_SUCCESS:
            return {
                ...state,
                loading: false,
                cartItems: [],
            };
        case CLEAR_CART_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        default:
            return state;
    }
};
export default cartReducer;
