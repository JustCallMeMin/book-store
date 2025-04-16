import React, { Component } from "react";
import {
    Container,
    Row,
    Col,
    Form,
    Button,
    Card,
    Image,
    Modal,
} from "react-bootstrap";
import { FaTrash } from "react-icons/fa";
import { connect } from "react-redux";
import { updateCartItemQuantity } from "./../store/actions/cart/cartAction";
import {
    getProvinces,
    getDistricts,
    getWards,
    sendOtp,
    verifyOtp,
    getShippingFee,
    createOrder,
    initiateMomoPayment,
} from "../store/actions/order/orderActions";
import "./Checkout.css";

class Checkout extends Component {
    constructor(props) {
        super(props);
        this.state = {
            recipientName: "",
            phoneNumber: "",
            shippingAddress: "",
            province: "",
            district: "",
            ward: "",
            paymentMethod: "bankTransfer",
            showConfirmModal: false,
            showOtpModal: false,
            otp: "",
            otpError: "",
            notes: "",
        };
    }

    componentDidMount() {
        this.props.getProvinces();
    }

    componentDidUpdate(prevProps, prevState) {
        // Handle OTP sent success
        if (
            prevProps.sendingOtp &&
            !this.props.sendingOtp &&
            this.props.otpSent
        ) {
            this.setState({ showConfirmModal: false, showOtpModal: true });
        }
        // Handle OTP sent failure
        if (
            prevProps.sendingOtp &&
            !this.props.sendingOtp &&
            this.props.otpError
        ) {
            this.setState({ otpError: this.props.otpError });
        }
        // Handle OTP verification success
        if (
            prevProps.verifyingOtp &&
            !this.props.verifyingOtp &&
            this.props.otpVerified
        ) {
            const {
                recipientName,
                phoneNumber,
                shippingAddress,
                province,
                district,
                ward,
                notes,
            } = this.state;
            const { provinces, districts, wards, shippingFee } = this.props;

            const selectedProvince = Array.isArray(provinces)
                ? provinces.find(
                      (p) => String(p.province_id) === String(province)
                  )?.name || ""
                : "";
            const selectedDistrict = Array.isArray(districts)
                ? districts.find(
                      (d) => String(d.district_id) === String(district)
                  )?.name || ""
                : "";
            const selectedWard = Array.isArray(wards)
                ? wards.find((w) => String(w.ward_id) === String(ward))
                      ?.ward_name || ""
                : "";

            const orderDetails = {
                recipient_name: recipientName,
                recipient_phone: phoneNumber,
                recipient_address: shippingAddress,
                province_name: selectedProvince,
                district_name: selectedDistrict,
                ward_name: selectedWard,
                shipping_fee: shippingFee,
                notes: notes,
            };

            this.props.createOrder(orderDetails);
            this.setState({ showOtpModal: false, otp: "", otpError: "" });
        }
        // Handle OTP verification failure
        if (
            prevProps.verifyingOtp &&
            !this.props.verifyingOtp &&
            this.props.otpError
        ) {
            this.setState({ otpError: this.props.otpError });
        }
        // Fetch shipping fee when ward changes
        if (
            this.state.ward &&
            prevState.ward !== this.state.ward &&
            this.state.district
        ) {
            this.props.getShippingFee(this.state.district, this.state.ward);
        }
        // Handle order creation success
        if (
            prevProps.creatingOrder &&
            !this.props.creatingOrder &&
            this.props.orderCreated &&
            this.state.paymentMethod === "bankTransfer"
        ) {
            this.props.initiateMomoPayment(this.props.orderData.order_code);
        }
        // Handle MoMo payment initiation success
        if (
            prevProps.initiatingMomoPayment &&
            !this.props.initiatingMomoPayment &&
            this.props.momoPayUrl
        ) {
            window.location.href = this.props.momoPayUrl;
        }
    }

    handleInputChange = (e) => {
        this.setState({ [e.target.name]: e.target.value });
    };

    handleProvinceChange = (e) => {
        const provinceId = e.target.value;
        this.setState({
            province: provinceId,
            district: "",
            ward: "",
        });
        if (provinceId) {
            this.props.getDistricts(provinceId);
        }
    };

    handleDistrictChange = (e) => {
        const districtId = e.target.value;
        this.setState({
            district: districtId,
            ward: "",
        });
        if (districtId) {
            this.props.getWards(districtId);
        }
    };

    handleWardChange = (e) => {
        const wardId = e.target.value;
        this.setState({ ward: wardId });
    };

    handleQuantityChange = (bookId, newQuantity) => {
        this.props.updateCartItemQuantity(bookId, parseInt(newQuantity, 10));
    };

    handleRemoveItem = (bookId) => {
        this.props.updateCartItemQuantity(bookId, 0);
    };

    handleSubmit = (e) => {
        e.preventDefault();
        this.setState({ showConfirmModal: true });
    };

    handleConfirmOrder = () => {
        const {
            recipientName,
            phoneNumber,
            shippingAddress,
            province,
            district,
            ward,
        } = this.state;
        const { provinces, districts, wards } = this.props;

        const selectedProvince = Array.isArray(provinces)
            ? provinces.find((p) => String(p.province_id) === String(province))
                  ?.name || ""
            : "";
        const selectedDistrict = Array.isArray(districts)
            ? districts.find((d) => String(d.district_id) === String(district))
                  ?.name || ""
            : "";
        const selectedWard = Array.isArray(wards)
            ? wards.find((w) => String(w.ward_id) === String(ward))
                  ?.ward_name || ""
            : "";

        const orderDetails = {
            name: recipientName,
            phone: phoneNumber,
            address: shippingAddress,
            ward: selectedWard,
            district: selectedDistrict,
            province: selectedProvince,
        };

        this.props.sendOtp(orderDetails);
    };

    handleCloseConfirmModal = () => {
        this.setState({ showConfirmModal: false });
    };

    handleOtpChange = (e) => {
        this.setState({ otp: e.target.value, otpError: "" });
    };

    handleVerifyOtp = () => {
        const { otp } = this.state;
        if (!otp) {
            this.setState({ otpError: "Vui lòng nhập OTP" });
            return;
        }
        this.props.verifyOtp({ otp });
    };

    handleCloseOtpModal = () => {
        this.setState({ showOtpModal: false, otp: "", otpError: "" });
    };

    render() {
        const {
            recipientName,
            phoneNumber,
            shippingAddress,
            province,
            district,
            ward,
            paymentMethod,
            showConfirmModal,
            showOtpModal,
            otp,
            otpError,
            notes,
        } = this.state;
        const {
            cartItems,
            provinces,
            districts,
            wards,
            loadingProvinces,
            loadingDistricts,
            loadingWards,
            sendingOtp,
            verifyingOtp,
            shippingFee,
            loadingShippingFee,
            creatingOrder,
            initiatingMomoPayment,
        } = this.props;

        // Default cart data
        const data =
            cartItems && cartItems.data
                ? cartItems.data
                : {
                      items: [],
                      total_amount: 0,
                      final_amount: 0,
                      total_items: 0,
                  };
        const carts = data.items;
        const total_amount = data.total_amount;
        const final_amount = data.final_amount + (shippingFee || 0);

        // For confirmation modal
        const selectedProvince = Array.isArray(provinces)
            ? provinces.find((p) => String(p.province_id) === String(province))
                  ?.name || ""
            : "";
        const selectedDistrict = Array.isArray(districts)
            ? districts.find((d) => String(d.district_id) === String(district))
                  ?.name || ""
            : "";
        const selectedWard = Array.isArray(wards)
            ? wards.find((w) => String(w.ward_id) === String(ward))
                  ?.ward_name || ""
            : "";
        const paymentMethodText =
            paymentMethod === "bankTransfer"
                ? "Chuyển khoản ngân hàng"
                : "Thanh toán khi nhận hàng (COD)";

        return (
            <Container className="checkout-page my-5">
                <Row className="gx-4">
                    <Col lg={8}>
                        <Card className="shadow-sm border-0">
                            <Card.Body className="p-4">
                                <h3 className="mb-4 fw-bold text-primary">
                                    Thông tin thanh toán
                                </h3>
                                <Form onSubmit={this.handleSubmit}>
                                    <h5 className="mb-3 fw-semibold">
                                        Thông tin người nhận
                                    </h5>
                                    <Row>
                                        <Col md={6}>
                                            <Form.Group
                                                controlId="formRecipientName"
                                                className="mb-3"
                                            >
                                                <Form.Label className="fw-medium">
                                                    Tên người nhận
                                                </Form.Label>
                                                <Form.Control
                                                    type="text"
                                                    placeholder="Nhập tên người nhận"
                                                    name="recipientName"
                                                    value={recipientName}
                                                    onChange={
                                                        this.handleInputChange
                                                    }
                                                    required
                                                    className="rounded-3"
                                                />
                                            </Form.Group>
                                        </Col>
                                        <Col md={6}>
                                            <Form.Group
                                                controlId="formPhoneNumber"
                                                className="mb-3"
                                            >
                                                <Form.Label className="fw-medium">
                                                    Số điện thoại
                                                </Form.Label>
                                                <Form.Control
                                                    type="tel"
                                                    placeholder="Nhập số điện thoại"
                                                    name="phoneNumber"
                                                    value={phoneNumber}
                                                    onChange={
                                                        this.handleInputChange
                                                    }
                                                    required
                                                    className="rounded-3"
                                                />
                                            </Form.Group>
                                        </Col>
                                    </Row>

                                    <h5 className="mb-3 fw-semibold">
                                        Địa chỉ giao hàng
                                    </h5>
                                    <Form.Group
                                        controlId="formShippingAddress"
                                        className="mb-3"
                                    >
                                        <Form.Label className="fw-medium">
                                            Địa chỉ cụ thể
                                        </Form.Label>
                                        <Form.Control
                                            type="text"
                                            placeholder="Ví dụ: Số 123, Đường ABC"
                                            name="shippingAddress"
                                            value={shippingAddress}
                                            onChange={this.handleInputChange}
                                            required
                                            className="rounded-3"
                                        />
                                    </Form.Group>
                                    <Row className="mb-4">
                                        <Col md={4}>
                                            <Form.Group controlId="formProvince">
                                                <Form.Label className="fw-medium">
                                                    Tỉnh/Thành
                                                </Form.Label>
                                                <Form.Control
                                                    as="select"
                                                    name="province"
                                                    value={province}
                                                    onChange={
                                                        this
                                                            .handleProvinceChange
                                                    }
                                                    required
                                                    className="rounded-3"
                                                >
                                                    <option value="">
                                                        Chọn Tỉnh/Thành
                                                    </option>
                                                    {loadingProvinces ? (
                                                        <option>
                                                            Đang tải...
                                                        </option>
                                                    ) : (
                                                        provinces.map(
                                                            (prov) => (
                                                                <option
                                                                    key={
                                                                        prov.province_id
                                                                    }
                                                                    value={
                                                                        prov.province_id
                                                                    }
                                                                >
                                                                    {prov.name}
                                                                </option>
                                                            )
                                                        )
                                                    )}
                                                </Form.Control>
                                            </Form.Group>
                                        </Col>
                                        <Col md={4}>
                                            <Form.Group controlId="formDistrict">
                                                <Form.Label className="fw-medium">
                                                    Quận/Huyện
                                                </Form.Label>
                                                <Form.Control
                                                    as="select"
                                                    name="district"
                                                    value={district}
                                                    onChange={
                                                        this
                                                            .handleDistrictChange
                                                    }
                                                    required
                                                    disabled={!province}
                                                    className="rounded-3"
                                                >
                                                    <option value="">
                                                        Chọn Quận/Huyện
                                                    </option>
                                                    {loadingDistricts ? (
                                                        <option>
                                                            Đang tải...
                                                        </option>
                                                    ) : (
                                                        districts.map(
                                                            (dist) => (
                                                                <option
                                                                    key={
                                                                        dist.district_id
                                                                    }
                                                                    value={
                                                                        dist.district_id
                                                                    }
                                                                >
                                                                    {dist.name}
                                                                </option>
                                                            )
                                                        )
                                                    )}
                                                </Form.Control>
                                            </Form.Group>
                                        </Col>
                                        <Col md={4}>
                                            <Form.Group controlId="formWard">
                                                <Form.Label className="fw-medium">
                                                    Phường/Xã
                                                </Form.Label>
                                                <Form.Control
                                                    as="select"
                                                    name="ward"
                                                    value={ward}
                                                    onChange={
                                                        this.handleWardChange
                                                    }
                                                    required
                                                    disabled={!district}
                                                    className="rounded-3"
                                                >
                                                    <option value="">
                                                        Chọn Phường/Xã
                                                    </option>
                                                    {loadingWards ? (
                                                        <option>
                                                            Đang tải...
                                                        </option>
                                                    ) : (
                                                        wards.map((w) => (
                                                            <option
                                                                key={w.ward_id}
                                                                value={
                                                                    w.ward_id
                                                                }
                                                            >
                                                                {w.ward_name}
                                                            </option>
                                                        ))
                                                    )}
                                                </Form.Control>
                                            </Form.Group>
                                        </Col>
                                    </Row>

                                    <h5 className="mb-3 fw-semibold">
                                        Ghi chú
                                    </h5>
                                    <Form.Group
                                        controlId="formNotes"
                                        className="mb-4"
                                    >
                                        <Form.Control
                                            as="textarea"
                                            rows={3}
                                            placeholder="Nhập ghi chú (nếu có)"
                                            name="notes"
                                            value={notes}
                                            onChange={this.handleInputChange}
                                            className="rounded-3"
                                        />
                                    </Form.Group>

                                    <h5 className="mb-3 fw-semibold">
                                        Phương thức thanh toán
                                    </h5>
                                    <Form.Group
                                        controlId="formPaymentMethod"
                                        className="mb-4"
                                    >
                                        <Form.Check
                                            type="radio"
                                            label="Chuyển khoản ngân hàng"
                                            name="paymentMethod"
                                            value="bankTransfer"
                                            checked={
                                                paymentMethod === "bankTransfer"
                                            }
                                            onChange={this.handleInputChange}
                                            className="mb-2"
                                        />
                                        <Form.Check
                                            type="radio"
                                            label="Thanh toán khi nhận hàng (COD)"
                                            name="paymentMethod"
                                            value="cod"
                                            checked={paymentMethod === "cod"}
                                            onChange={this.handleInputChange}
                                        />
                                    </Form.Group>

                                    <Button
                                        variant="primary"
                                        type="submit"
                                        className="w-100 py-3 rounded-3 fw-semibold"
                                        disabled={
                                            creatingOrder ||
                                            initiatingMomoPayment
                                        }
                                    >
                                        {creatingOrder || initiatingMomoPayment
                                            ? "Đang xử lý..."
                                            : "Xác nhận thanh toán"}
                                    </Button>
                                </Form>
                            </Card.Body>
                        </Card>
                    </Col>
                    <Col lg={4}>
                        <Card className="shadow-sm border-0">
                            <Card.Body className="p-4">
                                <h4 className="mb-4 fw-bold text-primary">
                                    Tóm tắt đơn hàng
                                </h4>
                                {carts.length === 0 ? (
                                    <p className="text-muted">Giỏ hàng trống</p>
                                ) : (
                                    <>
                                        <div className="cart-items">
                                            {carts.map((item) => (
                                                <div
                                                    key={item.book_id}
                                                    className="cart-item d-flex mb-3 align-items-center"
                                                >
                                                    <Image
                                                        src={item.cover_image}
                                                        alt={item.title}
                                                        className="item-image rounded"
                                                        style={{
                                                            width: "60px",
                                                            height: "80px",
                                                            objectFit: "cover",
                                                        }}
                                                    />
                                                    <div className="item-details ms-3 flex-grow-1">
                                                        <h6 className="item-title mb-1 fw-semibold">
                                                            {item.title}
                                                        </h6>
                                                        <p className="item-price mb-1 text-muted">
                                                            {item.unit_price.toLocaleString(
                                                                "vi-VN"
                                                            )}{" "}
                                                            đ x {item.quantity}
                                                        </p>
                                                        <div className="item-actions d-flex align-items-center">
                                                            <Form.Control
                                                                type="number"
                                                                min="1"
                                                                value={
                                                                    item.quantity
                                                                }
                                                                onChange={(e) =>
                                                                    this.handleQuantityChange(
                                                                        item.book_id,
                                                                        e.target
                                                                            .value
                                                                    )
                                                                }
                                                                className="quantity-input rounded-3"
                                                                style={{
                                                                    width: "60px",
                                                                }}
                                                            />
                                                            <Button
                                                                variant="link"
                                                                className="remove-button ms-2 text-danger"
                                                                onClick={() =>
                                                                    this.handleRemoveItem(
                                                                        item.book_id
                                                                    )
                                                                }
                                                            >
                                                                <FaTrash />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        <hr />
                                        <div className="cart-summary">
                                            <div className="summary-row d-flex justify-content-between mb-2">
                                                <span className="fw-medium">
                                                    Tạm tính:
                                                </span>
                                                <span>
                                                    {total_amount.toLocaleString(
                                                        "vi-VN"
                                                    )}
                                                    đ
                                                </span>
                                            </div>
                                            <div className="summary-row d-flex justify-content-between mb-2">
                                                <span className="fw-medium">
                                                    Phí vận chuyển:
                                                </span>
                                                <span>
                                                    {loadingShippingFee
                                                        ? "Đang tính..."
                                                        : shippingFee.toLocaleString(
                                                              "vi-VN"
                                                          )}
                                                    đ
                                                </span>
                                            </div>
                                            <div className="summary-row d-flex justify-content-between fw-bold">
                                                <span>Tổng cộng:</span>
                                                <span>
                                                    {final_amount.toLocaleString(
                                                        "vi-VN"
                                                    )}
                                                    đ
                                                </span>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </Card.Body>
                        </Card>
                    </Col>
                </Row>

                <Modal
                    show={showConfirmModal}
                    onHide={this.handleCloseConfirmModal}
                    centered
                >
                    <Modal.Header closeButton>
                        <Modal.Title>Xác nhận đơn hàng</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <h5 className="fw-semibold">Thông tin người nhận</h5>
                        <p>
                            <strong>Tên:</strong> {recipientName}
                        </p>
                        <p>
                            <strong>Số điện thoại:</strong> {phoneNumber}
                        </p>
                        <h5 className="fw-semibold mt-3">Địa chỉ giao hàng</h5>
                        <p>
                            <strong>Địa chỉ:</strong> {shippingAddress}
                            {selectedWard ? `, ${selectedWard}` : ""}
                            {selectedDistrict ? `, ${selectedDistrict}` : ""}
                            {selectedProvince ? `, ${selectedProvince}` : ""}
                        </p>
                        <h5 className="fw-semibold mt-3">Ghi chú</h5>
                        <p>{notes || "Không có ghi chú"}</p>
                        <h5 className="fw-semibold mt-3">
                            Phương thức thanh toán
                        </h5>
                        <p>{paymentMethodText}</p>
                        <h5 className="fw-semibold mt-3">Tóm tắt đơn hàng</h5>
                        {carts.map((item) => (
                            <div key={item.book_id} className="d-flex mb-2">
                                <Image
                                    src={item.cover_image}
                                    alt={item.title}
                                    style={{
                                        width: "40px",
                                        height: "50px",
                                        objectFit: "cover",
                                    }}
                                    className="me-2"
                                />
                                <div>
                                    <p className="mb-0">{item.title}</p>
                                    <p className="mb-0 text-muted">
                                        {item.quantity} x{" "}
                                        {item.unit_price.toLocaleString(
                                            "vi-VN"
                                        )}
                                        đ
                                    </p>
                                </div>
                            </div>
                        ))}
                        <hr />
                        <p>
                            <strong>Tạm tính:</strong>{" "}
                            {total_amount.toLocaleString("vi-VN")}đ
                        </p>
                        <p>
                            <strong>Phí vận chuyển:</strong>{" "}
                            {loadingShippingFee
                                ? "Đang tính..."
                                : shippingFee.toLocaleString("vi-VN")}
                            đ
                        </p>
                        <p>
                            <strong>Tổng cộng:</strong>{" "}
                            {final_amount.toLocaleString("vi-VN")}đ
                        </p>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button
                            variant="secondary"
                            onClick={this.handleCloseConfirmModal}
                        >
                            Hủy
                        </Button>
                        <Button
                            variant="primary"
                            onClick={this.handleConfirmOrder}
                            disabled={sendingOtp}
                        >
                            {sendingOtp
                                ? "Đang gửi OTP..."
                                : "Xác nhận đặt hàng"}
                        </Button>
                    </Modal.Footer>
                </Modal>

                <Modal
                    show={showOtpModal}
                    onHide={this.handleCloseOtpModal}
                    centered
                >
                    <Modal.Header closeButton>
                        <Modal.Title>Xác minh OTP</Modal.Title>
                    </Modal.Header>
                    <Modal.Body>
                        <p>Vui lòng nhập mã OTP được gửi đến email của bạn</p>
                        <Form.Group controlId="formOtp" className="mb-3">
                            <Form.Label>Mã OTP</Form.Label>
                            <Form.Control
                                type="text"
                                placeholder="Nhập mã OTP"
                                name="otp"
                                value={otp}
                                onChange={this.handleOtpChange}
                                className="rounded-3"
                            />
                            {otpError && (
                                <p className="text-danger mt-2">{otpError}</p>
                            )}
                        </Form.Group>
                    </Modal.Body>
                    <Modal.Footer>
                        <Button
                            variant="secondary"
                            onClick={this.handleCloseOtpModal}
                        >
                            Hủy
                        </Button>
                        <Button
                            variant="primary"
                            onClick={this.handleVerifyOtp}
                            disabled={verifyingOtp}
                        >
                            {verifyingOtp ? "Đang xác minh..." : "Xác minh OTP"}
                        </Button>
                    </Modal.Footer>
                </Modal>
            </Container>
        );
    }
}

const mapStateToProps = (state) => ({
    cartItems: state.cartReducer.cartItems,
    provinces: state.orderReducer.provinces,
    districts: state.orderReducer.districts,
    wards: state.orderReducer.wards,
    loadingProvinces: state.orderReducer.loadingProvinces,
    loadingDistricts: state.orderReducer.loadingDistricts,
    loadingWards: state.orderReducer.loadingWards,
    sendingOtp: state.orderReducer.sendingOtp,
    otpSent: state.orderReducer.otpSent,
    verifyingOtp: state.orderReducer.verifyingOtp,
    otpVerified: state.orderReducer.otpVerified,
    otpError: state.orderReducer.otpError,
    shippingFee: state.orderReducer.shippingFee,
    loadingShippingFee: state.orderReducer.loadingShippingFee,
    creatingOrder: state.orderReducer.creatingOrder,
    orderCreated: state.orderReducer.orderCreated,
    orderData: state.orderReducer.orderData,
    initiatingMomoPayment: state.orderReducer.initiatingMomoPayment,
    momoPayUrl: state.orderReducer.momoPayUrl,
    createOrderError: state.orderReducer.createOrderError,
    momoPaymentError: state.orderReducer.momoPaymentError,
});

const mapDispatchToProps = (dispatch) => ({
    updateCartItemQuantity: (book_id, quantity) =>
        dispatch(updateCartItemQuantity(book_id, quantity)),
    getProvinces: () => dispatch(getProvinces()),
    getDistricts: (provinceId) => dispatch(getDistricts(provinceId)),
    getWards: (districtId) => dispatch(getWards(districtId)),
    sendOtp: (orderDetails) => dispatch(sendOtp(orderDetails)),
    verifyOtp: (otpData) => dispatch(verifyOtp(otpData)),
    getShippingFee: (districtId, wardId) =>
        dispatch(getShippingFee(districtId, wardId)),
    createOrder: (orderDetails) => dispatch(createOrder(orderDetails)),
    initiateMomoPayment: (orderCode) =>
        dispatch(initiateMomoPayment(orderCode)),
});

export default connect(mapStateToProps, mapDispatchToProps)(Checkout);
