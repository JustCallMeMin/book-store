import React from "react";
import { Navigate } from "react-router-dom";

const ProtectedRoute = ({ children }) => {
    const accessToken = localStorage.getItem("access_token");
    const user = JSON.parse(localStorage.getItem("user"));

    console.log("Access Token:", accessToken);
    console.log("User:", user);
    if (!accessToken || !user) {
        // Redirect to login if not authenticated
        return <Navigate to="/login" />;
    }

    // Redirect based on user role
    if (user.roles.includes("Admin")) {
        return <Navigate to="/admin" />;
    } else if (user.roles.includes("User")) {
        return children;
    }

    // Render children if no redirection is needed
};

export default ProtectedRoute;
