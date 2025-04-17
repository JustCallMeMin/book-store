const {
    FETCH_BOOKS_FAILURE,
    FETCH_BOOKS_REQUEST,
    FETCH_BOOKS_SUCCESS,
    FETCH_BOOK_REQUEST,
    FETCH_BOOK_SUCCESS,
    FETCH_BOOK_FAILURE,
    UPDATE_BOOK_REQUEST,
    UPDATE_BOOK_SUCCESS,
    UPDATE_BOOK_FAILURE,
    DELETE_BOOK_REQUEST,
    DELETE_BOOK_SUCCESS,
    DELETE_BOOK_FAILURE,
    ADD_BOOK_REQUEST,
    ADD_BOOK_SUCCESS,
    ADD_BOOK_FAILURE,
} = require("./../actions/book/bookTypes");

const initialState = {
    books: [],
    loading: false,
    error: "",
    actionState: false,
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
        case UPDATE_BOOK_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case UPDATE_BOOK_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case UPDATE_BOOK_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        case DELETE_BOOK_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case DELETE_BOOK_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case DELETE_BOOK_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        case ADD_BOOK_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case ADD_BOOK_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case ADD_BOOK_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload,
            };
        default:
            return state;
    }
}
export default bookReducer;
