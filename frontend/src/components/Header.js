import React, { useState, useEffect } from "react";
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

function Header() {
    const [showCategories, setShowCategories] = useState(false);
    const [cartItemCount, setCartItemCount] = useState(0);
    const [showCart, setShowCart] = useState(false);
    const [cartItems, setCartItems] = useState([]);

    useEffect(() => {
        // Cập nhật số lượng ban đầu
        const items = getCartItems();
        setCartItems(items);
        const count = items.reduce((total, item) => total + item.quantity, 0);
        setCartItemCount(count);

        // Lắng nghe custom event cartUpdated
        const handleCartUpdate = (e) => {
            const updatedItems = e.detail.cartItems;
            setCartItems(updatedItems);
            const newCount = updatedItems.reduce(
                (total, item) => total + item.quantity,
                0
            );
            setCartItemCount(newCount);
        };

        window.addEventListener("cartUpdated", handleCartUpdate);

        // Cleanup
        return () => {
            window.removeEventListener("cartUpdated", handleCartUpdate);
        };
    }, []);

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
                                onMouseEnter={() => setShowCategories(true)}
                                onMouseLeave={() => setShowCategories(false)}
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
                            <Button variant="primary" className="search-button">
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
                                onClick={() => setShowCart(true)}
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

                            <Nav.Link
                                as={Link}
                                to="/profile"
                                className="nav-icon"
                            >
                                <FaUser />
                            </Nav.Link>
                        </Nav>
                    </Navbar.Collapse>
                </Container>
            </Navbar>

            <CartSidebar
                show={showCart}
                handleClose={() => setShowCart(false)}
                cartItems={cartItems}
            />
        </>
    );
}

export default Header;
