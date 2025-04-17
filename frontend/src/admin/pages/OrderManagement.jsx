import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";
import {
    message,
    Spin,
    Button,
    Modal,
    Timeline,
    Descriptions,
    Image,
} from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import {
    getOrders,
    confirmOrder,
    cancelOrder,
} from "src/store/actions/order/orderActions";
import { parsePermissionsForPage } from "../utils/permissionHelper";
import { getPermissionsFromApi } from "src/store/actions/user/userActions";

class OrderManagement extends Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [],
            access: false,
            create: false,
            update: false,
            delete: false,
            permissionsLoaded: false,
            selectedOrder: null,
            isModalVisible: false,
        };
    }

    async componentDidMount() {
        const user = sessionStorage.getItem("user")
            ? JSON.parse(sessionStorage.getItem("user"))
            : null;

        if (!user || !user.roles) {
            toast.error("Không tìm thấy thông tin người dùng");
            return;
        }

        try {
            const permissions = sessionStorage.getItem("permissions")
                ? JSON.parse(sessionStorage.getItem("permissions"))
                : await this.props.getPermissionsFromApi(user.roles[0]);
            if (!permissions) {
                toast.error("Không tìm thấy quyền truy cập");
                return;
            }

            const parsed = parsePermissionsForPage(permissions, "books");

            this.setState({ ...parsed, permissionsLoaded: true }, () => {
                if (this.state.access) {
                    this.props.getOrders();
                }
            });
        } catch (err) {
            console.error("Lỗi khi lấy quyền truy cập", err);
            this.setState({ access: false, permissionsLoaded: true });
        }
    }

    componentDidUpdate(prevProps) {
        const {
            ordersError,
            confirmOrderError,
            orderConfirmed,
            cancelOrderError,
            orderCancelled,
        } = this.props;

        if (prevProps.ordersError !== ordersError && ordersError) {
            message.error(
                `Có lỗi xảy ra khi tải dữ liệu đơn hàng: ${ordersError}`
            );
        }

        if (
            prevProps.confirmOrderError !== confirmOrderError &&
            confirmOrderError
        ) {
            message.error(`Có lỗi khi xác nhận đơn hàng: ${confirmOrderError}`);
        }

        if (prevProps.orderConfirmed !== orderConfirmed && orderConfirmed) {
            message.success("Xác nhận đơn hàng thành công");
            this.props.getOrders(); // Refresh orders after confirmation
        }

        if (
            prevProps.cancelOrderError !== cancelOrderError &&
            cancelOrderError
        ) {
            message.error(`Có lỗi khi hủy đơn hàng: ${cancelOrderError}`);
        }

        if (prevProps.orderCancelled !== orderCancelled && orderCancelled) {
            message.success("Hủy đơn hàng thành công");
            this.props.getOrders(); // Refresh orders after cancellation
        }

        if (
            prevProps.orders !== this.props.orders &&
            Array.isArray(this.props.orders)
        ) {
            const columns = this.generateColumns();
            this.setState({ columns });
        }
    }

    formatStatus = (status) => {
        switch (status) {
            case "pending":
                return "Đợi thanh toán";
            case "paid":
                return "Đã thanh toán - chờ xác nhận";
            case "confirmed":
                return "Đã xác nhận";
            case "picking":
                return "Đang lấy hàng";
            case "picked":
                return "Đã lấy hàng";
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
            case "lost":
                return "Mất hàng";
            default:
                return status || "Không xác định";
        }
    };

    handleConfirmOrder = (orderCode) => {
        this.props.confirmOrder(orderCode);
    };

    handleCancelOrder = (orderCode) => {
        this.props.cancelOrder(orderCode);
    };

    handleViewDetails = (order) => {
        this.setState({
            selectedOrder: order,
            isModalVisible: true,
        });
    };

    handleModalClose = () => {
        this.setState({
            selectedOrder: null,
            isModalVisible: false,
        });
    };

    generateColumns = () => {
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Mã đơn hàng",
                dataIndex: "order_code",
                key: "order_code",
                width: 150,
            },
            {
                title: "Mã vận chuyển",
                dataIndex: "ship_code",
                key: "ship_code",
                width: 150,
            },
            {
                title: "Người nhận",
                dataIndex: "recipient_name",
                key: "recipient_name",
                width: 150,
            },
            {
                title: "Tổng tiền",
                dataIndex: "final_amount",
                key: "final_amount",
                width: 120,
                render: (text) =>
                    parseFloat(text).toLocaleString("vi-VN") + " đ",
            },
            {
                title: "Trạng thái",
                dataIndex: "status",
                key: "status",
                width: 180,
                render: (text) => this.formatStatus(text),
            },
            {
                title: "Ngày đặt hàng",
                dataIndex: "order_date",
                key: "order_date",
                width: 200,
                render: (text) =>
                    new Date(text).toLocaleString("vi-VN", {
                        hour: "2-digit",
                        minute: "2-digit",
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    }),
            },
            {
                title: "Phương thức thanh toán",
                dataIndex: "payment_method",
                key: "payment_method",
                width: 200,
                render: (text) =>
                    text === "MoMo"
                        ? "Chuyển khoản ngân hàng (MoMo)"
                        : text || "Không xác định",
            },
            {
                title: "Thao tác",
                key: "action",
                width: 250,
                render: (text, record, index) => (
                    <div style={{ display: "flex", gap: "8px" }}>
                        <Button
                            size="small"
                            onClick={() =>
                                this.handleViewDetails(this.props.orders[index])
                            }
                        >
                            Xem chi tiết
                        </Button>
                        {record.status === "paid" && (
                            <Button
                                type="primary"
                                size="small"
                                onClick={() =>
                                    this.handleConfirmOrder(record.order_code)
                                }
                                loading={this.props.confirmingOrder}
                                disabled={this.props.confirmingOrder}
                            >
                                Xác nhận
                            </Button>
                        )}
                        {record.status === "pending" && (
                            <Button
                                danger
                                size="small"
                                onClick={() =>
                                    this.handleCancelOrder(record.order_code)
                                }
                                loading={this.props.deletingOrder}
                                disabled={this.props.deletingOrder}
                            >
                                Hủy đơn
                            </Button>
                        )}
                    </div>
                ),
            },
        ];
    };

    renderOrderDetails = () => {
        const { selectedOrder } = this.state;
        if (!selectedOrder) return null;

        const order = selectedOrder.order;
        const logs = selectedOrder.logs || [];

        return (
            <div>
                <Descriptions title="Chi tiết đơn hàng" bordered column={1}>
                    <Descriptions.Item label="Mã đơn hàng">
                        {order.order_code}
                    </Descriptions.Item>
                    <Descriptions.Item label="Mã vận chuyển">
                        {order.ship_code}
                    </Descriptions.Item>
                    <Descriptions.Item label="Người nhận">
                        {order.recipient_name}
                    </Descriptions.Item>
                    <Descriptions.Item label="Số điện thoại">
                        {order.recipient_phone || "Không có"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Địa chỉ">
                        {order.recipient_address || "Không có"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Tổng tiền">
                        {parseFloat(order.final_amount).toLocaleString("vi-VN")}{" "}
                        đ
                    </Descriptions.Item>
                    <Descriptions.Item label="Trạng thái">
                        {this.formatStatus(order.status)}
                    </Descriptions.Item>
                    <Descriptions.Item label="Phương thức thanh toán">
                        {order.payment_method === "MoMo"
                            ? "Chuyển khoản ngân hàng (MoMo)"
                            : order.payment_method || "Không xác định"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Ngày đặt hàng">
                        {new Date(order.order_date).toLocaleString("vi-VN", {
                            hour: "2-digit",
                            minute: "2-digit",
                            day: "2-digit",
                            month: "2-digit",
                            year: "numeric",
                        })}
                    </Descriptions.Item>
                    {order.items &&
                        Array.isArray(order.items) &&
                        order.items.length > 0 && (
                            <Descriptions.Item label="Sản phẩm">
                                <div>
                                    {order.items.map((item, index) => (
                                        <div
                                            key={index}
                                            style={{
                                                display: "flex",
                                                alignItems: "center",
                                                marginBottom: "16px",
                                                padding: "8px",
                                                border: "1px solid #f0f0f0",
                                                borderRadius: "4px",
                                            }}
                                        >
                                            <Image
                                                src={item.book.cover_image}
                                                alt={item.book.title}
                                                style={{
                                                    width: "60px",
                                                    height: "80px",
                                                    marginRight: "16px",
                                                    objectFit: "cover",
                                                }}
                                                preview={false}
                                            />
                                            <div>
                                                <p
                                                    style={{
                                                        margin: 0,
                                                        fontWeight: 500,
                                                    }}
                                                >
                                                    {item.book.title}
                                                </p>
                                                <p style={{ margin: "4px 0" }}>
                                                    Số lượng: {item.quantity}
                                                </p>
                                                <p style={{ margin: "4px 0" }}>
                                                    Đơn giá:{" "}
                                                    {parseFloat(
                                                        item.unit_price
                                                    ).toLocaleString(
                                                        "vi-VN"
                                                    )}{" "}
                                                    đ
                                                </p>
                                                <p style={{ margin: "4px 0" }}>
                                                    Tổng:{" "}
                                                    {(
                                                        parseFloat(
                                                            item.final_price
                                                        ) * item.quantity
                                                    ).toLocaleString(
                                                        "vi-VN"
                                                    )}{" "}
                                                    đ
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </Descriptions.Item>
                        )}
                </Descriptions>
                {logs.length > 0 && (
                    <div style={{ marginTop: "20px" }}>
                        <h4>Lịch sử trạng thái</h4>
                        <Timeline>
                            {logs.map((log, index) => (
                                <Timeline.Item key={index}>
                                    {this.formatStatus(log.status)} -{" "}
                                    {new Date(log.created_at).toLocaleString(
                                        "vi-VN",
                                        {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                            day: "2-digit",
                                            month: "2-digit",
                                            year: "numeric",
                                        }
                                    )}
                                </Timeline.Item>
                            ))}
                        </Timeline>
                    </div>
                )}
            </div>
        );
    };

    render() {
        const { orders, loadingOrders } = this.props;
        const { columns, access, permissionsLoaded, isModalVisible } =
            this.state;

        if (!permissionsLoaded) {
            return (
                <div
                    style={{
                        display: "flex",
                        justifyContent: "center",
                        alignItems: "center",
                        height: "40vh",
                    }}
                >
                    <Spin tip="Đang kiểm tra quyền truy cập..." size="large" />
                </div>
            );
        }
        if (!access) {
            return <Navigate to="/accessDenied" replace />;
        }

        const dataWithKey = Array.isArray(orders)
            ? orders.map((item) => ({
                  key: item.order.id,
                  order_code: item.order.order_code,
                  ship_code: item.order.ship_code ?? "Chưa có",
                  recipient_name: item.order.recipient_name,
                  final_amount: item.order.final_amount,
                  status: item.order.status,
                  order_date: item.order.order_date,
                  payment_method: item.order.payment_method,
              }))
            : [];

        return (
            <>
                <DataManagementPage
                    title="Quản lý đơn hàng"
                    subtitle="Xem và quản lý các đơn hàng trong hệ thống."
                    columns={columns}
                    data={dataWithKey}
                    rowKey="key"
                    loading={loadingOrders}
                />
                <Modal
                    title="Chi tiết đơn hàng"
                    open={isModalVisible}
                    onCancel={this.handleModalClose}
                    footer={[
                        <Button key="close" onClick={this.handleModalClose}>
                            Đóng
                        </Button>,
                    ]}
                    width={800}
                >
                    {this.renderOrderDetails()}
                </Modal>
            </>
        );
    }
}

const mapStateToProps = (state) => ({
    orders: state.orderReducer.orders,
    loadingOrders: state.orderReducer.loadingOrders,
    ordersError: state.orderReducer.ordersError,
    confirmingOrder: state.orderReducer.confirmingOrder,
    orderConfirmed: state.orderReducer.orderConfirmed,
    confirmOrderError: state.orderReducer.confirmOrderError,
    deletingOrder: state.orderReducer.deletingOrder,
    orderCancelled: state.orderReducer.orderCancelled,
    cancelOrderError: state.orderReducer.cancelOrderError,
});

const mapDispatchToProps = {
    getOrders,
    confirmOrder,
    cancelOrder,
    getPermissionsFromApi,
};

export default connect(mapStateToProps, mapDispatchToProps)(OrderManagement);
