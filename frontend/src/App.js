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
import ProtectedRoute from "./admin/components/ProtectedRoute";
import AccessDenied from "./admin/pages/AccessDenied";
import MainLayout from "./admin/components/Layout";
import Dashboard from "./admin/pages/Dashboard";
import CategoryManagement from "./admin/pages/CategoryManagement";
import AuthorManagement from "./admin/pages/AuthorManagement";
const user = JSON.parse(sessionStorage.getItem("user") || "{}");
const accessAdminRoles = ["Admin"];
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
                        <Route path="book/:id" element={<BookDetail />} />
                    </Route>
                    <Route
                        path="/accessDenied"
                        element={<AccessDenied />}
                    ></Route>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/register" element={<RegisterPage />} />
                    <Route
                        path="/admin"
                        element={
                            <ProtectedRoute
                                user={user}
                                isAccess={user?.roles?.some((role) =>
                                    accessAdminRoles.includes(role)
                                )}
                            >
                                <MainLayout />
                            </ProtectedRoute>
                        }
                    >
                        <Route index element={<Dashboard />} />
                        <Route
                            path="categories"
                            element={<CategoryManagement />}
                        />
                        <Route path="authors" element={<AuthorManagement />} />
                    </Route>
                </Routes>
            </Provider>
        </BrowserRouter>
    );
}

export default App;
