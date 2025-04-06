import React, { Component } from "react";
import {
    Navbar,
    Container,
    Nav,
    Form,
    Button,
    Badge,
    NavDropdown,
} from "react-bootstrap";
import { FaHeart, FaShoppingCart, FaUser, FaSearch } from "react-icons/fa";
import { Link } from "react-router-dom";
import { getCartItems } from "../utils/cartUtils";
import CartSidebar from "./CartSidebar";
import "./Header.css";
import AccountMenu from "./AccountMenu";
import { connect } from "react-redux";
import { m } from "framer-motion";

class Header extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showCategories: false,
            cartItemCount: 0,
            showCart: false,
            cartItems: [],
        };
    }

    componentDidUpdate(prevProps) {
        if (prevProps.user !== this.props.user) {
            // Handle user state change if needed
        }
    }

    componentDidMount() {
        console.log("User state changed:", this.props.user);
        const items = getCartItems();
        this.setState({
            cartItems: items,
            cartItemCount: items.reduce(
                (total, item) => total + item.quantity,
                0
            ),
        });

        this.handleCartUpdate = (e) => {
            const updatedItems = e.detail.cartItems;
            this.setState({
                cartItems: updatedItems,
                cartItemCount: updatedItems.reduce(
                    (total, item) => total + item.quantity,
                    0
                ),
            });
        };

        window.addEventListener("cartUpdated", this.handleCartUpdate);
    }

    componentWillUnmount() {
        window.removeEventListener("cartUpdated", this.handleCartUpdate);
    }

    render() {
        const { showCategories, cartItemCount, showCart, cartItems } =
            this.state;

        const categories = [
            { name: "Văn học", path: "/categories/van-hoc" },
            { name: "Kinh tế", path: "/categories/kinh-te" },
            { name: "Tâm lý - Kỹ năng sống", path: "/categories/tam-ly" },
            { name: "Nuôi dạy con", path: "/categories/nuoi-day-con" },
            { name: "Sách giáo khoa", path: "/categories/sach-giao-khoa" },
            { name: "Học ngoại ngữ", path: "/categories/ngoai-ngu" },
        ];

        return (
            <>
                <Navbar expand="lg" className="custom-navbar">
                    <Container>
                        <Navbar.Brand as={Link} to="/" className="brand">
                            BookStore
                        </Navbar.Brand>

                        <Navbar.Toggle aria-controls="basic-navbar-nav" />

                        <Navbar.Collapse id="basic-navbar-nav">
                            <Nav className="me-auto">
                                <NavDropdown
                                    title="Danh mục sách"
                                    id="basic-nav-dropdown"
                                    className="category-dropdown"
                                    show={showCategories}
                                    onMouseEnter={() =>
                                        this.setState({ showCategories: true })
                                    }
                                    onMouseLeave={() =>
                                        this.setState({ showCategories: false })
                                    }
                                >
                                    {categories.map((category, index) => (
                                        <NavDropdown.Item
                                            key={index}
                                            as={Link}
                                            to={category.path}
                                        >
                                            {category.name}
                                        </NavDropdown.Item>
                                    ))}
                                </NavDropdown>
                            </Nav>

                            <Form className="search-form">
                                <Form.Control
                                    type="search"
                                    placeholder="Tìm kiếm sách, tác giả..."
                                    className="search-input"
                                />
                                <Button
                                    variant="primary"
                                    className="search-button"
                                >
                                    <FaSearch />
                                </Button>
                            </Form>

                            <Nav className="nav-icons">
                                <Nav.Link
                                    as={Link}
                                    to="/wishlist"
                                    className="nav-icon"
                                >
                                    <FaHeart />
                                </Nav.Link>

                                <Nav.Link
                                    className="nav-icon"
                                    onClick={() =>
                                        this.setState({ showCart: true })
                                    }
                                >
                                    <div className="cart-icon-container">
                                        <FaShoppingCart />
                                        {cartItemCount > 0 && (
                                            <Badge
                                                pill
                                                bg="danger"
                                                className="cart-badge"
                                            >
                                                {cartItemCount}
                                            </Badge>
                                        )}
                                    </div>
                                </Nav.Link>

                                <Nav>
                                    {this.props.user ? (
                                        <AccountMenu user={this.props.user} />
                                    ) : (
                                        <>
                                            <Nav.Link as={Link} to="/login">
                                                Đăng nhập
                                            </Nav.Link>
                                            <Nav.Link as={Link} to="/register">
                                                Đăng ký
                                            </Nav.Link>
                                        </>
                                    )}
                                </Nav>
                            </Nav>
                        </Navbar.Collapse>
                    </Container>
                </Navbar>

                <CartSidebar
                    show={showCart}
                    handleClose={() => this.setState({ showCart: false })}
                    cartItems={cartItems}
                />
            </>
        );
    }
}

const mapStateToProps = (state) => ({
    user: state.userReducer.user,
});

export default connect(mapStateToProps)(Header);
