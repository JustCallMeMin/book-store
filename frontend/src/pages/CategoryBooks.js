import React, { useState, useEffect } from "react";
import { Container, Row, Col, Card, Form } from "react-bootstrap";
import { useParams } from "react-router-dom";
import BookCard from "../components/BookCard";

const CategoryBooks = () => {
    const { categoryId } = useParams();
    const [books, setBooks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [sortBy, setSortBy] = useState("newest");
    const [priceRange, setPriceRange] = useState("all");

    useEffect(() => {
        fetchBooks();
    }, [categoryId, sortBy, priceRange]);

    const fetchBooks = async () => {
        setLoading(true);
        try {
            // TODO: Implement API call
            const dummyBooks = [
                {
                    id: 1,
                    title: "Sách 1",
                    author: "Tác giả 1",
                    price: 150000,
                    image: "https://via.placeholder.com/150",
                    discount: 10,
                },
                {
                    id: 2,
                    title: "Sách 2",
                    author: "Tác giả 2",
                    price: 200000,
                    image: "https://via.placeholder.com/150",
                    discount: 0,
                },
                // Thêm sách mẫu khác...
            ];
            setBooks(dummyBooks);
        } catch (error) {
            console.error("Error fetching books:", error);
        }
        setLoading(false);
    };

    const handleSortChange = (e) => {
        setSortBy(e.target.value);
    };

    const handlePriceRangeChange = (e) => {
        setPriceRange(e.target.value);
    };

    return (
        <Container className="py-4">
            <Row className="mb-4">
                <Col md={6}>
                    <h2>Danh mục: {categoryId}</h2>
                </Col>
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>Sắp xếp theo</Form.Label>
                        <Form.Select value={sortBy} onChange={handleSortChange}>
                            <option value="newest">Mới nhất</option>
                            <option value="price-asc">Giá tăng dần</option>
                            <option value="price-desc">Giá giảm dần</option>
                            <option value="name-asc">Tên A-Z</option>
                            <option value="name-desc">Tên Z-A</option>
                        </Form.Select>
                    </Form.Group>
                </Col>
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>Khoảng giá</Form.Label>
                        <Form.Select
                            value={priceRange}
                            onChange={handlePriceRangeChange}
                        >
                            <option value="all">Tất cả</option>
                            <option value="0-100000">Dưới 100.000đ</option>
                            <option value="100000-200000">
                                100.000đ - 200.000đ
                            </option>
                            <option value="200000-500000">
                                200.000đ - 500.000đ
                            </option>
                            <option value="500000">Trên 500.000đ</option>
                        </Form.Select>
                    </Form.Group>
                </Col>
            </Row>

            {loading ? (
                <div className="text-center py-5">
                    <div className="spinner-border text-primary" role="status">
                        <span className="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            ) : (
                <Row xs={1} md={2} lg={4} className="g-4">
                    {books.map((book) => (
                        <Col key={book.id}>
                            <BookCard book={book} />
                        </Col>
                    ))}
                </Row>
            )}

            {!loading && books.length === 0 && (
                <Card className="text-center p-5">
                    <Card.Body>
                        <h4>Không tìm thấy sách nào trong danh mục này</h4>
                    </Card.Body>
                </Card>
            )}
        </Container>
    );
};

export default CategoryBooks;
