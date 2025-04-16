import React, { Component } from "react";
import {
    Container,
    Row,
    Col,
    Card,
    Button,
    Toast,
    Alert,
    Breadcrumb,
    Form,
    InputGroup,
    Spinner,
} from "react-bootstrap";
import { FaShoppingCart } from "react-icons/fa";
import { connect } from "react-redux";
import { compose } from "redux";
import { fetchBook } from "src/store/actions/book/bookActions";
import {
    addToCart as addToCartAction,
    getCartItems as getCartItemsAction,
} from "src/store/actions/cart/cartAction";
import { withRouter } from "src/store/HOC/withRouter";
import "./BookDetail.css";

class BookDetail extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showToast: false,
            book: {},
            loading: true,
            error: null,
            quantity: 1,
            imageError: false,
            isAdding: false,
        };
    }

    componentDidMount() {
        const { fetchBook } = this.props;
        const bookId = this.props.params.id;
        fetchBook(bookId);
    }

    componentDidUpdate(prevProps) {
        const { book, loading, error } = this.props;
        if (prevProps.book !== book) {
            console.log("Updated book prop:", book);
            this.setState({
                book,
                loading: false,
                error: null,
                imageError: false,
                isAdding: false,
            });
        }
        if (prevProps.loading !== loading) {
            this.setState({ loading });
        }
        if (prevProps.error !== error) {
            this.setState({ error });
        }
    }

    handleAddToCart = async () => {
        const { book, quantity } = this.state;
        const { addToCart, onCartUpdate } = this.props;
        const bookData = book.data ? book.data : book;
        if (!bookData) return;
        this.setState({ isAdding: true });
        try {
            console.log("Adding to cart:", bookData.id, "Quantity:", quantity);
            const updatedCart = await addToCart(bookData.id, quantity);
            this.setState({ showToast: true });
            if (typeof onCartUpdate === "function") {
                onCartUpdate(updatedCart);
            }
        } catch (error) {
            console.error("Error adding to cart:", error);
        } finally {
            this.setState({ isAdding: false });
        }
    };

    handleQuantityChange = (e) => {
        const value = parseInt(e.target.value, 10);
        if (value >= 1) {
            this.setState({ quantity: value });
        }
    };

    handleImageError = (e) => {
        console.warn(`Failed to load image: ${e.target.src}`);
        this.setState({ imageError: true });
    };

    render() {
        const {
            book,
            loading,
            error,
            showToast,
            quantity,
            imageError,
            isAdding,
        } = this.state;
        const book1 = book.data ? book.data : book;
        const baseUrl =
            process.env.REACT_APP_API_URL || "http://localhost:8000";
        const imageSrc =
            imageError || !book1.cover_image
                ? "/media/placeholder.png"
                : book1.cover_image.startsWith("http")
                ? book1.cover_image
                : `${baseUrl}${book1.cover_image}`;

        console.log("BookDetail book:", book1);
        console.log("Image src:", imageSrc);
        console.log(
            "Stock value:",
            book1.quantity_in_stock,
            "Type:",
            typeof book1.quantity_in_stock
        );

        return (
            <Container className="book-detail-detail-page py-5">
                {/* Breadcrumb
                <Breadcrumb className="book-detail-breadcrumb mb-4">
                    <Breadcrumb.Item href="/">Trang chủ</Breadcrumb.Item>
                    <Breadcrumb.Item href="/categories">
                        Danh mục
                    </Breadcrumb.Item>
                    {book1.categories && book1.categories[0] ? (
                        <Breadcrumb.Item
                            href={`/category/${book1.categories[0].id}`}
                        >
                            {book1.categories[0].name}
                        </Breadcrumb.Item>
                    ) : (
                        <Breadcrumb.Item>Danh mục</Breadcrumb.Item>
                    )}
                    <Breadcrumb.Item active>
                        {book1.title || "Sách"}
                    </Breadcrumb.Item>
                </Breadcrumb> */}

                {/* Loading State */}
                {loading && (
                    <Row className="book-detail-skeleton">
                        <Col md={4}>
                            <div className="skeleton-image"></div>
                        </Col>
                        <Col md={8}>
                            <div className="skeleton-title mb-3"></div>
                            <div className="skeleton-text mb-2"></div>
                            <div className="skeleton-text mb-2"></div>
                            <div className="skeleton-text mb-4"></div>
                            <div className="skeleton-button"></div>
                        </Col>
                    </Row>
                )}

                {/* Error State */}
                {error && (
                    <Alert variant="danger" className="text-center p-4">
                        <h4>Lỗi: {error}</h4>
                        <p>Vui lòng thử lại sau hoặc liên hệ hỗ trợ.</p>
                        <Button
                            variant="outline-danger"
                            onClick={() =>
                                this.props.fetchBook(this.props.params.id)
                            }
                        >
                            Thử lại
                        </Button>
                    </Alert>
                )}

                {/* Book Details */}
                {!loading && !error && book1 && (
                    <Row className="book-detail-content animate-fade-in">
                        <Col md={5}>
                            <Card className="book-detail-image-card">
                                <Card.Img
                                    src={imageSrc}
                                    alt={book1.title || "Book image"}
                                    className="book-detail-image"
                                    onError={this.handleImageError}
                                />
                            </Card>
                        </Col>
                        <Col md={7}>
                            <h1 className="book-detail-title">
                                {book1.title || "Không có tiêu đề"}
                            </h1>
                            <div className="book-detail-meta mb-3">
                                <p>
                                    <strong>Tác giả:</strong>{" "}
                                    {book1.authors &&
                                    Array.isArray(book1.authors)
                                        ? book1.authors
                                              .map((author) => author.name)
                                              .join(", ")
                                        : "Không rõ"}
                                </p>
                                <p>
                                    <strong>Thể loại:</strong>{" "}
                                    {book1.categories &&
                                    Array.isArray(book1.categories)
                                        ? book1.categories
                                              .map((category) => category.name)
                                              .join(", ")
                                        : "Không rõ"}
                                </p>
                                <p>
                                    <strong>Ngày xuất bản:</strong>{" "}
                                    {book1.published_date
                                        ? new Date(
                                              book1.published_date
                                          ).toLocaleDateString("vi-VN", {
                                              day: "2-digit",
                                              month: "2-digit",
                                              year: "numeric",
                                          })
                                        : "N/A"}
                                </p>
                            </div>
                            {/* <div className="book-detail-rating mb-3">
                                <span className="text-warning">★</span>{" "}
                                <span>
                                    {book1.rating
                                        ? `${book1.rating}/5`
                                        : "Chưa có đánh giá"}
                                </span>
                            </div> */}
                            <div className="book-detail-price mb-3">
                                <strong>Giá:</strong>{" "}
                                {book1.price
                                    ? `${parseFloat(book1.price).toLocaleString(
                                          "vi-VN"
                                      )} đ`
                                    : "Liên hệ để biết giá"}
                            </div>
                            <div className="book-detail-quantity_in_stock mb-4">
                                <strong>Trạng thái:</strong>{" "}
                                {book1.quantity_in_stock > 0 ? (
                                    <span className="text-success">
                                        Còn hàng
                                    </span>
                                ) : (
                                    <span className="text-danger">
                                        Hết hàng
                                    </span>
                                )}
                            </div>
                            <p className="book-detail-description">
                                <strong>Mô tả:</strong>{" "}
                                {book1.description ||
                                    "Không có mô tả chi tiết."}
                            </p>
                            {Number(book1.quantity_in_stock) > 0 ? (
                                <div className=" mb-4">
                                    {/* <InputGroup
                                        className="quantity-selector mb-3"
                                        style={{ maxWidth: "150px" }}
                                    >
                                        <InputGroup.Text>
                                            Số lượng:
                                        </InputGroup.Text>
                                        <Form.Control
                                            type="number"
                                            min="1"
                                            value={quantity}
                                            onChange={this.handleQuantityChange}
                                            style={{ width: "80px" }}
                                        />
                                    </InputGroup> */}
                                    <Button
                                        variant="primary"
                                        className="action-button me-3"
                                        onClick={this.handleAddToCart}
                                        disabled={isAdding}
                                    >
                                        {isAdding ? (
                                            <Spinner
                                                size="sm"
                                                animation="border"
                                            />
                                        ) : (
                                            <>
                                                <FaShoppingCart className="me-2" />
                                                Thêm vào giỏ hàng
                                            </>
                                        )}
                                    </Button>
                                    {/* <Button
                                        variant="success"
                                        className="action-button"
                                    >
                                        Mua ngay
                                    </Button> */}
                                </div>
                            ) : (
                                <Alert variant="warning" className="mt-3">
                                    Sách này hiện đã hết hàng. Vui lòng quay lại
                                    sau!
                                </Alert>
                            )}
                        </Col>
                    </Row>
                )}

                {/* Toast Notification */}
                <Toast
                    show={showToast}
                    onClose={() => this.setState({ showToast: false })}
                    delay={3000}
                    autohide
                    className="cart-toast"
                >
                    <Toast.Header>
                        <strong className="me-auto">Thông báo</strong>
                    </Toast.Header>
                    <Toast.Body>
                        Đã thêm "{book1.title || "sách"}" vào giỏ hàng!
                    </Toast.Body>
                </Toast>
            </Container>
        );
    }
}

const mapStateToProps = (state) => {
    return {
        book: state.bookReducer.book,
        loading: state.bookReducer.loading,
        error: state.bookReducer.error,
    };
};

const mapDispatchToProps = {
    fetchBook,
    addToCart: addToCartAction,
    getCartItems: getCartItemsAction,
};

export default compose(
    connect(mapStateToProps, mapDispatchToProps),
    withRouter
)(BookDetail);
