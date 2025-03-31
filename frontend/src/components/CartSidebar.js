import React from "react";
import { Offcanvas, Button, Image, Form } from "react-bootstrap";
import { FaTrash } from "react-icons/fa";
import { Link } from "react-router-dom";
import { updateCartItemQuantity, removeFromCart } from "../utils/cartUtils";
import "./CartSidebar.css";

function CartSidebar({ show, handleClose, cartItems }) {
    const handleQuantityChange = (bookId, newQuantity) => {
        updateCartItemQuantity(bookId, parseInt(newQuantity));
    };

    const handleRemoveItem = (bookId) => {
        removeFromCart(bookId);
    };

    const calculateTotal = () => {
        return cartItems.reduce(
            (total, item) => total + item.price * item.quantity,
            0
        );
    };

    return (
        <Offcanvas
            show={show}
            onHide={handleClose}
            placement="end"
            className="cart-sidebar"
        >
            <Offcanvas.Header closeButton>
                <Offcanvas.Title>
                    Giỏ hàng ({cartItems.length} sản phẩm)
                </Offcanvas.Title>
            </Offcanvas.Header>
            <Offcanvas.Body>
                {cartItems.length === 0 ? (
                    <div className="empty-cart">
                        <p>Giỏ hàng trống</p>
                        <Button
                            variant="primary"
                            as={Link}
                            to="/"
                            onClick={handleClose}
                        >
                            Tiếp tục mua sắm
                        </Button>
                    </div>
                ) : (
                    <>
                        <div className="cart-items">
                            {cartItems.map((item) => (
                                <div key={item.id} className="cart-item">
                                    <Image
                                        src={item.image}
                                        alt={item.title}
                                        className="item-image"
                                    />
                                    <div className="item-details">
                                        <h6 className="item-title">
                                            {item.title}
                                        </h6>
                                        <p className="item-price">
                                            {item.price.toLocaleString("vi-VN")}
                                            đ
                                        </p>
                                        <div className="item-actions">
                                            <Form.Control
                                                type="number"
                                                min="1"
                                                value={item.quantity}
                                                onChange={(e) =>
                                                    handleQuantityChange(
                                                        item.id,
                                                        e.target.value
                                                    )
                                                }
                                                className="quantity-input"
                                            />
                                            <Button
                                                variant="link"
                                                className="remove-button"
                                                onClick={() =>
                                                    handleRemoveItem(item.id)
                                                }
                                            >
                                                <FaTrash />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="cart-summary">
                            <div className="summary-row">
                                <span>Tạm tính:</span>
                                <span>
                                    {calculateTotal().toLocaleString("vi-VN")}đ
                                </span>
                            </div>
                            <div className="summary-row total">
                                <span>Tổng cộng:</span>
                                <span>
                                    {calculateTotal().toLocaleString("vi-VN")}đ
                                </span>
                            </div>
                            <div className="cart-actions">
                                <Button
                                    variant="primary"
                                    className="checkout-button"
                                    as={Link}
                                    to="/checkout"
                                    onClick={handleClose}
                                >
                                    Thanh toán
                                </Button>
                                <Button
                                    variant="outline-primary"
                                    as={Link}
                                    to="/cart"
                                    onClick={handleClose}
                                >
                                    Xem giỏ hàng
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </Offcanvas.Body>
        </Offcanvas>
    );
}

export default CartSidebar;
