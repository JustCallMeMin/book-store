import React, { Component } from "react";
import {
    Container,
    Row,
    Col,
    Card,
    Table,
    Button,
    Modal,
    Alert,
    ListGroup,
} from "react-bootstrap";
import { connect } from "react-redux";
import {
    getUserOrders,
    initiateMomoPayment,
} from "../store/actions/order/orderActions";
import "./MyOrders.css";

class MyOrders extends Component {
    constructor(props) {
        super(props);
        this.state = {
            showDetailsModal: false,
            selectedOrder: null,
            selectedOrderLogs: null,
        };
    }

    componentDidMount() {
        this.props.getUserOrders();
    }

    componentDidUpdate(prevProps) {
        // Handle MoMo payment initiation success
        if (
            prevProps.initiatingMomoPayment &&
            !this.props.initiatingMomoPayment &&
            this.props.momoPayUrl
        ) {
            window.location.href = this.props.momoPayUrl;
        }
    }

    handleShowDetails = (order, logs) => {
        this.setState({
            showDetailsModal: true,
            selectedOrder: order,
            selectedOrderLogs: logs,
        });
    };

    handleCloseDetails = () => {
        this.setState({
            showDetailsModal: false,
            selectedOrder: null,
            selectedOrderLogs: null,
        });
    };

    handlePayment = (orderCode) => {
        this.props.initiateMomoPayment(orderCode);
    };

    formatDate = (dateString) => {
        if (!dateString) return "Chưa xác định";
        const date = new Date(dateString);
        return date.toLocaleDateString("vi-VN", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    };

    formatStatus = (status) => {
        switch (status) {
            case "pending":
                return "Đang chờ thanh toán";
            case "paid":
                return "Đã thanh toán - chờ xác nhận";
            case "confirmed":
                return "Đã xác nhận";
            case "picking":
                return "Đang chuẩn bị hàng";
            case "picked":
                return "Đã chuẩn bị xong";
            case "storing":
                return "Đang lưu kho";
            case "transporting":
                return "Đang vận chuyển";
            case "sorting":
                return "Đang phân loại";
            case "delivering":
                return "Đang giao hàng";
            case "delivered":
                return "Đã giao hàng";
            case "completed":
                return "Đã hoàn thành";
            case "cancelled":
                return "Đã hủy";
            case "refunded":
                return "Đã hoàn tiền";
            case "delivery_fail":
                return "Giao hàng thất bại";
            case "returning":
                return "Đang trả hàng";
            case "returned":
                return "Đã trả hàng";
            default:
                return status || "Không xác định";
        }
    };

    formatPaymentMethod = (method) => {
        switch (method) {
            case "MoMo":
                return "Chuyển khoản ngân hàng (MoMo)";
            case "COD":
                return "Thanh toán khi nhận hàng (COD)";
            default:
                return method || "Không xác định";
        }
    };

    formatPaymentStatus = (status) => {
        switch (status) {
            case "pending":
                return "Chưa thanh toán";
            case "paid":
                return "Đã thanh toán";
            default:
                return status || "Không xác định";
        }
    };

    formatShippingMethod = (method) => {
        switch (method) {
            case "Car":
                return "Xe hơi";
            default:
                return method || "Không xác định";
        }
    };

    render() {
        const { showDetailsModal, selectedOrder, selectedOrderLogs } =
            this.state;
        const {
            userOrders,
            loadingUserOrders,
            userOrdersError,
            initiatingMomoPayment,
        } = this.props;

        return (
            <Container className="my-orders-page my-5">
                <h2 className="mb-4 fw-bold text-primary">Đơn hàng của tôi</h2>
                <Card className="shadow-sm border-0">
                    <Card.Body className="p-4">
                        {loadingUserOrders ? (
                            <p className="text-muted">Đang tải đơn hàng...</p>
                        ) : userOrdersError ? (
                            <Alert variant="danger">
                                Lỗi: {userOrdersError}
                            </Alert>
                        ) : userOrders.length === 0 ? (
                            <p className="text-muted">
                                Bạn chưa có đơn hàng nào.
                            </p>
                        ) : (
                            <Table responsive hover className="orders-table">
                                <thead>
                                    <tr>
                                        <th>Mã đơn hàng</th>
                                        <th>Ngày đặt</th>
                                        <th>Trạng thái</th>
                                        <th>Tổng tiền</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {userOrders.map((item) => (
                                        <tr key={item.order.order_code}>
                                            <td>{item.order.order_code}</td>
                                            <td>
                                                {this.formatDate(
                                                    item.order.order_date
                                                )}
                                            </td>
                                            <td>
                                                {this.formatStatus(
                                                    item.order.status
                                                )}
                                            </td>
                                            <td>
                                                {parseFloat(
                                                    item.order.final_amount
                                                ).toLocaleString("vi-VN")}{" "}
                                                đ
                                            </td>
                                            <td>
                                                <Button
                                                    variant="outline-primary"
                                                    size="sm"
                                                    onClick={() =>
                                                        this.handleShowDetails(
                                                            item.order,
                                                            item.logs
                                                        )
                                                    }
                                                    className="me-2"
                                                >
                                                    Xem chi tiết
                                                </Button>
                                                {item.order.payment_status ===
                                                    "pending" && (
                                                    <Button
                                                        variant="primary"
                                                        size="sm"
                                                        onClick={() =>
                                                            this.handlePayment(
                                                                item.order
                                                                    .order_code
                                                            )
                                                        }
                                                        disabled={
                                                            initiatingMomoPayment
                                                        }
                                                    >
                                                        {initiatingMomoPayment
                                                            ? "Đang xử lý..."
                                                            : "Thanh toán"}
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </Table>
                        )}
                    </Card.Body>
                </Card>

                <Modal
                    show={showDetailsModal}
                    onHide={this.handleCloseDetails}
                    centered
                    size="lg"
                >
                    <Modal.Header closeButton>
                        <Modal.Title>Chi tiết đơn hàng</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        {selectedOrder && (
                            <Row>
                                <Col md={6}>
                                    <h5 className="fw-semibold mb-3">
                                        Thông tin đơn hàng
                                    </h5>
                                    <ListGroup variant="flush">
                                        <ListGroup.Item>
                                            <strong>Mã đơn hàng:</strong>{" "}
                                            {selectedOrder.order_code}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Ngày đặt hàng:</strong>{" "}
                                            {this.formatDate(
                                                selectedOrder.order_date
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>
                                                Ngày dự kiến giao hàng:
                                            </strong>{" "}
                                            {this.formatDate(
                                                this.props.userOrders.find(
                                                    (item) =>
                                                        item.order
                                                            .order_code ===
                                                        selectedOrder.order_code
                                                )?.lead_time
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Trạng thái:</strong>{" "}
                                            {this.formatStatus(
                                                selectedOrder.status
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>
                                                Phương thức thanh toán:
                                            </strong>{" "}
                                            {this.formatPaymentMethod(
                                                selectedOrder.payment_method
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>
                                                Trạng thái thanh toán:
                                            </strong>{" "}
                                            {this.formatPaymentStatus(
                                                selectedOrder.payment_status
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>
                                                Phương thức vận chuyển:
                                            </strong>{" "}
                                            {this.formatShippingMethod(
                                                selectedOrder.shipping_method
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Ngày thanh toán:</strong>{" "}
                                            {this.formatDate(
                                                selectedOrder.payment_date
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Ngày giao hàng:</strong>{" "}
                                            {this.formatDate(
                                                selectedOrder.shipping_date
                                            )}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Ngày nhận hàng:</strong>{" "}
                                            {this.formatDate(
                                                selectedOrder.delivery_date
                                            )}
                                        </ListGroup.Item>
                                    </ListGroup>

                                    <h5 className="fw-semibold mt-4 mb-3">
                                        Thông tin người nhận
                                    </h5>
                                    <ListGroup variant="flush">
                                        <ListGroup.Item>
                                            <strong>Tên:</strong>{" "}
                                            {selectedOrder.recipient_name}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Số điện thoại:</strong>{" "}
                                            {selectedOrder.recipient_phone}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Địa chỉ:</strong>{" "}
                                            {selectedOrder.recipient_address}
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Email:</strong>{" "}
                                            {selectedOrder.recipient_email ||
                                                "Không có"}
                                        </ListGroup.Item>
                                    </ListGroup>

                                    <h5 className="fw-semibold mt-4 mb-3">
                                        Ghi chú
                                    </h5>
                                    <p>
                                        {selectedOrder.notes ||
                                            "Không có ghi chú"}
                                    </p>
                                </Col>
                                <Col md={6}>
                                    <h5 className="fw-semibold mb-3">
                                        Lịch sử đơn hàng
                                    </h5>
                                    {selectedOrderLogs &&
                                    selectedOrderLogs.length > 0 ? (
                                        <div className="order-timeline">
                                            {selectedOrderLogs.map(
                                                (log, index) => (
                                                    <div
                                                        key={index}
                                                        className="timeline-item"
                                                    >
                                                        <div className="timeline-dot"></div>
                                                        <div className="timeline-content">
                                                            <p className="timeline-status">
                                                                {this.formatStatus(
                                                                    log.status
                                                                )}
                                                            </p>
                                                            <p className="timeline-date">
                                                                {this.formatDate(
                                                                    log.updated_date
                                                                )}
                                                            </p>
                                                            {log.action && (
                                                                <p className="timeline-action">
                                                                    Hành động:{" "}
                                                                    {log.action}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                )
                                            )}
                                        </div>
                                    ) : (
                                        <p className="text-muted">
                                            Chưa có lịch sử đơn hàng.
                                        </p>
                                    )}

                                    <h5 className="fw-semibold mt-4 mb-3">
                                        Tóm tắt thanh toán
                                    </h5>
                                    <ListGroup variant="flush">
                                        <ListGroup.Item>
                                            <strong>Tạm tính:</strong>{" "}
                                            {parseFloat(
                                                selectedOrder.total_amount
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Phí vận chuyển:</strong>{" "}
                                            {parseFloat(
                                                selectedOrder.shipping_fee
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Thuế:</strong>{" "}
                                            {parseFloat(
                                                selectedOrder.tax_amount
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Giảm giá:</strong>{" "}
                                            {parseFloat(
                                                selectedOrder.discount_amount
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </ListGroup.Item>
                                        <ListGroup.Item>
                                            <strong>Tổng cộng:</strong>{" "}
                                            {parseFloat(
                                                selectedOrder.final_amount
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </ListGroup.Item>
                                    </ListGroup>
                                </Col>
                            </Row>
                        )}
                    </Modal.Body>
                    <Modal.Footer>
                        <Button
                            variant="secondary"
                            onClick={this.handleCloseDetails}
                        >
                            Đóng
                        </Button>
                    </Modal.Footer>
                </Modal>
            </Container>
        );
    }
}

const mapStateToProps = (state) => ({
    userOrders: state.orderReducer.userOrders,
    loadingUserOrders: state.orderReducer.loadingUserOrders,
    userOrdersError: state.orderReducer.userOrdersError,
    initiatingMomoPayment: state.orderReducer.initiatingMomoPayment,
    momoPayUrl: state.orderReducer.momoPayUrl,
    momoPaymentError: state.orderReducer.momoPaymentError,
});

const mapDispatchToProps = (dispatch) => ({
    getUserOrders: () => dispatch(getUserOrders()),
    initiateMomoPayment: (orderCode) =>
        dispatch(initiateMomoPayment(orderCode)),
});

export default connect(mapStateToProps, mapDispatchToProps)(MyOrders);
