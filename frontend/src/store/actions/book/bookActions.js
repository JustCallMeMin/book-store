import customAxios from "../../../utils/customAxios";

const {
    FETCH_BOOKS_FAILURE,
    FETCH_BOOKS_REQUEST,
    FETCH_BOOKS_SUCCESS,
    FETCH_BOOK_REQUEST,
    FETCH_BOOK_SUCCESS,
} = require("../book/bookTypes");

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const fetchBooksRequest = () => ({
    type: FETCH_BOOKS_REQUEST,
});

const fetchBooksSuccess = (books) => ({
    type: FETCH_BOOKS_SUCCESS,
    payload: books,
});

const fetchBooksFailure = (error) => ({
    type: FETCH_BOOKS_FAILURE,
    payload: error,
});

export const fetchBooks = (page, per_page) => {
    return async (dispatch) => {
        dispatch(fetchBooksRequest());
        try {
            const response = await customAxios.get(
                `${BASE_URL}gutendex/books?page=${page}&per_page=${per_page}`
            );
            dispatch(fetchBooksSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchBooksFailure, error);
        }
    };
};

const fetchBookRequest = () => ({
    type: FETCH_BOOK_REQUEST,
});
const fetchBookSuccess = (book) => ({
    type: FETCH_BOOK_SUCCESS,
    payload: book,
});
const fetchBookFailure = (error) => ({
    type: FETCH_BOOKS_FAILURE,
    payload: error,
});
export const fetchBook = (bookId) => {
    return async (dispatch) => {
        dispatch(fetchBookRequest());
        try {
            const response = await customAxios.get(
                `${BASE_URL}gutendex/books/${bookId}`
            );
            dispatch(fetchBookSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchBookFailure, error);
        }
    };
};
