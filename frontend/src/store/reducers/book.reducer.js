const {
    FETCH_BOOKS_FAILURE,
    FETCH_BOOKS_REQUEST,
    FETCH_BOOKS_SUCCESS,
    FETCH_BOOK_REQUEST,
    FETCH_BOOK_SUCCESS,
    FETCH_BOOK_FAILURE,
} = require("./../actions/book/bookTypes");

const initialState = {
    books: [],
    loading: false,
    error: "",
};
function bookReducer(state = initialState, action) {
    switch (action.type) {
        case FETCH_BOOKS_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case FETCH_BOOKS_SUCCESS:
            return {
                loading: false,
                books: action.payload,
                error: "",
            };
        case FETCH_BOOKS_FAILURE:
            return {
                books: [],
                loading: false,
                error: action.payload,
            };
        case FETCH_BOOK_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case FETCH_BOOK_SUCCESS:
            return {
                book: action.payload,
                loading: false,
            };
        case FETCH_BOOK_FAILURE:
            return {
                book: {},
                loading: false,
                error: action.payload,
            };
        default:
            return state;
    }
}
export default bookReducer;
