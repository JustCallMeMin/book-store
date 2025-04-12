import React, { Component } from "react";
import { Container, Row, Col, Form, Button, Card } from "react-bootstrap";
import "./Checkout.css";
class Checkout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            shippingAddress: "",
            paymentMethod: "creditCard",
            cardNumber: "",
            expiry: "",
            cvv: "",
        };
    }

    handleInputChange = (e) => {
        this.setState({ [e.target.name]: e.target.value });
    };

    handleSubmit = (e) => {
        e.preventDefault();
        // Xử lý đặt hàng, gọi API thanh toán, vv.
        console.log("Checkout data:", this.state);
        // Ví dụ: chuyển hướng sang trang xác nhận thanh toán
    };

    render() {
        const { shippingAddress, paymentMethod, cardNumber, expiry, cvv } =
            this.state;
        return (
            <Container className="checkout-page mt-4 ">
                <Row className="justify-content-md-center">
                    <Col md={8}>
                        <Card>
                            <Card.Header>
                                <h2>Thanh toán</h2>
                            </Card.Header>
                            <Card.Body>
                                <Form onSubmit={this.handleSubmit}>
                                    <Form.Group controlId="formShippingAddress">
                                        <Form.Label>
                                            Địa chỉ giao hàng
                                        </Form.Label>
                                        <Form.Control
                                            type="text"
                                            placeholder="Nhập địa chỉ giao hàng"
                                            name="shippingAddress"
                                            value={shippingAddress}
                                            onChange={this.handleInputChange}
                                        />
                                    </Form.Group>
                                    <Form.Group controlId="formPaymentMethod">
                                        <Form.Label>
                                            Phương thức thanh toán
                                        </Form.Label>
                                        <Form.Control
                                            as="select"
                                            name="paymentMethod"
                                            value={paymentMethod}
                                            onChange={this.handleInputChange}
                                        >
                                            <option value="creditCard">
                                                Thẻ tín dụng
                                            </option>
                                            <option value="paypal">
                                                PayPal
                                            </option>
                                            <option value="bankTransfer">
                                                Chuyển khoản
                                            </option>
                                        </Form.Control>
                                    </Form.Group>
                                    {paymentMethod === "creditCard" && (
                                        <>
                                            <Form.Group controlId="formCardNumber">
                                                <Form.Label>Số thẻ</Form.Label>
                                                <Form.Control
                                                    type="text"
                                                    placeholder="Nhập số thẻ"
                                                    name="cardNumber"
                                                    value={cardNumber}
                                                    onChange={
                                                        this.handleInputChange
                                                    }
                                                />
                                            </Form.Group>
                                            <Row>
                                                <Col md={6}>
                                                    <Form.Group controlId="formExpiry">
                                                        <Form.Label>
                                                            Ngày hết hạn
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="text"
                                                            placeholder="MM/YY"
                                                            name="expiry"
                                                            value={expiry}
                                                            onChange={
                                                                this
                                                                    .handleInputChange
                                                            }
                                                        />
                                                    </Form.Group>
                                                </Col>
                                                <Col md={6}>
                                                    <Form.Group controlId="formCvv">
                                                        <Form.Label>
                                                            CVV
                                                        </Form.Label>
                                                        <Form.Control
                                                            type="text"
                                                            placeholder="CVV"
                                                            name="cvv"
                                                            value={cvv}
                                                            onChange={
                                                                this
                                                                    .handleInputChange
                                                            }
                                                        />
                                                    </Form.Group>
                                                </Col>
                                            </Row>
                                        </>
                                    )}
                                    <Button
                                        variant="primary"
                                        type="submit"
                                        className="mt-3"
                                    >
                                        Xác nhận thanh toán
                                    </Button>
                                </Form>
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>
            </Container>
        );
    }
}

export default Checkout;
