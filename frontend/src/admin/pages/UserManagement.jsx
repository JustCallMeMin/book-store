import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";

import { message, Spin } from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import { fetchUserList } from "../../store/actions/user/userActions"; // Changed from fetchAuthors to fetchUserList
import { getPermissionsFromApi } from "src/store/actions/user/userActions";
import { parsePermissionsForPage } from "../utils/permissionHelper";

class UserManagement extends Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [],
            access: false,
            create: false,
            update: false,
            delete: false,
            permissionsLoaded: false,
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
                : await getPermissionsFromApi(user.roles[0]);

            if (!permissions) {
                toast.error("Không tìm thấy quyền truy cập");
                return;
            }

            const parsed = parsePermissionsForPage(permissions, "users"); // Changed from "books" to "users"

            this.setState({ ...parsed, permissionsLoaded: true }, () => {
                if (this.state.access) {
                    this.props.fetchUserList(); // Changed from fetchAuthors to fetchUserList
                }
            });
        } catch (err) {
            console.error("Lỗi khi lấy quyền truy cập", err);
            this.setState({ access: false, permissionsLoaded: true });
        }
    }

    componentDidUpdate(prevProps) {
        const { error } = this.props;

        if (prevProps.error !== error) {
            if (error) {
                message.error(
                    `Có lỗi xảy ra khi tải dữ liệu người dùng: ${error}` // Changed from "tác giả" to "người dùng"
                );
            }
        }
        if (
            prevProps.users !== this.props.users && // Changed from authors to users
            Array.isArray(this.props.users?.data)
        ) {
            const columns = this.generateColumns(this.props.users.data); // Changed from authors to users
            this.setState({ columns });
        }
    }

    generateColumns(users) {
        if (!users || users.length === 0) return [];
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Họ và tên",
                dataIndex: "full_name",
                key: "full_name",
                width: 200,
            },
            {
                title: "Email",
                dataIndex: "email",
                key: "email",
                width: 200,
            },
            {
                title: "Vai trò",
                dataIndex: "roles",
                key: "roles",
                width: 200,
                render: (roles) =>
                    Array.isArray(roles) ? roles.join(", ") : roles,
            },
            // {
            //     title: "Ngày tạo",
            //     dataIndex: "created_at",
            //     key: "created_at",
            //     width: 200,
            //     render: (text) =>
            //         new Date(text).toLocaleString("vi-VN", {
            //             hour: "2-digit",
            //             minute: "2-digit",
            //             day: "2-digit",
            //             month: "2-digit",
            //             year: "numeric",
            //         }),
            // },
            // {
            //     title: "Ngày cập nhật",
            //     dataIndex: "updated_at",
            //     key: "updated_at",
            //     width: 200,
            //     render: (text) =>
            //         new Date(text).toLocaleString("vi-VN", {
            //             hour: "2-digit",
            //             minute: "2-digit",
            //             day: "2-digit",
            //             month: "2-digit",
            //             year: "numeric",
            //         }),
            // },
        ];
    }

    formFields = [
        {
            name: "full_name",
            label: "Họ và tên",
            placeholder: "Nhập họ và tên",
            rules: [{ required: true, message: "Vui lòng nhập họ và tên!" }],
        },
        {
            name: "email",
            label: "Email",
            placeholder: "Nhập email",
            rules: [
                { required: true, message: "Vui lòng nhập email!" },
                { type: "email", message: "Email không hợp lệ!" },
            ],
        },
        {
            name: "roles",
            label: "Vai trò",
            placeholder: "Chọn vai trò",
            rules: [{ required: true, message: "Vui lòng chọn vai trò!" }],
        },
    ];

    handleAdd = (values) => {
        if (!this.state.create) {
            toast.error("Bạn không có quyền thêm người dùng"); // Changed from "tác giả" to "người dùng"
            return;
        }
        message.success(`Đã thêm người dùng: ${values.full_name}`); // Changed from "tác giả" to "người dùng"
    };

    handleUpdate = (values) => {
        if (!this.state.update) {
            toast.error("Bạn không có quyền cập nhật người dùng"); // Changed from "tác giả" to "người dùng"
            return;
        }
        message.success(`Đã cập nhật người dùng: ${values.full_name}`); // Changed from "tác giả" to "người dùng"
    };

    handleDelete = (key) => {
        if (!this.state.delete) {
            toast.error("Bạn không có quyền xóa người dùng"); // Changed from "tác giả" to "người dùng"
            return;
        }
        message.info(`Giả lập xóa người dùng có key: ${key}`); // Changed from "tác giả" to "người dùng"
    };

    render() {
        const { users, loading } = this.props; // Changed from authors to users
        const { columns, access, permissionsLoaded } = this.state;

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

        console.log("Users data:", users); // Changed from "Authors" to "Users"
        const dataWithKey = Array.isArray(users?.data)
            ? users.data.map((item) => ({
                  ...item,
                  key: item.id,
              }))
            : [];

        return (
            <DataManagementPage
                title="Quản lý người dùng" // Changed from "tác giả" to "người dùng"
                subtitle="Xem và quản lý các người dùng trong hệ thống." // Changed from "tác giả" to "người dùng"
                columns={columns}
                data={dataWithKey}
                rowKey="key"
                formFields={this.formFields}
                loading={loading}
            />
        );
    }
}

const mapStateToProps = (state) => ({
    users: state.userReducer.userList, // Changed from authorReducer to userReducer
    loading: state.userReducer.loading,
    error: state.userReducer.error,
});

const mapDispatchToProps = {
    fetchUserList, // Changed from fetchAuthors to fetchUserList
};

export default connect(mapStateToProps, mapDispatchToProps)(UserManagement);
