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
} from "./customCategoryTypes";

import customAxios from "../../../utils/customAxios";

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};
// GET ALL
export const getCustomCategories = () => async (dispatch) => {
    dispatch({ type: GET_CUSTOM_CATEGORIES_REQUEST });

    try {
        const res = await customAxios.get(`${BASE_URL}custom-categories`, {
            withCredentials: true,
        });

        dispatch({
            type: GET_CUSTOM_CATEGORIES_SUCCESS,
            payload: res.data,
        });
    } catch (err) {
        handleApiError(err);
        dispatch({ type: GET_CUSTOM_CATEGORIES_FAILURE });
    }
};

// GET ACTIVE
export const getActiveCustomCategories = () => async (dispatch) => {
    dispatch({ type: GET_ACTIVE_CUSTOM_CATEGORIES_REQUEST });

    try {
        const res = await customAxios.get(
            `${BASE_URL}custom-categories/active`,
            {
                withCredentials: true,
            }
        );

        // Kiểm tra cấu trúc dữ liệu API
        const categories = res.data;

        dispatch({
            type: GET_ACTIVE_CUSTOM_CATEGORIES_SUCCESS,
            payload: categories,
        });
    } catch (err) {
        handleApiError(
            dispatch,
            (message) => ({
                type: GET_ACTIVE_CUSTOM_CATEGORIES_FAILURE,
                payload: message,
            }),
            err
        );
    }
};

// CREATE
export const createCustomCategory = (data) => async (dispatch) => {
    dispatch({ type: CREATE_CUSTOM_CATEGORY_REQUEST });

    try {
        const res = await customAxios.post(
            `${BASE_URL}custom-categories`,
            data,
            {
                withCredentials: true,
            }
        );

        dispatch({
            type: CREATE_CUSTOM_CATEGORY_SUCCESS,
            payload: res.data.data,
        });
    } catch (err) {
        handleApiError(err);
        dispatch({ type: CREATE_CUSTOM_CATEGORY_FAILURE });
    }
};

// UPDATE
export const updateCustomCategory = (id, data) => async (dispatch) => {
    dispatch({ type: UPDATE_CUSTOM_CATEGORY_REQUEST });

    try {
        const res = await customAxios.put(
            `${BASE_URL}custom-categories/${id}`,
            data,
            {
                withCredentials: true,
            }
        );

        dispatch({
            type: UPDATE_CUSTOM_CATEGORY_SUCCESS,
            payload: res.data.data,
        });
    } catch (err) {
        handleApiError(err);
        dispatch({ type: UPDATE_CUSTOM_CATEGORY_FAILURE });
    }
};

// DELETE
export const deleteCustomCategory = (id) => async (dispatch) => {
    dispatch({ type: DELETE_CUSTOM_CATEGORY_REQUEST });

    try {
        await customAxios.delete(`${BASE_URL}custom-categories/${id}`, {
            withCredentials: true,
        });

        dispatch({
            type: DELETE_CUSTOM_CATEGORY_SUCCESS,
            payload: id,
        });
    } catch (err) {
        handleApiError(err);
        dispatch({ type: DELETE_CUSTOM_CATEGORY_FAILURE });
    }
};
