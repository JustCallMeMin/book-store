import React from "react";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import ClientLayout from "./layouts/ClientLayout";
import HomePage from "./pages/HomePage";
import LoginPage from "./pages/LoginPage";
import RegisterPage from "./pages/RegisterPage";
import CategoryBooks from "./pages/CategoryBooks";
import ProfilePage from "./pages/ProfilePage";

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<ClientLayout />}>
                    <Route index element={<HomePage />} />
                    <Route
                        path="categories/:categoryId"
                        element={<CategoryBooks />}
                    />
                    <Route path="profile" element={<ProfilePage />} />
                </Route>
                <Route path="/login" element={<LoginPage />} />
                <Route path="/register" element={<RegisterPage />} />
            </Routes>
        </BrowserRouter>
    );
}

export default App;
