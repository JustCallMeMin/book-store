import React, { useState } from "react";
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

const LoginPage = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const state = location.state || {};

    const [formData, setFormData] = useState({
        email: "",
        password: "",
    });
    const [errors, setErrors] = useState({});
    const [showPassword, setShowPassword] = useState(false);
    const [serverError, setServerError] = useState("");
    const [loading, setLoading] = useState(false);

    const validateForm = () => {
        const newErrors = {};

        if (!formData.email) {
            newErrors.email = "Vui lòng nhập email";
        } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
            newErrors.email = "Email không hợp lệ";
        }

        if (!formData.password) {
            newErrors.password = "Vui lòng nhập mật khẩu";
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (validateForm()) {
            setLoading(true);
            try {
                // TODO: Implement login logic
                console.log("Form submitted:", formData);
                navigate("/");
            } catch (error) {
                setServerError("Email hoặc mật khẩu không chính xác");
            }
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prevState) => ({
            ...prevState,
            [name]: value,
        }));
        // Clear error when user starts typing
        if (errors[name]) {
            setErrors((prevErrors) => ({
                ...prevErrors,
                [name]: "",
            }));
        }
    };

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

                                <Form onSubmit={handleSubmit}>
                                    <Form.Group className="mb-3">
                                        <Form.Label>Email</Form.Label>
                                        <Form.Control
                                            type="email"
                                            name="email"
                                            value={formData.email}
                                            onChange={handleChange}
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
                                                onChange={handleChange}
                                                isInvalid={!!errors.password}
                                            />
                                            <button
                                                type="button"
                                                className="password-toggle"
                                                onClick={() =>
                                                    setShowPassword(
                                                        !showPassword
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
};

export default LoginPage;
