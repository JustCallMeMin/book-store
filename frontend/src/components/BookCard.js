import React, { useState } from "react";
import { Card, Button, Toast } from "react-bootstrap";
import { FaHeart, FaShoppingCart, FaStar } from "react-icons/fa";
import { addToCart } from "../utils/cartUtils";
import "./BookCard.css";

function BookCard({ book, onCartUpdate }) {
    const [showToast, setShowToast] = useState(false);

    const handleAddToCart = () => {
        const updatedCart = addToCart(book);
        setShowToast(true);
        if (typeof onCartUpdate === "function") {
            onCartUpdate(updatedCart);
        }
    };

    const renderStars = (rating) => {
        return [...Array(5)].map((_, index) => (
            <FaStar
                key={index}
                className={
                    index < Math.floor(rating) ? "star-filled" : "star-empty"
                }
            />
        ));
    };

    return (
        <>
            <Card className="book-card">
                <div className="book-image-wrapper">
                    <Card.Img
                        variant="top"
                        src={book.image}
                        className="book-image"
                    />
                    <div className="book-actions">
                        <Button variant="light" className="action-btn">
                            <FaHeart />
                        </Button>
                        <Button
                            variant="primary"
                            className="action-btn"
                            onClick={handleAddToCart}
                        >
                            <FaShoppingCart />
                        </Button>
                    </div>
                    {book.discount > 0 && (
                        <span className="discount-badge">
                            -{book.discount}%
                        </span>
                    )}
                </div>
                <Card.Body>
                    <div className="book-category">{book.category}</div>
                    <Card.Title className="book-title">
                        <a href={`/book/${book.id}`}>{book.title}</a>
                    </Card.Title>
                    <div className="book-author">{book.author}</div>
                    <div className="book-price">
                        <span className="current-price">
                            {book.price.toLocaleString("vi-VN")} đ
                        </span>
                        {book.originalPrice && (
                            <span className="original-price">
                                {book.originalPrice.toLocaleString("vi-VN")} đ
                            </span>
                        )}
                    </div>
                    {book.rating && (
                        <div className="book-rating">
                            <div className="stars">
                                {renderStars(book.rating)}
                            </div>
                            <span className="rating-count">
                                ({book.ratingCount})
                            </span>
                        </div>
                    )}
                </Card.Body>
            </Card>

            <Toast
                show={showToast}
                onClose={() => setShowToast(false)}
                delay={3000}
                autohide
                className="cart-toast"
                style={{
                    position: "fixed",
                    bottom: 20,
                    right: 20,
                    zIndex: 9999,
                }}
            >
                <Toast.Header>
                    <strong className="me-auto">Thông báo</strong>
                </Toast.Header>
                <Toast.Body>Đã thêm "{book.title}" vào giỏ hàng!</Toast.Body>
            </Toast>
        </>
    );
}

export default BookCard;
