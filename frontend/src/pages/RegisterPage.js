import React, { Component } from "react";
import {
    Container,
    Row,
    Col,
    Form,
    Button,
    Card,
    Alert,
} from "react-bootstrap";
import { Link, useNavigate } from "react-router-dom";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import "./AuthPages.css";

// HOC để truy cập navigate trong class component
function withRouter(Component) {
    return function WithRouterWrapper(props) {
        const navigate = useNavigate();
        return <Component navigate={navigate} {...props} />;
    };
}

class RegisterPage extends Component {
    constructor(props) {
        super(props);
        this.state = {
            formData: {
                firstName: "",
                lastName: "",
                email: "",
                password: "",
                confirmPassword: "",
            },
            showPassword: false,
            showConfirmPassword: false,
            errors: {},
            serverError: "",
            loading: false,
        };
    }

    validateForm = () => {
        const newErrors = {};
        const { firstName, lastName, email, password, confirmPassword } =
            this.state.formData;

        if (!firstName.trim()) {
            newErrors.firstName = "Vui lòng nhập tên";
        }

        if (!lastName.trim()) {
            newErrors.lastName = "Vui lòng nhập họ";
        }

        if (!email) {
            newErrors.email = "Vui lòng nhập email";
        } else if (!/\S+@\S+\.\S+/.test(email)) {
            newErrors.email = "Email không hợp lệ";
        }

        if (!password) {
            newErrors.password = "Vui lòng nhập mật khẩu";
        } else if (password.length < 6) {
            newErrors.password = "Mật khẩu phải có ít nhất 6 ký tự";
        }

        if (!confirmPassword) {
            newErrors.confirmPassword = "Vui lòng xác nhận mật khẩu";
        } else if (password !== confirmPassword) {
            newErrors.confirmPassword = "Mật khẩu xác nhận không khớp";
        }

        this.setState({ errors: newErrors });
        return Object.keys(newErrors).length === 0;
    };

    handleSubmit = async (e) => {
        e.preventDefault();
        if (this.validateForm()) {
            this.setState({ loading: true });
            try {
                // TODO: Implement registration logic
                console.log("Form submitted:", this.state.formData);
                this.props.navigate("/login", {
                    state: {
                        message: "Đăng ký thành công! Vui lòng đăng nhập.",
                        type: "success",
                    },
                });
            } catch (error) {
                this.setState({
                    serverError: "Có lỗi xảy ra khi đăng ký. Vui lòng thử lại.",
                });
            }
            this.setState({ loading: false });
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
        const {
            formData,
            showPassword,
            showConfirmPassword,
            errors,
            serverError,
            loading,
        } = this.state;

        return (
            <div className="auth-container">
                <Container>
                    <Row className="justify-content-center">
                        <Col md={8} lg={6}>
                            <Card className="auth-card">
                                <Card.Body>
                                    <h2 className="text-center">
                                        Đăng ký tài khoản
                                    </h2>
                                    {serverError && (
                                        <Alert variant="danger">
                                            {serverError}
                                        </Alert>
                                    )}

                                    <Form onSubmit={this.handleSubmit}>
                                        <Row>
                                            <Col md={6}>
                                                <Form.Group className="mb-3">
                                                    <Form.Label>Họ</Form.Label>
                                                    <Form.Control
                                                        type="text"
                                                        name="lastName"
                                                        value={
                                                            formData.lastName
                                                        }
                                                        onChange={
                                                            this.handleChange
                                                        }
                                                        isInvalid={
                                                            !!errors.lastName
                                                        }
                                                    />
                                                    <Form.Control.Feedback type="invalid">
                                                        {errors.lastName}
                                                    </Form.Control.Feedback>
                                                </Form.Group>
                                            </Col>
                                            <Col md={6}>
                                                <Form.Group className="mb-3">
                                                    <Form.Label>Tên</Form.Label>
                                                    <Form.Control
                                                        type="text"
                                                        name="firstName"
                                                        value={
                                                            formData.firstName
                                                        }
                                                        onChange={
                                                            this.handleChange
                                                        }
                                                        isInvalid={
                                                            !!errors.firstName
                                                        }
                                                    />
                                                    <Form.Control.Feedback type="invalid">
                                                        {errors.firstName}
                                                    </Form.Control.Feedback>
                                                </Form.Group>
                                            </Col>
                                        </Row>

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

                                        <Form.Group className="mb-3">
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
                                        </Form.Group>

                                        <Form.Group className="mb-4">
                                            <Form.Label>
                                                Xác nhận mật khẩu
                                            </Form.Label>
                                            <div className="password-input">
                                                <Form.Control
                                                    type={
                                                        showConfirmPassword
                                                            ? "text"
                                                            : "password"
                                                    }
                                                    name="confirmPassword"
                                                    value={
                                                        formData.confirmPassword
                                                    }
                                                    onChange={this.handleChange}
                                                    isInvalid={
                                                        !!errors.confirmPassword
                                                    }
                                                />
                                                <button
                                                    type="button"
                                                    className="password-toggle"
                                                    onClick={() =>
                                                        this.setState(
                                                            (prevState) => ({
                                                                showConfirmPassword:
                                                                    !prevState.showConfirmPassword,
                                                            })
                                                        )
                                                    }
                                                >
                                                    {showConfirmPassword ? (
                                                        <FaEyeSlash />
                                                    ) : (
                                                        <FaEye />
                                                    )}
                                                </button>
                                                <Form.Control.Feedback type="invalid">
                                                    {errors.confirmPassword}
                                                </Form.Control.Feedback>
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
                                                : "Đăng ký"}
                                        </Button>

                                        <p className="text-center mb-0">
                                            Đã có tài khoản?{" "}
                                            <Link to="/login">Đăng nhập</Link>
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

export default withRouter(RegisterPage);
