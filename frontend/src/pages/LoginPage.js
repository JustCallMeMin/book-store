import React, { Component } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import {
    Container,
    Row,
    Col,
    Card,
    Form,
    Button,
    Alert,
} from "react-bootstrap";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import "./AuthPages.css";
import { connect } from "react-redux";
import { loginUser } from "src/store/actions/user/userActions";

// HOC để truy cập navigate và location trong class component
function withRouter(Component) {
    return function WithRouterWrapper(props) {
        const navigate = useNavigate();
        const location = useLocation();
        return <Component navigate={navigate} location={location} {...props} />;
    };
}

class LoginPage extends Component {
    constructor(props) {
        super(props);
        this.state = {
            formData: {
                email: "",
                password: "",
            },
            errors: {},
            showPassword: false,
            serverError: "",
            loading: false,
        };
    }

    componentDidMount() {
        if (this.props.loginUserSuccess) {
            this.props.navigate("/");
        }
    }

    componentDidUpdate(prevProps) {
        if (prevProps.loginUserSuccess !== this.props.loginUserSuccess) {
            this.props.navigate("/");
        }
        if (prevProps.loginUserFailureMsg !== this.props.loginUserFailureMsg) {
            this.setState({
                serverError: this.props.loginUserFailureMsg,
            });
        }
        if (prevProps.loading !== this.props.loading) {
            this.setState({
                loading: this.props.loading,
            });
        }
    }

    validateForm = () => {
        const newErrors = {};
        const { email, password } = this.state.formData;

        if (!email) {
            newErrors.email = "Vui lòng nhập email";
        } else if (!/\S+@\S+\.\S+/.test(email)) {
            newErrors.email = "Email không hợp lệ";
        }

        if (!password) {
            newErrors.password = "Vui lòng nhập mật khẩu";
        }

        this.setState({ errors: newErrors });
        return Object.keys(newErrors).length === 0;
    };

    handleSubmit = async (e) => {
        e.preventDefault();
        if (this.validateForm()) {
            this.props.login(this.state.formData);
        }
    };

    handleChange = (e) => {
        const { name, value } = e.target;
        this.setState((prevState) => ({
            formData: {
                ...prevState.formData,
                [name]: value,
            },
        }));
        // Clear error when user starts typing
        if (this.state.errors[name]) {
            this.setState((prevState) => ({
                errors: {
                    ...prevState.errors,
                    [name]: "",
                },
            }));
        }
    };

    render() {
        const { formData, errors, showPassword, serverError, loading } =
            this.state;
        const { location } = this.props;
        const state = location.state || {};

        return (
            <div className="auth-container">
                <Container>
                    <Row className="justify-content-center">
                        <Col md={8} lg={6}>
                            <Card className="auth-card">
                                <Card.Body>
                                    <h2 className="text-center">Đăng nhập</h2>
                                    {state?.message && (
                                        <Alert
                                            variant={state.type || "info"}
                                            className="mb-3"
                                        >
                                            {state.message}
                                        </Alert>
                                    )}
                                    {serverError && (
                                        <Alert variant="danger">
                                            {serverError}
                                        </Alert>
                                    )}

                                    <Form onSubmit={this.handleSubmit}>
                                        <Form.Group className="mb-3">
                                            <Form.Label>Email</Form.Label>
                                            <Form.Control
                                                type="email"
                                                name="email"
                                                value={formData.email}
                                                onChange={this.handleChange}
                                                isInvalid={!!errors.email}
                                            />
                                            <Form.Control.Feedback type="invalid">
                                                {errors.email}
                                            </Form.Control.Feedback>
                                        </Form.Group>

                                        <Form.Group className="mb-4">
                                            <Form.Label>Mật khẩu</Form.Label>
                                            <div className="password-input">
                                                <Form.Control
                                                    type={
                                                        showPassword
                                                            ? "text"
                                                            : "password"
                                                    }
                                                    name="password"
                                                    value={formData.password}
                                                    onChange={this.handleChange}
                                                    isInvalid={
                                                        !!errors.password
                                                    }
                                                />
                                                <button
                                                    type="button"
                                                    className="password-toggle"
                                                    onClick={() =>
                                                        this.setState(
                                                            (prevState) => ({
                                                                showPassword:
                                                                    !prevState.showPassword,
                                                            })
                                                        )
                                                    }
                                                >
                                                    {showPassword ? (
                                                        <FaEyeSlash />
                                                    ) : (
                                                        <FaEye />
                                                    )}
                                                </button>
                                                <Form.Control.Feedback type="invalid">
                                                    {errors.password}
                                                </Form.Control.Feedback>
                                            </div>
                                            <div className="d-flex justify-content-end mt-1">
                                                <Link
                                                    to="/forgot-password"
                                                    className="small"
                                                >
                                                    Quên mật khẩu?
                                                </Link>
                                            </div>
                                        </Form.Group>

                                        <Button
                                            variant="primary"
                                            type="submit"
                                            className="w-100 mb-3"
                                            disabled={loading}
                                        >
                                            {loading
                                                ? "Đang xử lý..."
                                                : "Đăng nhập"}
                                        </Button>

                                        <p className="text-center mb-0">
                                            Chưa có tài khoản?{" "}
                                            <Link to="/register">Đăng ký</Link>
                                        </p>
                                    </Form>
                                </Card.Body>
                            </Card>
                        </Col>
                    </Row>
                </Container>
            </div>
        );
    }
}

const mapDispatchToProps = (dispatch) => ({
    login: (user) => dispatch(loginUser(user)),
});

const mapStateToProps = (state) => ({
    loading: state.userReducer.loading,
    loginUserFailureMsg: state.userReducer.loginUserFailureMsg,
    loginUserSuccess: state.userReducer.loginUserSuccess,
});

export default withRouter(
    connect(mapStateToProps, mapDispatchToProps)(LoginPage)
);
