import React, { useState, useEffect, useCallback } from "react";
import {
    Container,
    Row,
    Col,
    Button,
    Carousel,
    Pagination,
    Form,
    InputGroup,
    Alert,
    Offcanvas,
} from "react-bootstrap";
import { debounce } from "lodash";
import { motion } from "framer-motion"; // Import motion từ Framer Motion
import BookCard from "../components/BookCard";
import "./HomePage.css";
import { connect } from "react-redux";
import { fetchBooks } from "src/store/actions/book/bookActions";
import { FaFilter, FaSearch } from "react-icons/fa";

const HomePage = ({ books, loading, error, fetchBooks }) => {
    const [page, setPage] = useState(1);
    const [perPage] = useState(10);
    const [showFilters, setShowFilters] = useState(false);
    const [filters, setFilters] = useState({
        search: "",
        category: "",
        language: "",
        price_min: "",
        price_max: "",
        sort_by: "download_count",
        sort_direction: "desc",
    });

    // Debounced fetchBooks
    const debouncedFetchBooks = useCallback(
        debounce((newFilters, newPage) => {
            fetchBooks({ ...newFilters, page: newPage, per_page: perPage });
        }, 2000), // Delay 500ms
        [fetchBooks, perPage]
    );

    // Fetch books on mount and when filters/page change
    useEffect(() => {
        fetchBooks({ ...filters, page, per_page: perPage });
    }, [fetchBooks, page, filters, perPage]);

    const handleFilterChange = (e) => {
        const { name, value } = e.target;
        setFilters((prevFilters) => {
            const newFilters = { ...prevFilters, [name]: value };
            debouncedFetchBooks(newFilters, 1); // Gọi API với debounce
            return newFilters;
        });
        setPage(1);
    };

    const handleFilterSubmit = (e) => {
        e.preventDefault();
        setPage(1);
        fetchBooks({ ...filters, page: 1, per_page: perPage });
        setShowFilters(false);
    };

    const handleResetFilters = () => {
        const resetFilters = {
            search: "",
            category: "",
            language: "",
            price_min: "",
            price_max: "",
            sort_by: "download_count",
            sort_direction: "desc",
        };
        setFilters(resetFilters);
        setPage(1);
        fetchBooks({ ...resetFilters, page: 1, per_page: perPage });
        setShowFilters(false);
    };

    const handlePageChange = (pageNumber) => {
        setPage(pageNumber);
        fetchBooks({ ...filters, page: pageNumber, per_page: perPage });
    };

    const renderPagination = () => {
        if (!books?.data?.pagination?.total) return null;

        const totalBooks = books.data.pagination.total;
        const totalPages = Math.ceil(totalBooks / perPage);
        if (totalPages <= 1) return null;

        const chunkSize = 5;
        const currentChunk = Math.floor((page - 1) / chunkSize);
        const startPage = currentChunk * chunkSize + 1;
        const endPage = Math.min(startPage + chunkSize - 1, totalPages);

        const items = [];
        if (page > 1) {
            items.push(
                <Pagination.Prev
                    key="prev"
                    onClick={() => handlePageChange(page - 1)}
                />
            );
        }

        for (let number = startPage; number <= endPage; number++) {
            items.push(
                <Pagination.Item
                    key={number}
                    active={number === page}
                    onClick={() => handlePageChange(number)}
                >
                    {number}
                </Pagination.Item>
            );
        }

        if (page < totalPages) {
            items.push(
                <Pagination.Next
                    key="next"
                    onClick={() => handlePageChange(page + 1)}
                />
            );
        }

        return (
            <Pagination className="justify-content-center mt-4">
                {items}
            </Pagination>
        );
    };

    const bookList =
        books && books.data && Array.isArray(books.data.books)
            ? books.data.books
            : [];

    const banners = [
        {
            id: 1,
            image: "/media/banner1.png",
            title: "Khám Phá Thế Giới Sách",
            description: "Hàng ngàn cuốn sách chờ bạn khám phá!",
        },
        {
            id: 2,
            image: "/media/banner2.png",
            title: "Ưu Đãi Đặc Biệt",
            description: "Giảm giá lên đến 40% cho sách mới!",
        },
    ];

    return (
        <motion.div
            className="home-page"
            initial={{ opacity: 0, y: 20 }} // Bắt đầu với opacity = 0 và dịch xuống 20px
            animate={{ opacity: 1, y: 0 }} // Hiệu ứng fade-in và dịch lên
            exit={{ opacity: 0, y: -20 }} // Hiệu ứng fade-out và dịch lên
            transition={{ duration: 0.5 }} // Thời gian chuyển đổi 0.5s
        >
            {/* Hero Banner */}
            <motion.div
                className="main-banner"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                transition={{ duration: 0.8 }}
            >
                <Carousel interval={5000} pause="hover">
                    {banners.map((banner) => (
                        <Carousel.Item key={banner.id}>
                            <img
                                className="d-block w-100"
                                src={banner.image}
                                alt={banner.title}
                            />
                            <Carousel.Caption className="banner-caption">
                                <div className="banner-content">
                                    <div className="banner-text">
                                        <h1>{banner.title}</h1>
                                        <p>{banner.description}</p>
                                    </div>
                                    <div className="banner-action">
                                        <Button variant="primary" size="lg">
                                            Xem Ngay
                                        </Button>
                                    </div>
                                </div>
                            </Carousel.Caption>
                        </Carousel.Item>
                    ))}
                </Carousel>
            </motion.div>

            <Container className="main-content">
                {/* Filter Button */}
                <div className="filter-toggle-section">
                    <Button
                        variant="outline-primary"
                        onClick={() => setShowFilters(true)}
                        className="filter-toggle"
                    >
                        <FaFilter /> Bộ Lọc
                    </Button>
                </div>

                {/* Filter Offcanvas */}
                <Offcanvas
                    show={showFilters}
                    onHide={() => setShowFilters(false)}
                    placement="end"
                    className="filter-offcanvas"
                >
                    <Offcanvas.Header closeButton>
                        <Offcanvas.Title>Bộ Lọc Sách</Offcanvas.Title>
                    </Offcanvas.Header>
                    <Offcanvas.Body>
                        {error && (
                            <Alert variant="danger" className="mb-3">
                                {error}
                            </Alert>
                        )}
                        <Form onSubmit={handleFilterSubmit}>
                            <Form.Group className="mb-3">
                                <Form.Label>Tìm Kiếm</Form.Label>
                                <InputGroup>
                                    <InputGroup.Text>
                                        <FaSearch />
                                    </InputGroup.Text>
                                    <Form.Control
                                        type="text"
                                        name="search"
                                        value={filters.search}
                                        onChange={handleFilterChange}
                                        placeholder="Tìm theo tên sách..."
                                    />
                                </InputGroup>
                            </Form.Group>
                            <Form.Group className="mb-3">
                                <Form.Label>Thể Loại</Form.Label>
                                <Form.Control
                                    type="text"
                                    name="category"
                                    value={filters.category}
                                    onChange={handleFilterChange}
                                    placeholder="Nhập thể loại (e.g., fiction)"
                                />
                            </Form.Group>
                            <Form.Group className="mb-3">
                                <Form.Label>Ngôn Ngữ</Form.Label>
                                <Form.Select
                                    name="language"
                                    value={filters.language}
                                    onChange={handleFilterChange}
                                >
                                    <option value="">Tất cả</option>
                                    <option value="en">Tiếng Anh</option>
                                    <option value="vi">Tiếng Việt</option>
                                </Form.Select>
                            </Form.Group>
                            <Form.Group className="mb-3">
                                <Form.Label>Giá (VNĐ)</Form.Label>
                                <Row>
                                    <Col xs={6}>
                                        <Form.Control
                                            type="number"
                                            name="price_min"
                                            value={filters.price_min}
                                            onChange={handleFilterChange}
                                            placeholder="Tối thiểu"
                                            min="0"
                                        />
                                    </Col>
                                    <Col xs={6}>
                                        <Form.Control
                                            type="number"
                                            name="price_max"
                                            value={filters.price_max}
                                            onChange={handleFilterChange}
                                            placeholder="Tối đa"
                                            min="0"
                                        />
                                    </Col>
                                </Row>
                            </Form.Group>
                            <Form.Group className="mb-3">
                                <Form.Label>Sắp Xếp</Form.Label>
                                <Form.Select
                                    name="sort_by"
                                    value={filters.sort_by}
                                    onChange={handleFilterChange}
                                >
                                    <option value="download_count">
                                        Lượt tải
                                    </option>
                                    <option value="price">Giá</option>
                                    <option value="published_year">
                                        Năm xuất bản
                                    </option>
                                </Form.Select>
                            </Form.Group>
                            <Form.Group className="mb-3">
                                <Form.Label>Hướng Sắp Xếp</Form.Label>
                                <Form.Select
                                    name="sort_direction"
                                    value={filters.sort_direction}
                                    onChange={handleFilterChange}
                                >
                                    <option value="desc">Giảm dần</option>
                                    <option value="asc">Tăng dần</option>
                                </Form.Select>
                            </Form.Group>
                            <div className="d-flex flex-column gap-2">
                                <Button
                                    variant="secondary"
                                    onClick={handleResetFilters}
                                >
                                    Xóa Bộ Lọc
                                </Button>
                                <Button variant="primary" type="submit">
                                    Áp Dụng
                                </Button>
                            </div>
                        </Form>
                    </Offcanvas.Body>
                </Offcanvas>

                {/* Promotional Banner */}
                <div className="promo-banner mt-4">
                    <img
                        src="/media/promo-banner.jpg"
                        alt="Khuyến mãi"
                        className="w-100"
                    />
                    <div className="promo-content">
                        <h3>Ưu Đãi Hôm Nay</h3>
                        <p>Giảm giá 30% cho tất cả sách mới!</p>
                        <Button variant="warning">Mua Ngay</Button>
                    </div>
                </div>

                {/* Book Grid */}
                <motion.section
                    className="book-section mt-4"
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ duration: 0.8 }}
                >
                    <div className="section-header">
                        <h2>Sách Nổi Bật</h2>
                        <Button variant="outline-primary">Xem Tất Cả</Button>
                    </div>
                    <Row className="book-grid">
                        {loading ? (
                            Array.from({ length: perPage }).map((_, index) => (
                                <Col
                                    key={`skeleton-${index}`}
                                    xs={12}
                                    sm={6}
                                    md={4}
                                    lg={2.4}
                                    className="mb-4 book-col"
                                >
                                    <BookCard isSkeleton={true} />
                                </Col>
                            ))
                        ) : bookList.length > 0 ? (
                            bookList.map((book) => (
                                <Col
                                    key={book.id}
                                    xs={12}
                                    sm={6}
                                    md={4}
                                    lg={2.4}
                                    className="mb-4 book-col"
                                >
                                    <BookCard book={book} />
                                </Col>
                            ))
                        ) : (
                            <Col>
                                <Alert variant="info">
                                    Không tìm thấy sách phù hợp với bộ lọc.
                                </Alert>
                            </Col>
                        )}
                    </Row>
                    {renderPagination()}
                </motion.section>
            </Container>
        </motion.div>
    );
};

const mapStateToProps = (state) => ({
    error: state.bookReducer.error,
    loading: state.bookReducer.loading,
    books: state.bookReducer.books,
});

const mapDispatchToProps = { fetchBooks };

export default connect(mapStateToProps, mapDispatchToProps)(HomePage);
