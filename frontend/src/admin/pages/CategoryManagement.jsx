import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";

import { message, Spin } from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import { fetchCategories } from "src/store/actions/category/categoryAction";

import { parsePermissionsForPage } from "../utils/permissionHelper";
import { getPermissionsFromApi } from "src/store/actions/user/userActions";

class CategoryManagement extends Component {
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

            const parsed = parsePermissionsForPage(permissions, "categories");

            this.setState({ ...parsed, permissionsLoaded: true }, () => {
                console.log("State sau khi set:", this.state);
                if (this.state.access) {
                    this.props.fetchCategories();
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
                    `Có lỗi xảy ra khi tải dữ liệu danh mục: ${error}`
                );
            }
        }
        if (
            prevProps.categories !== this.props.categories &&
            Array.isArray(this.props.categories?.data)
        ) {
            const columns = this.generateColumns(this.props.categories.data);
            this.setState({ columns });
        }
    }

    generateColumns(categories) {
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Tên danh mục",
                dataIndex: "name",
                key: "name",
                width: 200,
            },
            {
                title: "Ngày tạo",
                dataIndex: "created_at",
                key: "created_at",
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
                title: "Ngày cập nhật",
                dataIndex: "updated_at",
                key: "updated_at",
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
        ];
    }

    formFields = [
        {
            name: "name",
            label: "Tên danh mục",
            placeholder: "Nhập tên danh mục",
            rules: [{ required: true, message: "Vui lòng nhập tên danh mục!" }],
        },
    ];

    handleAdd = (values) => {
        if (!this.state.create) {
            toast.error("Bạn không có quyền tạo danh mục");
            return;
        }
        message.success(`Đã thêm danh mục: ${values.name}`);
    };

    handleUpdate = (values) => {
        if (!this.state.update) {
            toast.error("Bạn không có quyền cập nhật danh mục");
            return;
        }
        message.success(`Đã cập nhật danh mục: ${values.name}`);
    };

    handleDelete = (key) => {
        if (!this.state.delete) {
            toast.error("Bạn không có quyền xóa danh mục");
            return;
        }
        message.info(`Giả lập xóa danh mục có key: ${key}`);
    };

    render() {
        const { categories, loading } = this.props;
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

        const dataWithKey = Array.isArray(categories?.data)
            ? categories.data.map((item) => ({
                  ...item,
                  key: item.id,
              }))
            : [];

        return (
            <DataManagementPage
                title="Quản lý danh mục"
                subtitle="Xem và quản lý các danh mục trong hệ thống."
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
    categories: state.categoryReducer.categories,
    loading: state.categoryReducer.loading,
    error: state.categoryReducer.error,
});

const mapDispatchToProps = {
    fetchCategories,
};

export default connect(mapStateToProps, mapDispatchToProps)(CategoryManagement);
