import React from "react";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import ClientLayout from "./layouts/ClientLayout";
import HomePage from "./pages/HomePage";
import LoginPage from "./pages/LoginPage";
import BookDetail from "./pages/BookDetail";
import RegisterPage from "./pages/RegisterPage";
import CategoryBooks from "./pages/CategoryBooks";
import ProfilePage from "./pages/ProfilePage";
import { Provider } from "react-redux";
import store from "./store/config/store";

function App() {
    return (
        <BrowserRouter>
            <Provider store={store}>
                <Routes>
                    <Route path="/" element={<ClientLayout />}>
                        <Route index element={<HomePage />} />
                        <Route
                            path="categories/:categoryId"
                            element={<CategoryBooks />}
                        />
                        <Route path="profile" element={<ProfilePage />} />
                        <Route path="book/:id"  element={<BookDetail />} />
                    </Route>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/register" element={<RegisterPage />} />
                </Routes>
            </Provider>
        </BrowserRouter>
    );
}

export default App;
