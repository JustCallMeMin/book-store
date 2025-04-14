import { combineReducers } from "redux";
import userReducer from "./user.reducer";
import bookReducer from "./book.reducer";
import categoryReducer from "./category.reducer";
import authorReducer from "./author.reducer";
import publisherReducer from "./publisher.reducer";
import cartReducer from "./cart.reducer";

const rootReducer = combineReducers({
    userReducer,
    bookReducer,
    categoryReducer,
    authorReducer,
    publisherReducer,
    cartReducer,
});

export default rootReducer;
