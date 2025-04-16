import React, { Component } from "react";
import { connect } from "react-redux";
import { Container, Row, Col, Card, Spinner } from "react-bootstrap";
import { getCategory } from "../store/actions/customCategory/customCategoryActions";
import { fetchBooksByFilter } from "../store/actions/book/bookActions";
import BookCard from "../components/BookCard";
import { withRouter } from "../store/HOC/withRouter"; // Import HOC withRouter
import { motion } from "framer-motion"; // Import Framer Motion
import "./CategoryBooks.css"; // Import CSS file for custom styles

class CategoryBooks extends Component {
    componentDidMount() {
        const { categoryId } = this.props.params; // Lấy categoryId từ URL
        this.props.getCategory(categoryId); // Gọi action để lấy dữ liệu category
    }

    componentDidUpdate(prevProps) {
        const { category } = this.props;

        // Gọi lại API nếu category thay đổi
        if (prevProps.category !== category && category?.url) {
            this.props.fetchBooksByFilter(category.url); // Gọi API với category.url
        }
    }

    render() {
        const { category, books, loading, error } = this.props;

        if (loading) {
            return (
                <div className="loading-container">
                    <Spinner animation="border" variant="primary" />
                    <p>Đang tải dữ liệu...</p>
                </div>
            );
        }

        if (error) {
            return (
                <Card className="text-center p-5">
                    <Card.Body>
                        <h4 className="text-danger">{error}</h4>
                    </Card.Body>
                </Card>
            );
        }

        if (!category) {
            return (
                <Card className="text-center p-5">
                    <Card.Body>
                        <h4>Không tìm thấy danh mục</h4>
                    </Card.Body>
                </Card>
            );
        }

        return (
            <motion.div
                className="category-books-page"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -20 }}
                transition={{ duration: 0.5 }}
            >
                <Container className="py-4">
                    {/* Header Section */}
                    <Row className="mb-4">
                        <Col>
                            <div className="category-header text-center">
                                <h2 className="category-title">
                                    {category.name}
                                </h2>
                                <p className="category-description">
                                    {category.description || "Danh mục sách"}
                                </p>
                                <p className="category-count">
                                    {books.length} sách trong danh mục này
                                </p>
                            </div>
                        </Col>
                    </Row>

                    {/* Book Grid */}
                    <Row xs={1} md={2} lg={4} className="g-4">
                        {Array.isArray(books) && books.length > 0 ? (
                            books.map((book) => (
                                <Col key={book.id}>
                                    <BookCard book={book} />
                                </Col>
                            ))
                        ) : (
                            <Col>
                                <Card className="text-center p-5">
                                    <Card.Body>
                                        <h4>
                                            Không tìm thấy sách nào trong danh
                                            mục này
                                        </h4>
                                    </Card.Body>
                                </Card>
                            </Col>
                        )}
                    </Row>
                </Container>
            </motion.div>
        );
    }
}

const mapStateToProps = (state) => ({
    category: state.customCategoryReducer.category,
    books: state.bookReducer.books,
    loading: state.bookReducer.loading || state.customCategoryReducer.loading,
    error: state.bookReducer.error || state.customCategoryReducer.error,
});

const mapDispatchToProps = {
    getCategory,
    fetchBooksByFilter,
};

export default connect(
    mapStateToProps,
    mapDispatchToProps
)(withRouter(CategoryBooks));
