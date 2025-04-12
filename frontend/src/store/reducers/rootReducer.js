import { combineReducers } from "redux";
import userReducer from "./user.reducer";
import bookReducer from "./book.reducer";
import categoryReducer from "./category.reducer";
import cartReducer from "./cart.reducer";

const rootReducer = combineReducers({
    userReducer,
    bookReducer,
    categoryReducer,
    cartReducer,
});

export default rootReducer;
