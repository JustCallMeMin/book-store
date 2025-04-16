import React, { Component } from "react";
import { useNavigate, useLocation, Outlet } from "react-router-dom";
import { Spin } from "antd"; // Import Spin từ antd
import { connect } from "react-redux";
import { fetchUser } from "src/store/actions/user/userActions";
import { motion } from "framer-motion"; // Import motion từ Framer Motion
import Header from "../components/Header";
import Footer from "../components/Footer";
import "./ClientLayout.css";

// HOC để truy cập navigate và location trong class component
function withRouter(Component) {
    return function WithRouterWrapper(props) {
        const navigate = useNavigate();
        const location = useLocation();
        return <Component navigate={navigate} location={location} {...props} />;
    };
}

class ClientLayout extends Component {
    componentDidMount() {
        const accessToken = localStorage.getItem("access_token");
        if (accessToken) {
            this.props.fetchUser();
        }
    }

    componentDidUpdate(prevProps) {
        if (prevProps.loading !== this.props.loading) {
            if (this.props.loading) {
                console.log("Loading user data...");
            } else {
                console.log("User data loaded.");
            }
        }
    }

    render() {
        if (this.props.loading) {
            return (
                <motion.div
                    className="loading-container"
                    initial={{ opacity: 0 }} // Bắt đầu với opacity = 0
                    animate={{ opacity: 1 }} // Hiệu ứng fade-in
                    exit={{ opacity: 0 }} // Hiệu ứng fade-out
                    transition={{ duration: 0.5 }} // Thời gian chuyển đổi 0.5s
                >
                    <Spin size="large" tip="Đang tải dữ liệu..." />
                </motion.div>
            );
        }
        return (
            <div className="client-layout">
                <Header />
                <main className="main-content">
                    <Outlet />
                </main>
                <Footer />
            </div>
        );
    }
}

const mapDispatchToProps = (dispatch) => ({
    fetchUser: () => dispatch(fetchUser()),
});

const mapStateToProps = (state) => ({
    loading: state.userReducer.loading,
    loginUserFailureMsg: state.userReducer.loginUserFailureMsg,
    loginUserSuccess: state.userReducer.loginUserSuccess,
});

export default withRouter(
    connect(mapStateToProps, mapDispatchToProps)(ClientLayout)
);
