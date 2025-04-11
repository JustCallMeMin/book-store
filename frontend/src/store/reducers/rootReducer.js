import { combineReducers } from "redux";
import userReducer from "./user.reducer";
import bookReducer from "./book.reducer";
import categoryReducer from "./category.reducer";
import authorReducer from "./author.reducer";

const rootReducer = combineReducers({
    userReducer,
    bookReducer,
    categoryReducer,
    authorReducer,
});

export default rootReducer;
