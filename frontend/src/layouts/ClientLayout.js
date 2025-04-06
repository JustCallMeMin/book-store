import React, { Component } from "react";
import { useNavigate, useLocation, Outlet } from "react-router-dom";

import { connect } from "react-redux";
import { fetchUser } from "src/store/actions/user/userActions";
import Header from "../components/Header";
import Footer from "../components/Footer";

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
        // console.log("Access Token:", accessToken);
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
            return <div>Loading...</div>;
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

// export default ClientLayout;
