import {
    GET_CUSTOM_CATEGORIES_REQUEST,
    GET_CUSTOM_CATEGORIES_SUCCESS,
    GET_CUSTOM_CATEGORIES_FAILURE,
    GET_ACTIVE_CUSTOM_CATEGORIES_REQUEST,
    GET_ACTIVE_CUSTOM_CATEGORIES_SUCCESS,
    GET_ACTIVE_CUSTOM_CATEGORIES_FAILURE,
    CREATE_CUSTOM_CATEGORY_REQUEST,
    CREATE_CUSTOM_CATEGORY_SUCCESS,
    CREATE_CUSTOM_CATEGORY_FAILURE,
    UPDATE_CUSTOM_CATEGORY_REQUEST,
    UPDATE_CUSTOM_CATEGORY_SUCCESS,
    UPDATE_CUSTOM_CATEGORY_FAILURE,
    DELETE_CUSTOM_CATEGORY_REQUEST,
    DELETE_CUSTOM_CATEGORY_SUCCESS,
    DELETE_CUSTOM_CATEGORY_FAILURE,
} from "../actions/customCategory/customCategoryTypes";
const initialState = {
    allCustomCategories: [],
    activeCustomCategories: [],
    loading: false,
    error: null, // ✅ Thêm error
};

const customCategoryReducer = (state = initialState, action) => {
    switch (action.type) {
        case GET_CUSTOM_CATEGORIES_REQUEST:
        case GET_ACTIVE_CUSTOM_CATEGORIES_REQUEST:
        case CREATE_CUSTOM_CATEGORY_REQUEST:
        case UPDATE_CUSTOM_CATEGORY_REQUEST:
        case DELETE_CUSTOM_CATEGORY_REQUEST:
            return {
                ...state,
                loading: true,
                error: null, // Reset error khi bắt đầu request
            };

        case GET_CUSTOM_CATEGORIES_SUCCESS:
            return {
                ...state,
                loading: false,
                allCustomCategories: action.payload,
                error: null,
            };

        case GET_ACTIVE_CUSTOM_CATEGORIES_SUCCESS:
            return {
                ...state,
                loading: false,
                activeCustomCategories: action.payload,
                error: null,
            };

        case GET_CUSTOM_CATEGORIES_FAILURE:
        case GET_ACTIVE_CUSTOM_CATEGORIES_FAILURE:
        case CREATE_CUSTOM_CATEGORY_FAILURE:
        case UPDATE_CUSTOM_CATEGORY_FAILURE:
        case DELETE_CUSTOM_CATEGORY_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload || "Có lỗi xảy ra", // Lưu lỗi
            };

        case CREATE_CUSTOM_CATEGORY_SUCCESS:
            return {
                ...state,
                loading: false,
                allCustomCategories: [
                    ...state.allCustomCategories,
                    action.payload,
                ],
                error: null,
            };

        case UPDATE_CUSTOM_CATEGORY_SUCCESS:
            return {
                ...state,
                loading: false,
                allCustomCategories: state.allCustomCategories.map((cat) =>
                    cat.id === action.payload.id ? action.payload : cat
                ),
                error: null,
            };

        case DELETE_CUSTOM_CATEGORY_SUCCESS:
            return {
                ...state,
                loading: false,
                allCustomCategories: state.allCustomCategories.filter(
                    (cat) => cat.id !== action.payload
                ),
                error: null,
            };

        default:
            return state;
    }
};

export default customCategoryReducer;
