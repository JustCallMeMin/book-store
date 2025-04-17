import React, { Component } from "react";
import { connect } from "react-redux";
import { Dropdown } from "react-bootstrap";
import { logoutUser } from "src/store/actions/user/userActions";
import { withRouter } from "src/store/HOC/withRouter";
import { useNavigate } from "react-router-dom";

class AccountMenu extends Component {
    handleLogout = () => {
        const { logout, navigate } = this.props;
        logout();
        navigate("/login");
    };

    handleProfile = () => {
        const { navigate } = this.props;
        navigate("/profile");
    };

    handleMyOrders = () => {
        const { navigate } = this.props;
        navigate("/myOrders");
    };

    render() {
        const { user } = this.props;
        console.log("User in AccountMenu:", user);
        const fullName = user ? `${user.first_name} ${user.last_name}` : "";

        return (
            <Dropdown align="end">
                <Dropdown.Toggle
                    variant="link"
                    id="dropdown-account"
                    className="nav-link d-flex align-items-center"
                >
                    <span>{fullName}</span>
                </Dropdown.Toggle>

                <Dropdown.Menu>
                    <Dropdown.Item onClick={this.handleProfile}>
                        Thông tin cá nhân
                    </Dropdown.Item>
                    <Dropdown.Divider />
                    <Dropdown.Item onClick={this.handleMyOrders}>
                        Đơn hàng của tôi
                    </Dropdown.Item>
                    <Dropdown.Divider />
                    <Dropdown.Item onClick={this.handleLogout}>
                        Đăng xuất
                    </Dropdown.Item>
                </Dropdown.Menu>
            </Dropdown>
        );
    }
}

const mapStateToProps = (state) => ({
    user: state.userReducer.user,
});

const mapDispatchToProps = (dispatch) => ({
    logout: () => dispatch(logoutUser()),
});

// Wrap the component with `withRouter` to inject `navigate` prop
const withNavigate = (Component) => {
    return (props) => {
        const navigate = useNavigate();
        return <Component {...props} navigate={navigate} />;
    };
};

export default withNavigate(
    connect(mapStateToProps, mapDispatchToProps)(AccountMenu)
);
