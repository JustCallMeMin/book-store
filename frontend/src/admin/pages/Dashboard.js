import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { Row, Col, Button, Modal, Descriptions, Timeline } from "antd";
import StatCard from "../components/StatCard";
import DataTable from "../components/DataTable";
import Chart from "../components/Chart";
import { motion } from "framer-motion";
import { getOrders } from "src/store/actions/order/orderActions";

const Dashboard = () => {
    const dispatch = useDispatch();
    const { orders, loadingOrders, ordersError } = useSelector((state) => ({
        orders: state.orderReducer.orders,
        loadingOrders: state.orderReducer.loadingOrders,
        ordersError: state.orderReducer.ordersError,
    }));

    const [selectedOrder, setSelectedOrder] = useState(null);
    const [isModalVisible, setIsModalVisible] = useState(false);

    useEffect(() => {
        dispatch(getOrders());
    }, [dispatch]);

    const formatStatus = (status) => {
        switch (status) {
            case "pending":
                return "Đợi thanh toán";
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
            case "lost":
                return "Mất hàng";
            default:
                return status || "Không xác định";
        }
    };

    const columns = [
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
            render: (text) => parseFloat(text).toLocaleString("vi-VN") + " đ",
        },
        {
            title: "Trạng thái",
            dataIndex: "status",
            key: "status",
            width: 180,
            render: (text) => formatStatus(text),
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
            title: "Thao tác",
            key: "action",
            width: 120,
            render: (text, record, index) => (
                <Button
                    size="small"
                    onClick={() => handleViewDetails(orders[index])}
                >
                    Xem chi tiết
                </Button>
            ),
        },
    ];

    const handleViewDetails = (order) => {
        setSelectedOrder(order);
        setIsModalVisible(true);
    };

    const handleModalClose = () => {
        setSelectedOrder(null);
        setIsModalVisible(false);
    };

    const renderOrderDetails = () => {
        if (!selectedOrder) return null;

        const order = selectedOrder.order;
        const logs = selectedOrder.logs || [];

        return (
            <div>
                <Descriptions title="Chi tiết đơn hàng" bordered column={1}>
                    <Descriptions.Item label="Mã đơn hàng">
                        {order.order_code}
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
                        {formatStatus(order.status)}
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
                                <ul>
                                    {order.items.map((item, index) => (
                                        <li key={index}>
                                            {item.name} (x{item.quantity}) -{" "}
                                            {parseFloat(
                                                item.price
                                            ).toLocaleString("vi-VN")}{" "}
                                            đ
                                        </li>
                                    ))}
                                </ul>
                            </Descriptions.Item>
                        )}
                </Descriptions>
                {logs.length > 0 && (
                    <div style={{ marginTop: "20px" }}>
                        <h4>Lịch sử trạng thái</h4>
                        <Timeline>
                            {logs.map((log, index) => (
                                <Timeline.Item key={index}>
                                    {formatStatus(log.status)} -{" "}
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

    const today = new Date().toDateString();
    const todayOrders = Array.isArray(orders)
        ? orders
              .filter(
                  (item) =>
                      new Date(item.order.order_date).toDateString() === today
              )
              .map((item, index) => ({
                  key: item.order.id,
                  order_code: item.order.order_code,
                  recipient_name: item.order.recipient_name,
                  final_amount: item.order.final_amount,
                  status: item.order.status,
                  order_date: item.order.order_date,
              }))
        : [];

    const totalRevenue = Array.isArray(orders)
        ? orders
              .reduce(
                  (sum, item) => sum + parseFloat(item.order.final_amount),
                  0
              )
              .toLocaleString("vi-VN") + " đ"
        : "0 đ";

    const newOrders = Array.isArray(orders)
        ? orders.filter(
              (item) => new Date(item.order.order_date).toDateString() === today
          ).length
        : 0;

    const pendingConfirmation = Array.isArray(orders)
        ? orders.filter((item) => item.order.status === "paid").length
        : 0;

    const totalOrders = Array.isArray(orders) ? orders.length : 0;

    return (
        <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ duration: 0.5 }}
        >
            <Row gutter={[16, 16]}>
                {[
                    {
                        title: "Tổng doanh thu",
                        value: totalRevenue,
                        gradient:
                            "linear-gradient(135deg, #2C3E50 0%, #2C3E50 100%)",
                    },
                    {
                        title: "Đơn hàng mới",
                        value: newOrders.toString(),
                    },
                    {
                        title: "Đơn hàng chờ xác nhận",
                        value: pendingConfirmation.toString(),
                    },
                    {
                        title: "Tổng đơn hàng",
                        value: totalOrders.toString(),
                    },
                ].map((card, index) => (
                    <Col xs={24} sm={12} md={6} key={index}>
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ delay: index * 0.2 }}
                        >
                            <StatCard
                                title={card.title}
                                value={card.value}
                                gradient={card.gradient}
                            />
                        </motion.div>
                    </Col>
                ))}
            </Row>

            <Row gutter={[16, 16]} style={{ marginTop: 24 }}>
                {[1, 2].map((_, index) => (
                    <Col xs={24} md={12} key={index}>
                        <motion.div
                            initial={{ opacity: 0, scale: 0.95 }}
                            animate={{ opacity: 1, scale: 1 }}
                            transition={{ delay: 0.4 + index * 0.2 }}
                        >
                            <Chart />
                        </motion.div>
                    </Col>
                ))}
            </Row>

            <Row style={{ marginTop: 24 }}>
                <Col xs={24}>
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ delay: 0.6 }}
                    >
                        <DataTable
                            columns={columns}
                            data={todayOrders}
                            loading={loadingOrders}
                            title={() => "Đơn hàng hôm nay"}
                        />
                    </motion.div>
                </Col>
            </Row>

            <Modal
                title="Chi tiết đơn hàng"
                open={isModalVisible}
                onCancel={handleModalClose}
                footer={[
                    <Button key="close" onClick={handleModalClose}>
                        Đóng
                    </Button>,
                ]}
                width={800}
            >
                {renderOrderDetails()}
            </Modal>
        </motion.div>
    );
};

export default Dashboard;
