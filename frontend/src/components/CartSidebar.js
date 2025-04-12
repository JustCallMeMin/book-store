import React, { Component } from "react";
import { Offcanvas, Button, Image, Form } from "react-bootstrap";
import { FaTrash } from "react-icons/fa";
import { Link } from "react-router-dom";
import { connect } from "react-redux";
import {
    getCartItems,
    updateCartItemQuantity,
    clearCart,
} from "./../store/actions/cart/cartAction";
import "./CartSidebar.css";

class CartSidebar extends Component {
    constructor(props) {
        super(props);
        this.state = {
            loading: true,
            error: null,
        };
    }

    componentDidMount() {
        const { getCartItems } = this.props;
        getCartItems();
    }

    componentDidUpdate(prevProps) {
        const { cartItems, loading, error } = this.props;
        // Nếu có thay đổi từ props thì cập nhật loading và error (không lưu cartItems vào state vì đã có từ props)
        if (prevProps.cartItems !== cartItems) {
            this.setState({ cartItems: cartItems, loading: false });
        }
        if (prevProps.loading !== loading) {
            this.setState({ loading });
        }
        if (prevProps.error !== error) {
            this.setState({ error });
        }
    }

    handleQuantityChange = (bookId, newQuantity) => {
        this.props.updateCartItemQuantity(bookId, parseInt(newQuantity, 10));
    };

    handleRemoveItem = (bookId) => {
        this.props.updateCartItemQuantity(bookId, 0);
    };

    handleClearCart = () => {
        this.props.clearCart();
    };
    render() {
        const { show, handleClose, cartItems } = this.props;
        // Nếu cartItems không có data hay rỗng, cung cấp các giá trị mặc định:
        const data =
            cartItems && cartItems.data
                ? cartItems.data
                : {
                      items: [],
                      total_amount: 0,
                      final_amount: 0,
                      total_items: 0,
                  };
        const carts = data.items;
        const total_amount = data.total_amount;
        const final_amount = data.final_amount;
        const total_items = data.total_items;

        return (
            <Offcanvas
                show={show}
                onHide={handleClose}
                placement="end"
                className="cart-sidebar"
            >
                <Offcanvas.Header closeButton>
                    <Offcanvas.Title>
                        Giỏ hàng ({total_items} sản phẩm)
                    </Offcanvas.Title>
                </Offcanvas.Header>
                <Offcanvas.Body>
                    {carts.length === 0 ? (
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
                                {carts.map((item) => (
                                    <div
                                        key={item.book_id}
                                        className="cart-item"
                                    >
                                        <Image
                                            src={item.cover_image}
                                            alt={item.title}
                                            className="item-image"
                                        />
                                        <div className="item-details">
                                            <h6 className="item-title">
                                                {item.title}
                                            </h6>
                                            <p className="item-price">
                                                {item.unit_price
                                                    ? item.unit_price.toLocaleString(
                                                          "vi-VN"
                                                      )
                                                    : "0"}{" "}
                                                đ
                                            </p>
                                            <div className="item-actions">
                                                <Form.Control
                                                    type="number"
                                                    min="1"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        this.handleQuantityChange(
                                                            item.book_id,
                                                            e.target.value
                                                        )
                                                    }
                                                    className="quantity-input"
                                                />
                                                <Button
                                                    variant="link"
                                                    className="remove-button"
                                                    onClick={() =>
                                                        this.handleRemoveItem(
                                                            item.book_id
                                                        )
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
                                        {total_amount.toLocaleString("vi-VN")}đ
                                    </span>
                                </div>
                                <div className="summary-row total">
                                    <span>Tổng cộng:</span>
                                    <span>
                                        {final_amount.toLocaleString("vi-VN")}đ
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
                                        onClick={() => this.handleClearCart()}
                                    >
                                        Xoá Giỏ Hàng
                                    </Button>
                                    {
                                        //     <Button
                                        //     variant="outline-primary"
                                        //     as={Link}
                                        //     to="/cart"
                                        //     onClick={handleClose}
                                        // >
                                        //     Xem giỏ hàng
                                        // </Button>
                                    }
                                </div>
                            </div>
                        </>
                    )}
                </Offcanvas.Body>
            </Offcanvas>
        );
    }
}

const mapStateToProps = (state) => ({
    cartItems: state.cartReducer.cartItems,
});

const mapDispatchToProps = (dispatch) => {
    return {
        getCartItems: () => dispatch(getCartItems()),
        updateCartItemQuantity: (book_id, quantity) =>
            dispatch(updateCartItemQuantity(book_id, quantity)),
        clearCart: () => dispatch(clearCart()),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(CartSidebar);
