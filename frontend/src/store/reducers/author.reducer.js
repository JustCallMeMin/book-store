const {
    FETCH_AUTHORS_FAILURE,
    FETCH_AUTHORS_REQUEST,
    FETCH_AUTHORS_SUCCESS,
} = require("../actions/author/authorTypes");
const initialState = {
    authors: [],
    loading: false,
    error: "",
};
function authorReducer(state = initialState, action) {
    switch (action.type) {
        case FETCH_AUTHORS_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case FETCH_AUTHORS_SUCCESS:
            return {
                loading: false,
                authors: action.payload,
                error: "",
            };
        case FETCH_AUTHORS_FAILURE:
            return {
                authors: [],
                loading: false,
                error: action.payload,
            };
        default:
            return state;
    }
}
export default authorReducer;
