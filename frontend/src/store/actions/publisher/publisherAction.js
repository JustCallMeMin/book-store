import { type } from "@testing-library/user-event/dist/type";
import customAxios from "../../../utils/customAxios";

const {
    FETCH_PUBLISHERS_FAILURE,
    FETCH_PUBLISHERS_REQUEST,
    FETCH_PUBLISHERS_SUCCESS,
    ADD_PUBLISHER_REQUEST,
    ADD_PUBLISHER_SUCCESS,
    ADD_PUBLISHER_FAILURE,
    UPDATE_PUBLISHER_REQUEST,
    UPDATE_PUBLISHER_SUCCESS,
    UPDATE_PUBLISHER_FAILURE,
    DELETE_PUBLISHER_REQUEST,
    DELETE_PUBLISHER_SUCCESS,
    DELETE_PUBLISHER_FAILURE,
} = require("../publisher/publisherTypes");

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const handleApiError = (dispatch, action, error) => {
    if (error.response) {
        dispatch(action(error.response.data.message || "Có lỗi xảy ra"));
    } else {
        dispatch(action(error.message));
    }
};

const fetchPublishersRequest = () => ({
    type: FETCH_PUBLISHERS_REQUEST,
});
const fetchPublishersSuccess = (publishers) => ({
    type: FETCH_PUBLISHERS_SUCCESS,
    payload: publishers,
});
const fetchPublishersFailure = (error) => ({
    type: FETCH_PUBLISHERS_FAILURE,
    payload: error,
});
export const fetchPublishers = () => {
    return async (dispatch) => {
        dispatch(fetchPublishersRequest());
        try {
            const response = await customAxios.get(`${BASE_URL}publishers`);
            dispatch(fetchPublishersSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, fetchPublishersFailure, error);
        }
    };
};

const addPublisherRequest = () => ({
    type: ADD_PUBLISHER_REQUEST,
});
const addPublisherSuccess = (publisher) => ({
    type: ADD_PUBLISHER_SUCCESS,
    payload: publisher,
});
const addPublisherFailure = (error) => ({
    type: ADD_PUBLISHER_FAILURE,
    payload: error,
});
export const addPublisher = (publisherData) => {
    console.log("publisherData", publisherData);
    return async (dispatch) => {
        dispatch(addPublisherRequest());
        try {
            const response = await customAxios.post(
                `${BASE_URL}publishers`,
                publisherData
            );
            await dispatch(fetchPublishers());
            dispatch(addPublisherSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, addPublisherFailure, error);
        }
    };
};

const updatePublisherRequest = () => ({
    type: UPDATE_PUBLISHER_REQUEST,
});
const updatePublisherSuccess = (publisher) => ({
    type: UPDATE_PUBLISHER_SUCCESS,
    payload: publisher,
});
const updatePublisherFailure = (error) => ({
    type: UPDATE_PUBLISHER_FAILURE,
    payload: error,
});
export const updatePublisher = (publisherId, publisherData) => {
    return async (dispatch) => {
        dispatch(updatePublisherRequest());
        try {
            const response = await customAxios.put(
                `${BASE_URL}publishers/${publisherId}`,
                publisherData
            );
            await dispatch(fetchPublishers());
            dispatch(updatePublisherSuccess(response.data));
        } catch (error) {
            handleApiError(dispatch, updatePublisherFailure, error);
        }
    };
};
const deletePublisherRequest = () => ({
    type: DELETE_PUBLISHER_REQUEST,
});
const deletePublisherSuccess = (publisherId) => ({
    type: DELETE_PUBLISHER_SUCCESS,
    payload: publisherId,
});
const deletePublisherFailure = (error) => ({
    type: DELETE_PUBLISHER_FAILURE,
    payload: error,
});
export const deletePublisher = (publisherId) => {
    return async (dispatch) => {
        dispatch(deletePublisherRequest());
        try {
            await customAxios.delete(`${BASE_URL}publishers/${publisherId}`);
            await dispatch(fetchPublishers());
            dispatch(deletePublisherSuccess(publisherId));
        } catch (error) {
            handleApiError(dispatch, deletePublisherFailure, error);
        }
    };
};
