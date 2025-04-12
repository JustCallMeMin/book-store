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
class Header extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showCategories: false,
            showCart: false,
        };
    }

    componentDidMount() {
        this.props.getCartItems();
    }

    componentDidUpdate(prevProps) {
        const { cartItems, loading, error } = this.props;
        // Nếu có thay đổi từ props thì cập nhật loading và error (không lưu cartItems vào state vì đã có từ props)
        if (prevProps.cartItems !== cartItems) {
            this.setState({ loading: false });
        }
        if (prevProps.loading !== loading) {
            this.setState({ loading });
        }
        if (prevProps.error !== error) {
            this.setState({ error });
        }
    }

    render() {
        const { showCategories, showCart } = this.state;
        const { user, cartItems } = this.props;
        const cartItemCount =
            cartItems && cartItems.data ? cartItems.data.total_items : 0;

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
                    handleClose={() => this.setState({ showCart: false })}
                    cartItems={cartItems}
                />
            </>
        );
    }
}

const mapStateToProps = (state) => ({
    user: state.userReducer.user,
    cartItems: state.cartReducer.cartItems,
});

const mapDispatchToProps = (dispatch) => {
    return {
        getCartItems: () => dispatch(getCartItems()),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(Header);
