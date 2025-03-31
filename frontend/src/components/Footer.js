import React from "react";
import { Container, Row, Col } from "react-bootstrap";
import { Link } from "react-router-dom";

const Footer = () => {
    return (
        <footer className="bg-dark text-light py-4 mt-auto">
            <Container>
                <Row>
                    <Col md={4}>
                        <h5>Book Store</h5>
                        <p>
                            Địa chỉ: 123 Đường ABC, Quận XYZ
                            <br />
                            Điện thoại: (123) 456-7890
                            <br />
                            Email: contact@bookstore.com
                        </p>
                    </Col>
                    <Col md={4}>
                        <h5>Liên kết nhanh</h5>
                        <ul className="list-unstyled">
                            <li>
                                <Link to="/" className="text-light">
                                    Trang chủ
                                </Link>
                            </li>
                            <li>
                                <Link to="/categories" className="text-light">
                                    Danh mục sách
                                </Link>
                            </li>
                            <li>
                                <Link to="/about" className="text-light">
                                    Về chúng tôi
                                </Link>
                            </li>
                            <li>
                                <Link to="/contact" className="text-light">
                                    Liên hệ
                                </Link>
                            </li>
                        </ul>
                    </Col>
                    <Col md={4}>
                        <h5>Theo dõi chúng tôi</h5>
                        <div className="social-links">
                            <a href="#" className="text-light me-3">
                                <i className="fab fa-facebook"></i>
                            </a>
                            <a href="#" className="text-light me-3">
                                <i className="fab fa-twitter"></i>
                            </a>
                            <a href="#" className="text-light me-3">
                                <i className="fab fa-instagram"></i>
                            </a>
                        </div>
                    </Col>
                </Row>
                <Row className="mt-3">
                    <Col className="text-center">
                        <p className="mb-0">
                            &copy; 2024 Book Store. All rights reserved.
                        </p>
                    </Col>
                </Row>
            </Container>
        </footer>
    );
};

export default Footer;
