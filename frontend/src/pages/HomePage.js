import React, { useState } from "react";
import { Container, Row, Col, Carousel, Button } from "react-bootstrap";
import BookCard from "../components/BookCard";
import "./HomePage.css";

function HomePage() {
    const [cartItems, setCartItems] = useState([]);

    const handleCartUpdate = (updatedCart) => {
        setCartItems(updatedCart);
    };

    const banners = [
        {
            id: 1,
            image: "/images/banner1.jpg",
            title: "Sách mới tháng 4",
            description: "Khám phá những cuốn sách mới nhất",
        },
        {
            id: 2,
            image: "/images/banner2.jpg",
            title: "Giảm giá 30%",
            description: "Cho tất cả sách văn học",
        },
    ];

    const newBooks = [
        {
            id: 1,
            title: "Dế Mèn Phiêu Lưu Ký",
            author: "Tô Hoài",
            price: 75000,
            originalPrice: 95000,
            discount: 21,
            image: "/images/de-men.jpg",
            category: "Văn học",
            rating: 4.5,
            ratingCount: 128,
        },
        // Thêm sách khác...
    ];

    const bestSellers = [
        {
            id: 2,
            title: "Nhật Ký Trong Tù",
            author: "Hồ Chí Minh",
            price: 85000,
            image: "/images/nhat-ky.jpg",
            category: "Lịch sử",
            rating: 5,
            ratingCount: 245,
        },
        // Thêm sách khác...
    ];

    return (
        <div className="home-page">
            <Carousel className="main-banner">
                {banners.map((banner) => (
                    <Carousel.Item key={banner.id}>
                        <img
                            className="d-block w-100"
                            src={banner.image}
                            alt={banner.title}
                        />
                        <Carousel.Caption>
                            <h3>{banner.title}</h3>
                            <p>{banner.description}</p>
                            <Button variant="primary">Xem ngay</Button>
                        </Carousel.Caption>
                    </Carousel.Item>
                ))}
            </Carousel>

            <Container>
                <section className="book-section">
                    <div className="section-header">
                        <h2>Sách Mới</h2>
                        <Button variant="outline-primary">Xem tất cả</Button>
                    </div>
                    <Row>
                        {newBooks.map((book) => (
                            <Col
                                key={book.id}
                                xs={12}
                                sm={6}
                                md={4}
                                lg={3}
                                className="mb-4"
                            >
                                <BookCard
                                    book={book}
                                    onCartUpdate={handleCartUpdate}
                                />
                            </Col>
                        ))}
                    </Row>
                </section>

                <section className="book-section">
                    <div className="section-header">
                        <h2>Sách Bán Chạy</h2>
                        <Button variant="outline-primary">Xem tất cả</Button>
                    </div>
                    <Row>
                        {bestSellers.map((book) => (
                            <Col
                                key={book.id}
                                xs={12}
                                sm={6}
                                md={4}
                                lg={3}
                                className="mb-4"
                            >
                                <BookCard
                                    book={book}
                                    onCartUpdate={handleCartUpdate}
                                />
                            </Col>
                        ))}
                    </Row>
                </section>

                <section className="features">
                    <Row>
                        <Col md={3}>
                            <div className="feature-item">
                                <i className="fas fa-truck"></i>
                                <h4>Giao hàng miễn phí</h4>
                                <p>Cho đơn hàng từ 200.000đ</p>
                            </div>
                        </Col>
                        <Col md={3}>
                            <div className="feature-item">
                                <i className="fas fa-undo"></i>
                                <h4>Đổi trả dễ dàng</h4>
                                <p>7 ngày đổi trả</p>
                            </div>
                        </Col>
                        <Col md={3}>
                            <div className="feature-item">
                                <i className="fas fa-shield-alt"></i>
                                <h4>Thanh toán an toàn</h4>
                                <p>Bảo mật thông tin</p>
                            </div>
                        </Col>
                        <Col md={3}>
                            <div className="feature-item">
                                <i className="fas fa-headset"></i>
                                <h4>Hỗ trợ 24/7</h4>
                                <p>Hotline: 1900 1234</p>
                            </div>
                        </Col>
                    </Row>
                </section>
            </Container>
        </div>
    );
}

export default HomePage;
