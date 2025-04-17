import customAxios from "../../../utils/customAxios";

const {
    FETCH_AUTHORS_FAILURE,
    FETCH_AUTHORS_REQUEST,
    FETCH_AUTHORS_SUCCESS,
} = require("../author/authorTypes");

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const fetchAuthorsRequest = () => ({
    type: FETCH_AUTHORS_REQUEST,
});
const fetchAuthorsSuccess = (authors) => ({
    type: FETCH_AUTHORS_SUCCESS,
    payload: authors,
});
const fetchAuthorsFailure = (error) => ({
    type: FETCH_AUTHORS_FAILURE,
    payload: error,
});
export const fetchAuthors = () => {
    return async (dispatch) => {
        dispatch(fetchAuthorsRequest());
        try {
            const response = await customAxios.get(
                `${BASE_URL}gutendex/authors`
            );
            dispatch(fetchAuthorsSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchAuthorsFailure, error);
        }
    };
};
