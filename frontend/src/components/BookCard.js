import React, { useState, useCallback } from "react";
import { Card, Button, Toast, Spinner } from "react-bootstrap";
import { FaHeart, FaShoppingCart, FaStar } from "react-icons/fa";
import { connect } from "react-redux";
import {
    addToCart as addToCartAction,
    getCartItems as getCartItemsAction,
} from "./../store/actions/cart/cartAction";
import "./BookCard.css";

const BookCard = ({
    book,
    addToCart,
    getCartItems,
    onCartUpdate,
    isSkeleton = false,
}) => {
    const [showToast, setShowToast] = useState(false);
    const [isAdding, setIsAdding] = useState(false);

    const handleAddToCart = useCallback(async () => {
        if (isSkeleton || !book) return;
        setIsAdding(true);
        try {
            console.log("Adding to cart:", book.id);
            const updatedCart = await addToCart(book.id, 1);
            setShowToast(true);
            if (typeof onCartUpdate === "function") {
                onCartUpdate(updatedCart);
            }
        } catch (error) {
            console.error("Error adding to cart", error);
        } finally {
            setIsAdding(false);
        }
    }, [book, addToCart, onCartUpdate, isSkeleton]);

    const renderStars = (rating) => {
        const stars = [];
        for (let i = 0; i < 5; i++) {
            stars.push(
                <FaStar
                    key={i}
                    className={
                        i < Math.floor(rating) ? "star-filled" : "star-empty"
                    }
                />
            );
        }
        return stars;
    };

    // Parse price string and format as 141.000đ
    const formatPrice = (priceStr) => {
        if (!priceStr) return "N/A";
        const priceNum = parseFloat(priceStr);
        if (isNaN(priceNum)) return "N/A";
        return `${priceNum.toLocaleString("vi-VN", {
            minimumFractionDigits: 0,
        })}đ`;
    };

    if (isSkeleton) {
        return (
            <Card className="book-card skeleton">
                <div className="book-image-wrapper skeleton-image"></div>
                <Card.Body>
                    <div className="book-category skeleton-text short"></div>
                    <Card.Title className="book-title skeleton-text"></Card.Title>
                    <div className="book-author skeleton-text short"></div>
                    <div className="book-price">
                        <span className="current-price skeleton-text short"></span>
                    </div>
                    <div className="book-rating">
                        <div className="stars skeleton-text short"></div>
                    </div>
                </Card.Body>
            </Card>
        );
    }

    if (!book) return null;

    return (
        <>
            <Card className="book-card">
                <div className="book-image-wrapper">
                    <Card.Img
                        variant="top"
                        src={book.cover_image || "/media/placeholder.png"}
                        className="book-image"
                        alt={book.title || "Book cover"}
                        onError={(e) => {
                            console.warn(
                                `Failed to load image: ${book.cover_image}`
                            );
                            e.target.src = "/media/placeholder.png";
                        }}
                    />
                    <div className="book-actions">
                        <Button
                            variant="light"
                            className="action-btn"
                            disabled={isAdding}
                        >
                            <FaHeart />
                        </Button>
                        <Button
                            variant="primary"
                            className="action-btn"
                            onClick={handleAddToCart}
                            disabled={isAdding}
                        >
                            {isAdding ? (
                                <Spinner size="sm" animation="border" />
                            ) : (
                                <FaShoppingCart />
                            )}
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
                            {formatPrice(book.price)}
                        </span>
                        {book.originalPrice && (
                            <span className="original-price">
                                {formatPrice(book.originalPrice)}
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
};

const mapDispatchToProps = {
    addToCart: addToCartAction,
    getCartItems: getCartItemsAction,
};

export default connect(null, mapDispatchToProps)(React.memo(BookCard));
