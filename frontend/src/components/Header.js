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
import { FaHeart, FaShoppingCart, FaSearch } from "react-icons/fa";
import { Link } from "react-router-dom";
import CartSidebar from "./CartSidebar";
import "./Header.css";
import AccountMenu from "./AccountMenu";
import { connect } from "react-redux";
import { getCartItems } from "./../store/actions/cart/cartAction";
import { getActiveCustomCategories } from "../store/actions/customCategory/customCategoryActions";

class Header extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showCart: false,
            showCategories: false,
        };
    }

    componentDidMount() {
        const { getCartItems, getActiveCustomCategories } = this.props;
        getCartItems();
        getActiveCustomCategories();
    }

    toggleCart = () => {
        this.setState((prevState) => ({ showCart: !prevState.showCart }));
    };

    toggleCategories = () => {
        this.setState((prevState) => ({
            showCategories: !prevState.showCategories,
        }));
    };

    showCategoriesOnHover = () => {
        this.setState({ showCategories: true });
    };

    hideCategoriesOnLeave = () => {
        this.setState({ showCategories: false });
    };

    render() {
        const { showCart, showCategories } = this.state;
        const { user, cartItems, customCategories, loading, error } =
            this.props;

        const cartItemCount = cartItems?.data?.total_items || 0;

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
                                    onClick={this.toggleCategories} // Hỗ trợ mobile
                                    onMouseEnter={this.showCategoriesOnHover} // Hỗ trợ desktop
                                    onMouseLeave={this.hideCategoriesOnLeave}
                                >
                                    {loading ? (
                                        <NavDropdown.Item disabled>
                                            Đang tải...
                                        </NavDropdown.Item>
                                    ) : error ? (
                                        <NavDropdown.Item disabled>
                                            Lỗi: {error}
                                        </NavDropdown.Item>
                                    ) : customCategories?.length > 0 ? (
                                        customCategories.map((category) => (
                                            <NavDropdown.Item
                                                key={category.id}
                                                as={Link}
                                                to={`/categories/${
                                                    category.slug || category.id
                                                }`}
                                            >
                                                {category.name}
                                            </NavDropdown.Item>
                                        ))
                                    ) : (
                                        <NavDropdown.Item disabled>
                                            Không có danh mục
                                        </NavDropdown.Item>
                                    )}
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
                                    onClick={this.toggleCart}
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
                                    {user ? (
                                        <AccountMenu user={user} />
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
                    handleClose={this.toggleCart}
                    cartItems={cartItems}
                />
            </>
        );
    }
}

const mapStateToProps = (state) => ({
    user: state.userReducer.user,
    cartItems: state.cartReducer.cartItems,
    customCategories: state.customCategoryReducer.activeCustomCategories, // ✅ Lấy activeCustomCategories
    loading: state.customCategoryReducer.loading,
    error: state.customCategoryReducer.error,
});

const mapDispatchToProps = {
    getCartItems,
    getActiveCustomCategories,
};

export default connect(mapStateToProps, mapDispatchToProps)(Header);
