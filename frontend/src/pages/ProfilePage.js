import React, { useState } from "react";
import {
    Container,
    Row,
    Col,
    Card,
    Form,
    Button,
    Alert,
    Nav,
    Tab,
} from "react-bootstrap";
import {
    FaUser,
    FaEnvelope,
    FaPhone,
    FaMapMarkerAlt,
    FaLock,
} from "react-icons/fa";
import "./ProfilePage.css";

const initialErrors = {
    submit: "",
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
};

const ProfilePage = () => {
    const [activeTab, setActiveTab] = useState("info");
    const [profileData, setProfileData] = useState({
        name: "Nguyễn Văn A",
        email: "nguyenvana@example.com",
        phone: "0123456789",
        address: "123 Đường ABC, Quận XYZ, TP.HCM",
    });
    const [passwordData, setPasswordData] = useState({
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
    });
    const [errors, setErrors] = useState(initialErrors);
    const [success, setSuccess] = useState("");
    const [loading, setLoading] = useState(false);

    const handleProfileSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors(initialErrors);
        setSuccess("");

        try {
            await new Promise((resolve) => setTimeout(resolve, 1000));
            setSuccess("Cập nhật thông tin thành công!");
        } catch (error) {
            setErrors((prev) => ({
                ...prev,
                submit: "Có lỗi xảy ra khi cập nhật thông tin.",
            }));
        }
        setLoading(false);
    };

    const handlePasswordSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setErrors(initialErrors);
        setSuccess("");

        if (!passwordData.currentPassword) {
            setErrors((prev) => ({
                ...prev,
                currentPassword: "Vui lòng nhập mật khẩu hiện tại",
            }));
            setLoading(false);
            return;
        }
        if (!passwordData.newPassword) {
            setErrors((prev) => ({
                ...prev,
                newPassword: "Vui lòng nhập mật khẩu mới",
            }));
            setLoading(false);
            return;
        }
        if (passwordData.newPassword.length < 6) {
            setErrors((prev) => ({
                ...prev,
                newPassword: "Mật khẩu phải có ít nhất 6 ký tự",
            }));
            setLoading(false);
            return;
        }
        if (passwordData.newPassword !== passwordData.confirmPassword) {
            setErrors((prev) => ({
                ...prev,
                confirmPassword: "Mật khẩu xác nhận không khớp",
            }));
            setLoading(false);
            return;
        }

        try {
            await new Promise((resolve) => setTimeout(resolve, 1000));
            setSuccess("Đổi mật khẩu thành công!");
            setPasswordData({
                currentPassword: "",
                newPassword: "",
                confirmPassword: "",
            });
        } catch (error) {
            setErrors((prev) => ({
                ...prev,
                submit: "Có lỗi xảy ra khi đổi mật khẩu.",
            }));
        }
        setLoading(false);
    };

    const handleProfileChange = (e) => {
        const { name, value } = e.target;
        setProfileData((prev) => ({
            ...prev,
            [name]: value,
        }));
    };

    const handlePasswordChange = (e) => {
        const { name, value } = e.target;
        setPasswordData((prev) => ({
            ...prev,
            [name]: value,
        }));
        if (errors[name]) {
            setErrors((prev) => ({
                ...prev,
                [name]: "",
            }));
        }
    };

    return (
        <Container className="py-5">
            <h2 className="text-center mb-4">Thông tin tài khoản</h2>

            <Row className="justify-content-center">
                <Col md={8}>
                    <Card className="profile-card mb-4">
                        <Card.Body>
                            <div className="profile-header">
                                <div className="profile-avatar">
                                    <FaUser size={40} />
                                </div>
                                <div className="profile-info">
                                    <h4>{profileData.name}</h4>
                                    <p className="text-muted mb-0">
                                        {profileData.email}
                                    </p>
                                </div>
                            </div>

                            {success && (
                                <Alert variant="success" className="mt-3">
                                    {success}
                                </Alert>
                            )}
                            {errors.submit && (
                                <Alert variant="danger" className="mt-3">
                                    {errors.submit}
                                </Alert>
                            )}

                            <Form
                                onSubmit={handleProfileSubmit}
                                className="mt-4"
                            >
                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaUser className="icon" />
                                        <Form.Control
                                            type="text"
                                            name="name"
                                            value={profileData.name}
                                            onChange={handleProfileChange}
                                            placeholder="Họ tên"
                                        />
                                    </div>
                                </Form.Group>

                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaEnvelope className="icon" />
                                        <Form.Control
                                            type="email"
                                            value={profileData.email}
                                            disabled
                                            placeholder="Email"
                                        />
                                    </div>
                                </Form.Group>

                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaPhone className="icon" />
                                        <Form.Control
                                            type="tel"
                                            name="phone"
                                            value={profileData.phone}
                                            onChange={handleProfileChange}
                                            placeholder="Số điện thoại"
                                        />
                                    </div>
                                </Form.Group>

                                <Form.Group className="mb-4 form-group">
                                    <div className="input-icon">
                                        <FaMapMarkerAlt className="icon" />
                                        <Form.Control
                                            as="textarea"
                                            rows={3}
                                            name="address"
                                            value={profileData.address}
                                            onChange={handleProfileChange}
                                            placeholder="Địa chỉ"
                                        />
                                    </div>
                                </Form.Group>

                                <div className="d-grid">
                                    <Button
                                        type="submit"
                                        className="profile-button"
                                        disabled={loading}
                                    >
                                        {loading
                                            ? "Đang cập nhật..."
                                            : "Cập nhật thông tin"}
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>

                    <Card className="profile-card">
                        <Card.Body>
                            <h4 className="mb-4">Đổi mật khẩu</h4>
                            <Form onSubmit={handlePasswordSubmit}>
                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaLock className="icon" />
                                        <Form.Control
                                            type="password"
                                            name="currentPassword"
                                            value={passwordData.currentPassword}
                                            onChange={handlePasswordChange}
                                            placeholder="Mật khẩu hiện tại"
                                            isInvalid={!!errors.currentPassword}
                                        />
                                    </div>
                                    <Form.Control.Feedback type="invalid">
                                        {errors.currentPassword}
                                    </Form.Control.Feedback>
                                </Form.Group>

                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaLock className="icon" />
                                        <Form.Control
                                            type="password"
                                            name="newPassword"
                                            value={passwordData.newPassword}
                                            onChange={handlePasswordChange}
                                            placeholder="Mật khẩu mới"
                                            isInvalid={!!errors.newPassword}
                                        />
                                    </div>
                                    <Form.Control.Feedback type="invalid">
                                        {errors.newPassword}
                                    </Form.Control.Feedback>
                                </Form.Group>

                                <Form.Group className="mb-4 form-group">
                                    <div className="input-icon">
                                        <FaLock className="icon" />
                                        <Form.Control
                                            type="password"
                                            name="confirmPassword"
                                            value={passwordData.confirmPassword}
                                            onChange={handlePasswordChange}
                                            placeholder="Xác nhận mật khẩu mới"
                                            isInvalid={!!errors.confirmPassword}
                                        />
                                    </div>
                                    <Form.Control.Feedback type="invalid">
                                        {errors.confirmPassword}
                                    </Form.Control.Feedback>
                                </Form.Group>

                                <div className="d-grid">
                                    <Button
                                        type="submit"
                                        className="profile-button"
                                        disabled={loading}
                                    >
                                        {loading
                                            ? "Đang cập nhật..."
                                            : "Đổi mật khẩu"}
                                    </Button>
                                </div>
                            </Form>
                        </Card.Body>
                    </Card>
                </Col>
            </Row>
        </Container>
    );
};

export default ProfilePage;
