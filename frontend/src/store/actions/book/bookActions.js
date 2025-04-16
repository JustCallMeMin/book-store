import customAxios from "../../../utils/customAxios";

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

export const fetchBooks = (filters = {}) => {
    return async (dispatch) => {
        dispatch(fetchBooksRequest());
        try {
            // Build query string from filters
            const queryParams = new URLSearchParams();

            if (filters.page) queryParams.append("page", filters.page);
            if (filters.per_page)
                queryParams.append("per_page", filters.per_page);
            if (filters.search) queryParams.append("search", filters.search);
            if (filters.category)
                queryParams.append("category", filters.category);
            if (filters.author_id)
                queryParams.append("author_id", filters.author_id);
            if (filters.language)
                queryParams.append("language", filters.language);
            if (filters.is_featured !== undefined)
                queryParams.append("is_featured", filters.is_featured);
            if (filters.is_active !== undefined)
                queryParams.append("is_active", filters.is_active);
            if (filters.price_min)
                queryParams.append("price_min", filters.price_min);
            if (filters.price_max)
                queryParams.append("price_max", filters.price_max);
            if (filters.published_year_min)
                queryParams.append(
                    "published_year_min",
                    filters.published_year_min
                );
            if (filters.published_year_max)
                queryParams.append(
                    "published_year_max",
                    filters.published_year_max
                );
            if (filters.publisher)
                queryParams.append("publisher", filters.publisher);
            if (filters.publisher_id)
                queryParams.append("publisher_id", filters.publisher_id);
            if (filters.sort_by) queryParams.append("sort_by", filters.sort_by);
            if (filters.sort_direction)
                queryParams.append("sort_direction", filters.sort_direction);
            console.log(queryParams.toString());
            const response = await customAxios.get(
                `${BASE_URL}gutendex/books?${queryParams.toString()}`
            );
            dispatch(fetchBooksSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchBooksFailure, error);
        }
    };
};

// Rest of the file remains unchanged
export const fetchAllBooks = () => {
    return async (dispatch) => {
        dispatch(fetchBooksRequest());
        try {
            const response = await customAxios.get(
                `${BASE_URL}gutendex/books?per_page=${1000000}`
            );
            dispatch(fetchBooksSuccess(response.data.data));
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
    type: FETCH_BOOK_FAILURE,
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

const updateBookRequest = () => ({
    type: UPDATE_BOOK_REQUEST,
});
const updateBookSuccess = (book) => ({
    type: UPDATE_BOOK_SUCCESS,
    payload: book,
});
const updateBookFailure = (error) => ({
    type: UPDATE_BOOK_FAILURE,
    payload: error,
});
export const updateBook = (bookId, bookData) => {
    return async (dispatch) => {
        dispatch(updateBookRequest());
        try {
            const response = await customAxios.put(
                `${BASE_URL}gutendex/books/${bookId}`,
                bookData
            );
            dispatch(updateBookSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, updateBookFailure, error);
        }
    };
};
const deleteBookRequest = () => ({
    type: DELETE_BOOK_REQUEST,
});
const deleteBookSuccess = (bookId) => ({
    type: DELETE_BOOK_SUCCESS,
    payload: bookId,
});
const deleteBookFailure = (error) => ({
    type: DELETE_BOOK_FAILURE,
    payload: error,
});
export const deleteBook = (bookId) => {
    return async (dispatch) => {
        dispatch(deleteBookRequest());
        try {
            await customAxios.delete(`${BASE_URL}gutendex/books/${bookId}`);
            dispatch(deleteBookSuccess(bookId));
        } catch (error) {
            handleApiError(dispatch, deleteBookFailure, error);
        }
    };
};
const addBookRequest = () => ({
    type: ADD_BOOK_REQUEST,
});
const addBookSuccess = (book) => ({
    type: ADD_BOOK_SUCCESS,
    payload: book,
});
const addBookFailure = (error) => ({
    type: ADD_BOOK_FAILURE,
    payload: error,
});
export const addBook = (bookData) => {
    return async (dispatch) => {
        dispatch(addBookRequest());
        try {
            const response = await customAxios.post(
                `${BASE_URL}gutendex/books`,
                bookData
            );
            dispatch(addBookSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, addBookFailure, error);
        }
    };
};
