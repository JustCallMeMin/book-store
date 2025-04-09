import React, { Component } from "react";
import { Container, Row, Col, Card, Button, Toast } from "react-bootstrap";
// import "./BookDetail.css";
import { connect } from "react-redux";
import { compose } from "redux";
import { fetchBook } from "src/store/actions/book/bookActions";
import { withRouter } from "src/store/HOC/withRouter";
import { addToCart } from "src/utils/cartUtils";

class BookDetail extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showToast: false,
            book: {},
            loading: true,
            error: null,
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
            this.setState({ book, loading: false, error: null });
        }
        if (prevProps.loading !== loading) {
            this.setState({ loading });
        }
        if (prevProps.error !== error) {
            this.setState({ error });
        }
    }

    handleAddToCart = () => {
        const book = this.state.book.data;
        if (!book) return;
        console.log("BookDetail book", book);
        const updatedCart = addToCart(book);
        this.setState({ showToast: true });
        const { onCartUpdate } = this.props;
        if (typeof onCartUpdate === "function") {
            onCartUpdate(updatedCart);
        }
    };

    render() {
        const { book, loading, error } = this.state;
        const book1 = book.data ? book.data : book;
        console.log("BookDetail book", book1);

        if (loading) {
            return (
                <Container
                    style={{
                        minHeight: "70vh",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                    }}
                >
                    <div>Loading...</div>
                </Container>
            );
        }

        if (error) {
            return (
                <Container
                    style={{
                        minHeight: "70vh",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                    }}
                >
                    <div>{error}</div>
                </Container>
            );
        }

        return (
            <Container className="book-detail my-5">
                <Row>
                    <Col md={4}>
                        <Card>
                            <Card.Img
                                variant="top"
                                src={book1.cover_image}
                                alt={book1.title}
                            />
                        </Card>
                    </Col>
                    <Col md={8}>
                        <h2>{book1.title}</h2>
                        <p>
                            <strong>Tác giả:</strong>{" "}
                            {book1.authors && Array.isArray(book1.authors)
                                ? book1.authors
                                      .map((author) => author.name)
                                      .join(", ")
                                : "Unknown"}
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
                        <p>
                            <strong>Thể loại:</strong>{" "}
                            {book1.categories && Array.isArray(book1.categories)
                                ? book1.categories
                                      .map((category) => category.name)
                                      .join(", ")
                                : "Unknown"}
                        </p>
                        <p>
                            <strong>Mô tả:</strong>{" "}
                            {book1.description || "No description available."}
                        </p>
                        <Button variant="primary">Mua Ngay</Button>
                        <Button
                            variant="primary"
                            className="mx-2"
                            onClick={this.handleAddToCart}
                        >
                            Thêm Vào Giỏ Hàng
                        </Button>
                    </Col>
                </Row>
                <Toast
                    show={this.state.showToast}
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
                        Đã thêm "{book1.title}" vào giỏ hàng!
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

const mapDispatchToProps = (dispatch) => {
    return {
        fetchBook: (bookId) => dispatch(fetchBook(bookId)),
    };
};

export default compose(
    connect(mapStateToProps, mapDispatchToProps),
    withRouter
)(BookDetail);
