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
} = require("../actions/publisher/publisherTypes");
const initialState = {
    publishers: [],
    loading: false,
    error: "",
    actionState: false,
};
function publisherReducer(state = initialState, action) {
    switch (action.type) {
        case FETCH_PUBLISHERS_REQUEST:
            return {
                ...state,
                loading: true,
            };
        case FETCH_PUBLISHERS_SUCCESS:
            return {
                loading: false,
                publishers: action.payload,
                error: "",
            };
        case FETCH_PUBLISHERS_FAILURE:
            return {
                publishers: [],
                loading: false,
                error: action.payload,
            };
        case ADD_PUBLISHER_REQUEST:
            return {
                ...state,
                loading: true,
                actionState: false,
                error: "",
            };
        case ADD_PUBLISHER_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case ADD_PUBLISHER_FAILURE:
            return {
                ...state,
                loading: false,
                actionState: false,
                error: action.payload,
            };
        case UPDATE_PUBLISHER_REQUEST:
            return {
                ...state,
                loading: true,
                actionState: false,
                error: "",
            };
        case UPDATE_PUBLISHER_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case UPDATE_PUBLISHER_FAILURE:
            return {
                ...state,
                loading: false,
                actionState: false,
                error: action.payload,
            };
        case DELETE_PUBLISHER_REQUEST:
            return {
                ...state,
                loading: true,
                actionState: false,
                error: "",
            };
        case DELETE_PUBLISHER_SUCCESS:
            return {
                ...state,
                loading: false,
                actionState: true,
            };
        case DELETE_PUBLISHER_FAILURE:
            return {
                ...state,
                loading: false,
                actionState: false,
                error: action.payload,
            };
        default:
            return state;
    }
}
export default publisherReducer;
