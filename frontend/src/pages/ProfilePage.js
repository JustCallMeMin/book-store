import React, { useState, useEffect } from "react";
import {
    Container,
    Row,
    Col,
    Card,
    Form,
    Button,
    Alert,
} from "react-bootstrap";
import { FaUser, FaEnvelope, FaPhone, FaLock } from "react-icons/fa";
import { connect } from "react-redux";
import "./ProfilePage.css";
import {
    changePassword,
    fetchUser,
    updateUser,
} from "src/store/actions/user/userActions";

const initialErrors = {
    currentPassword: "",
    newPassword: "",
    confirmPassword: "",
};

const ProfilePage = ({
    user,
    updatingUser,
    updateUserSuccess,
    updateUserError,
    changePassword,
    changingPassword,
    changePasswordSuccess,
    changePasswordError,
    updateUser,
    fetchUser,
}) => {
    const [profileData, setProfileData] = useState({
        firstName: "",
        lastName: "",
        email: "",
        phone: "",
    });
    const [passwordData, setPasswordData] = useState({
        currentPassword: "",
        newPassword: "",
        confirmPassword: "",
    });
    const [errors, setErrors] = useState(initialErrors);

    // Pre-fill profile data from user prop
    useEffect(() => {
        if (user) {
            setProfileData({
                firstName: user.first_name || "",
                lastName: user.last_name || "",
                email: user.email || "",
                phone: user.phone || "",
            });
        } else {
            console.error("User data not found");
            fetchUser();
        }
    }, [user]);

    const handleProfileSubmit = async (e) => {
        e.preventDefault();
        setErrors(initialErrors);

        // Dispatch the updateUser action
        updateUser({
            first_name: profileData.firstName,
            last_name: profileData.lastName,
            phone: profileData.phone,
        });
    };

    const handlePasswordSubmit = async (e) => {
        e.preventDefault();
        setErrors(initialErrors);

        if (!passwordData.currentPassword) {
            setErrors((prev) => ({
                ...prev,
                currentPassword: "Vui lòng nhập mật khẩu hiện tại",
            }));
            return;
        }
        if (!passwordData.newPassword) {
            setErrors((prev) => ({
                ...prev,
                newPassword: "Vui lòng nhập mật khẩu mới",
            }));
            return;
        }
        if (passwordData.newPassword.length < 6) {
            setErrors((prev) => ({
                ...prev,
                newPassword: "Mật khẩu phải có ít nhất 6 ký tự",
            }));
            return;
        }
        if (passwordData.newPassword !== passwordData.confirmPassword) {
            setErrors((prev) => ({
                ...prev,
                confirmPassword: "Mật khẩu xác nhận không khớp",
            }));
            return;
        }

        // Dispatch the changePassword action
        changePassword({
            current_password: passwordData.currentPassword,
            new_password: passwordData.newPassword,
            new_password_confirmation: passwordData.confirmPassword,
        });
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
                                    <h4>
                                        {profileData.firstName}{" "}
                                        {profileData.lastName}
                                    </h4>
                                    <p className="text-muted mb-0">
                                        {profileData.email}
                                    </p>
                                </div>
                            </div>

                            {updateUserSuccess && (
                                <Alert variant="success" className="mt-3">
                                    Cập nhật thông tin thành công!
                                </Alert>
                            )}
                            {updateUserError && (
                                <Alert variant="danger" className="mt-3">
                                    {updateUserError}
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
                                            name="firstName"
                                            value={profileData.firstName}
                                            onChange={handleProfileChange}
                                            placeholder="Họ"
                                        />
                                    </div>
                                </Form.Group>

                                <Form.Group className="mb-3 form-group">
                                    <div className="input-icon">
                                        <FaUser className="icon" />
                                        <Form.Control
                                            type="text"
                                            name="lastName"
                                            value={profileData.lastName}
                                            onChange={handleProfileChange}
                                            placeholder="Tên"
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

                                <div className="d-grid">
                                    <Button
                                        type="submit"
                                        className="profile-button"
                                        disabled={updatingUser}
                                    >
                                        {updatingUser
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

                            {changePasswordSuccess && (
                                <Alert variant="success" className="mt-3">
                                    Đổi mật khẩu thành công!
                                </Alert>
                            )}
                            {changePasswordError && (
                                <Alert variant="danger" className="mt-3">
                                    {changePasswordError}
                                </Alert>
                            )}

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
                                        disabled={changingPassword}
                                    >
                                        {changingPassword
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

const mapStateToProps = (state) => ({
    user: state.userReducer.user,
    updatingUser: state.userReducer.updatingUser,
    updateUserSuccess: state.userReducer.updateUserSuccess,
    updateUserError: state.userReducer.updateUserError,
    changingPassword: state.userReducer.changingPassword,
    changePasswordSuccess: state.userReducer.changePasswordSuccess,
    changePasswordError: state.userReducer.changePasswordError,
});

const mapDispatchToProps = (dispatch) => ({
    updateUser: (userData) => dispatch(updateUser(userData)),
    changePassword: (passwordData) => dispatch(changePassword(passwordData)),
    fetchUser: () => dispatch(fetchUser()),
});

export default connect(mapStateToProps, mapDispatchToProps)(ProfilePage);
