import customAxios from "../../../utils/customAxios";

const {
    FETCH_CATEGORIES_FAILURE,
    FETCH_CATEGORIES_REQUEST,
    FETCH_CATEGORIES_SUCCESS,
} = require("../category/categoryTypes");

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const fetchCategoriesRequest = () => ({
    type: FETCH_CATEGORIES_REQUEST,
});
const fetchCategoriesSuccess = (categories) => ({
    type: FETCH_CATEGORIES_SUCCESS,
    payload: categories,
});
const fetchCategoriesFailure = (error) => ({
    type: FETCH_CATEGORIES_FAILURE,
    payload: error,
});
export const fetchCategories = () => {
    return async (dispatch) => {
        dispatch(fetchCategoriesRequest());
        try {
            const response = await customAxios.get(
                `${BASE_URL}gutendex/categories`
            );
            dispatch(fetchCategoriesSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchCategoriesFailure, error);
        }
    };
};
