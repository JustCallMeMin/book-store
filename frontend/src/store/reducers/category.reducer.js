const {
    FETCH_CATEGORIES_FAILURE,
    FETCH_CATEGORIES_REQUEST,
    FETCH_CATEGORIES_SUCCESS,
} = require("./../actions/category/categoryTypes");
const initialState = {
    categories: [],
    loading: false,
    error: "",
};
function categoryReducer(state = initialState, action) {
    switch (action.type) {
        case FETCH_CATEGORIES_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case FETCH_CATEGORIES_SUCCESS:
            return {
                loading: false,
                categories: action.payload,
                error: "",
            };
        case FETCH_CATEGORIES_FAILURE:
            return {
                categories: [],
                loading: false,
                error: action.payload,
            };
        default:
            return state;
    }
}
export default categoryReducer;
