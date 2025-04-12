import React, { Component } from "react";
import { Card, Button, Toast } from "react-bootstrap";
import { FaHeart, FaShoppingCart, FaStar } from "react-icons/fa";
import { connect } from "react-redux";
import {
    addToCart as addToCartAction,
    getCartItems as getCartItemsAction,
} from "./../store/actions/cart/cartAction";
import "./BookCard.css";

class BookCard extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showToast: false,
        };
    }
    handleAddToCart = async () => {
        const { book, onCartUpdate, addToCart, getCartItems } = this.props;
        try {
            console.log("Adding to cart:", book.id);
            const updatedCart = await addToCart(book.id, 1);
            this.setState({ showToast: true });
            if (typeof onCartUpdate === "function") {
                onCartUpdate(updatedCart);
            }
        } catch (error) {
            console.error("Error adding to cart", error);
        }
    };

    renderStars(rating) {
        let stars = [];
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
    }

    render() {
        const { book } = this.props;
        const { showToast } = this.state;
        return (
            <>
                <Card className="book-card">
                    <div className="book-image-wrapper">
                        <Card.Img
                            variant="top"
                            src={book.cover_image}
                            className="book-image"
                        />
                        <div className="book-actions">
                            <Button variant="light" className="action-btn">
                                <FaHeart />
                            </Button>
                            <Button
                                variant="primary"
                                className="action-btn"
                                onClick={this.handleAddToCart}
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
                                {book.price?.toLocaleString("vi-VN")} đ
                            </span>
                            {book.originalPrice && (
                                <span className="original-price">
                                    {book.originalPrice?.toLocaleString(
                                        "vi-VN"
                                    )}{" "}
                                    đ
                                </span>
                            )}
                        </div>
                        {book.rating && (
                            <div className="book-rating">
                                <div className="stars">
                                    {this.renderStars(book.rating)}
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
                    onClose={() => this.setState({ showToast: false })}
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
                    <Toast.Body>
                        Đã thêm "{book.title}" vào giỏ hàng!
                    </Toast.Body>
                </Toast>
            </>
        );
    }
}

const mapDispatchToProps = {
    addToCart: addToCartAction,
    getCartItems: getCartItemsAction,
};

export default connect(null, mapDispatchToProps)(BookCard);
