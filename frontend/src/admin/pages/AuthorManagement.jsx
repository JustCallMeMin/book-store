import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";

import { message, Spin } from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import { fetchAuthors } from "src/store/actions/author/authorAction";
import { getPermissionsFromApi } from "src/store/actions/user/userActions";
import { parsePermissionsForPage } from "../utils/permissionHelper";

class AuthorManagement extends Component {
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

            const parsed = parsePermissionsForPage(permissions, "books");

            this.setState({ ...parsed, permissionsLoaded: true }, () => {
                if (this.state.access) {
                    this.props.fetchAuthors();
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
                    `Có lỗi xảy ra khi tải dữ liệu tác giả: ${error}`
                );
            }
        }
        if (
            prevProps.authors !== this.props.authors &&
            Array.isArray(this.props.authors?.data)
        ) {
            const columns = this.generateColumns(this.props.authors.data);
            this.setState({ columns });
        }
    }

    generateColumns(authors) {
        if (!authors || authors.length === 0) return [];
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Tên tác giả",
                dataIndex: "name",
                key: "name",
                width: 200,
            },
            {
                title: "Năm sinh",
                dataIndex: "birth_year",
                key: "birth_year",
                width: 200,
            },
            {
                title: "Năm mất",
                dataIndex: "death_year",
                key: "death_year",
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
            label: "Tên tác giả",
            placeholder: "Nhập tên tác giả",
            rules: [{ required: true, message: "Vui lòng nhập tên tác giả!" }],
        },
    ];

    handleAdd = (values) => {
        if (!this.state.create) {
            toast.error("Bạn không có quyền thêm tác giả");
            return;
        }
        message.success(`Đã thêm tác giả: ${values.name}`);
    };

    handleUpdate = (values) => {
        if (!this.state.update) {
            toast.error("Bạn không có quyền cập nhật tác giả");
            return;
        }
        message.success(`Đã cập nhật tác giả: ${values.name}`);
    };

    handleDelete = (key) => {
        if (!this.state.delete) {
            toast.error("Bạn không có quyền xóa tác giả");
            return;
        }
        message.info(`Giả lập xóa tác giả có key: ${key}`);
    };

    render() {
        const { authors, loading } = this.props;
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

        console.log("Authors data:", authors);
        const dataWithKey = Array.isArray(authors?.data)
            ? authors.data.map((item) => ({
                  ...item,
                  key: item.id,
              }))
            : [];

        return (
            <DataManagementPage
                title="Quản lý tác giả"
                subtitle="Xem và quản lý các tác giả trong hệ thống."
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
    authors: state.authorReducer.authors,
    loading: state.authorReducer.loading,
    error: state.authorReducer.error,
});

const mapDispatchToProps = {
    fetchAuthors,
};

export default connect(mapStateToProps, mapDispatchToProps)(AuthorManagement);
