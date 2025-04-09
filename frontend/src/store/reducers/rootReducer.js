import { combineReducers } from "redux";
import userReducer from "./user.reducer";
import bookReducer from "./book.reducer";
import categoryReducer from "./category.reducer";

const rootReducer = combineReducers({
    userReducer,
    bookReducer,
    categoryReducer,
});

export default rootReducer;
