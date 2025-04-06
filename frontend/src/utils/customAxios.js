import axios from "axios";

const BASE_URL = process.env.REACT_APP_API_BASE_URL;

const customAxios = axios.create({
    baseURL: BASE_URL,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
    withCredentials: true,
});

// Add a request interceptor to dynamically add the token
customAxios.interceptors.request.use(
    (config) => {
        // Skip adding the token if `skipAuth` is true
        if (config.skipAuth) {
            return config;
        }

        // Get the latest access token from localStorage
        const ACCESS_TOKEN = localStorage.getItem("access_token")
            ? localStorage.getItem("access_token").replace(/"/g, "")
            : null;

        if (ACCESS_TOKEN) {
            config.headers["Authorization"] = `Bearer ${ACCESS_TOKEN}`;
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add a response interceptor to handle token refresh (optional)
customAxios.interceptors.response.use(
    (response) => {
        return response;
    },
    async (error) => {
        const originalRequest = error.config;

        // Skip token refresh if `skipAuth` is true
        if (originalRequest.skipAuth) {
            return Promise.reject(error);
        }

        // Handle 401 errors and refresh the token
        if (
            error.response &&
            error.response.status === 401 &&
            !originalRequest._retry
        ) {
            originalRequest._retry = true;
            try {
                const refreshResponse = await axios.post(
                    `${BASE_URL}auth/refresh-token`,
                    {},
                    { withCredentials: true }
                );
                const newAccessToken = refreshResponse.data.token;

                // Save the new token to localStorage
                localStorage.setItem(
                    "access_token",
                    JSON.stringify(newAccessToken)
                );

                // Update the Authorization header for the original request
                originalRequest.headers[
                    "Authorization"
                ] = `Bearer ${newAccessToken}`;

                // Retry the original request with the new token
                return customAxios(originalRequest);
            } catch (refreshError) {
                return Promise.reject(refreshError);
            }
        }

        return Promise.reject(error);
    }
);

export default customAxios;
