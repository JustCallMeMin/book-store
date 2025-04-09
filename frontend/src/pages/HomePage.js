import React, { Component } from "react";
import {
    Container,
    Row,
    Col,
    Button,
    Carousel,
    Pagination,
} from "react-bootstrap";
import BookCard from "../components/BookCard";
import "./HomePage.css";
import { connect } from "react-redux";
import { fetchBooks } from "src/store/actions/book/bookActions";

class HomePage extends Component {
    constructor(props) {
        super(props);
        this.state = {
            page: 1,
            per_page: 8, // number of books per page
        };
    }

    componentDidMount() {
        const { fetchBooks } = this.props;
        const { page, per_page } = this.state;
        fetchBooks(page, per_page);
    }

    handlePageChange = (pageNumber) => {
        this.setState({ page: pageNumber }, () => {
            this.props.fetchBooks(this.state.page, this.state.per_page);
        });
    };

    renderPagination() {
        const { books } = this.props;
        if (books && books.data && books.data.pagination.total) {
            const totalBooks = books.data.pagination.total;
            const { per_page, page } = this.state;
            const totalPages = Math.ceil(totalBooks / per_page);
            if (totalPages <= 1) return null;

            const chunkSize = 10;
            // Determine the current chunk index (0-based)
            const currentChunk = Math.floor((page - 1) / chunkSize);
            const startPage = currentChunk * chunkSize + 1;
            const endPage = Math.min(startPage + chunkSize - 1, totalPages);

            let items = [];
            // Previous chunk button
            if (currentChunk > 0) {
                items.push(
                    <Pagination.Prev
                        key="prev"
                        onClick={() => this.handlePageChange(startPage - 1)}
                    />
                );
            }

            // Page number buttons for the current chunk
            for (let number = startPage; number <= endPage; number++) {
                items.push(
                    <Pagination.Item
                        key={number}
                        active={number === page}
                        onClick={() => this.handlePageChange(number)}
                    >
                        {number}
                    </Pagination.Item>
                );
            }

            // Next chunk button
            if (endPage < totalPages) {
                items.push(
                    <Pagination.Next
                        key="next"
                        onClick={() => this.handlePageChange(endPage + 1)}
                    />
                );
            }

            return (
                <Pagination className="justify-content-center mt-4">
                    {items}
                </Pagination>
            );
        }
        return null;
    }

    render() {
        const { books, loading } = this.props;
        if (loading) {
            return (
                <Container>
                    <div>Loading...</div>
                </Container>
            );
        }
        // Extract the book list from the API response.
        // Here we assume the response is: { data: { count, books: [...] } }
        const bookList =
            books && books.data && Array.isArray(books.data.books)
                ? books.data.books
                : [];
        const banners = [
            {
                id: 1,
                image: "/media/banner1.png",
                title: "Sách mới tháng 4",
                description: "Khám phá những cuốn sách mới nhất",
            },
            {
                id: 2,
                image: "/media/banner2.png",
                title: "Giảm giá 30%",
                description: "Cho tất cả sách văn học",
            },
        ];
        return (
            <div className="home-page">
                <Carousel className="main-banner">
                    {banners.map((banner) => (
                        <Carousel.Item key={banner.id}>
                            <img
                                className="d-block w-100"
                                src={banner.image}
                                alt={banner.title}
                            />
                            <Carousel.Caption>
                                <h3>{banner.title}</h3>
                                <p>{banner.description}</p>
                                <Button variant="primary">Xem ngay</Button>
                            </Carousel.Caption>
                        </Carousel.Item>
                    ))}
                </Carousel>
                <Container>
                    <section className="book-section">
                        <div className="section-header">
                            <h2>Danh Sách Sách</h2>
                            <Button variant="outline-primary">
                                Xem tất cả
                            </Button>
                        </div>
                        <Row>
                            {Array.isArray(bookList) &&
                                bookList.map((book) => (
                                    <Col
                                        key={book.id}
                                        xs={12}
                                        sm={6}
                                        md={4}
                                        lg={3}
                                        className="mb-4"
                                    >
                                        <BookCard
                                            book={book}
                                            onCartUpdate={undefined}
                                        />
                                    </Col>
                                ))}
                        </Row>
                        {/* Render pagination below the book list */}
                        {this.renderPagination()}
                    </section>
                </Container>
            </div>
        );
    }
}

const mapStateToProps = (state) => {
    return {
        error: state.bookReducer.error,
        loading: state.bookReducer.loading,
        books: state.bookReducer.books,
    };
};

const mapDispatchToProps = (dispatch) => {
    return {
        fetchBooks: (page, per_page) => dispatch(fetchBooks(page, per_page)),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(HomePage);
