import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";

import { message, Spin } from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import {
    deleteBook,
    fetchAllBooks,
    fetchBooks,
} from "../../store/actions/book/bookActions";
import "@ant-design/v5-patch-for-react-19";
import { parsePermissionsForPage } from "../utils/permissionHelper";
import { getPermissionsFromApi } from "src/store/actions/user/userActions";

class BookManagement extends Component {
    constructor(props) {
        super(props);
        this.state = {
            columns: [],
            access: false,
            create: false,
            update: false,
            delete: false,
            expandedRows: {},
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
                console.log("State sau khi set:", this.state);
                if (this.state.access) {
                    this.props.fetchBooks();
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
                message.error(`Có lỗi xảy ra khi tải dữ liệu sách: ${error}`);
            }
        }
        if (
            prevProps.actionState !== this.props.actionState &&
            this.props.actionState === true
        ) {
            message.success("Thao tác thành công!");
            this.props.fetchBooks();
        }
        if (
            prevProps.books !== this.props.books &&
            Array.isArray(this.props.books?.books)
        ) {
            const columns = this.generateColumns(this.props.books.books);
            this.setState({ columns });
        }
    }

    renderExpandableText = (text, keyPrefix, maxLength, record) => {
        const fullText = text || "";
        const key = `${keyPrefix}-${record.id}`;
        const isExpanded = this.state.expandedRows?.[key] === true;
        const shouldTruncate = fullText.length > maxLength;

        const displayText =
            isExpanded || !shouldTruncate
                ? fullText
                : fullText.slice(0, maxLength) + "...";

        const toggleButton = shouldTruncate ? (
            <span
                onClick={() => {
                    this.setState((prevState) => ({
                        expandedRows: {
                            ...prevState.expandedRows,
                            [key]: !isExpanded,
                        },
                    }));
                }}
                style={{
                    color: "#1677ff",
                    cursor: "pointer",
                    marginLeft: 8,
                }}
            >
                {isExpanded ? "Thu gọn" : "Xem thêm"}
            </span>
        ) : null;

        return (
            <>
                <span>{displayText}</span>
                {toggleButton}
            </>
        );
    };

    generateColumns(books) {
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Ảnh bìa",
                dataIndex: "cover_image",
                key: "cover_image",
                width: 100,
                render: (url) => (
                    <img
                        src={url}
                        alt="Ảnh bìa"
                        style={{
                            width: 60,
                            height: 90,
                            objectFit: "cover",
                            borderRadius: 4,
                        }}
                    />
                ),
            },
            {
                title: "Tên sách",
                dataIndex: "title",
                key: "title",
                width: 200,
                render: (text, record) =>
                    this.renderExpandableText(text, "title", 50, record),
            },
            {
                title: "Tác giả",
                dataIndex: "authors",
                key: "authors",
                width: 200,
                render: (authors, record) => {
                    const text = authors?.map((a) => a.name).join(", ") || "";
                    return this.renderExpandableText(
                        text,
                        "authors",
                        50,
                        record
                    );
                },
            },
            {
                title: "Thể loại",
                dataIndex: "categories",
                key: "categories",
                width: 250,
                render: (categories, record) => {
                    const text =
                        categories?.map((c) => c.name).join(", ") || "";
                    return this.renderExpandableText(text, "cat", 40, record);
                },
            },
            {
                title: "Mô tả",
                dataIndex: "description",
                key: "description",
                width: 400,
                render: (description, record) =>
                    this.renderExpandableText(description, "desc", 100, record),
            },
            {
                title: "Ngày xuất bản",
                dataIndex: "published_date",
                key: "published_date",
                width: 180,
                render: (text) => {
                    const date = new Date(text);
                    const time = date.toLocaleTimeString("vi-VN", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    });
                    const day = date.toLocaleDateString("vi-VN", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    });
                    return `${time} ${day}`;
                },
            },
            {
                title: "Nhà xuất bản",
                dataIndex: "publisher",
                key: "publisher",
                width: 200,
            },
            {
                title: "Số trang",
                dataIndex: "page_count",
                key: "page_count",
                width: 100,
            },
            {
                title: "Giá",
                dataIndex: "price",
                key: "price",
                width: 120,
                render: (price) => `${Number(price).toLocaleString("vi-VN")} đ`,
            },
            {
                title: "Số lượng",
                dataIndex: "quantity_in_stock",
                key: "quantity_in_stock",
                width: 100,
            },
            {
                title: "Ngày tạo",
                dataIndex: "created_at",
                key: "created_at",
                width: 200,
                render: (text) => {
                    const date = new Date(text);
                    const time = date.toLocaleTimeString("vi-VN", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    });
                    const day = date.toLocaleDateString("vi-VN", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    });
                    return `${time} ${day}`;
                },
            },
            {
                title: "Ngày cập nhật",
                dataIndex: "updated_at",
                key: "updated_at",
                width: 200,
                render: (text) => {
                    const date = new Date(text);
                    const time = date.toLocaleTimeString("vi-VN", {
                        hour: "2-digit",
                        minute: "2-digit",
                        hour12: false,
                    });
                    const day = date.toLocaleDateString("vi-VN", {
                        day: "2-digit",
                        month: "2-digit",
                        year: "numeric",
                    });
                    return `${time} ${day}`;
                },
            },
        ];
    }
    formFields = [
        {
            name: "name",
            label: "Tên sách",
            placeholder: "Nhập tên sách",
            rules: [{ required: true, message: "Vui lòng nhập tên sách!" }],
        },
    ];

    handleAdd = async (values) => {
        const book = {
            name: values.name,
        };
        await this.props.addBook(book);
        // message.success(`Đã thêm sách: ${values.name}`);
    };

    handleUpdate = async (values) => {
        const id = values.id;
        const book = {
            name: values.name,
        };
        // await this.props.updateBook(id, book);
    };

    handleDelete = async (key) => {
        await this.props.deleteBook(key);
    };

    render() {
        const { books, loading } = this.props;
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

        console.log("Books data:", books);
        const dataWithKey = Array.isArray(books?.books)
            ? books.books.map((item) => ({
                  ...item,
                  key: item.id,
              }))
            : [];

        return (
            <DataManagementPage
                title="Quản lý sách"
                subtitle="Xem và quản lý các sách trong hệ thống."
                columns={columns}
                data={dataWithKey}
                rowKey="key"
                formFields={this.formFields}
                loading={loading}
                onDelete={this.state.delete === true ? this.handleDelete : null}
            />
        );
    }
}

const mapStateToProps = (state) => ({
    books: state.bookReducer.books,
    loading: state.bookReducer.loading,
    error: state.bookReducer.error,
    actionState: state.bookReducer.actionState,
});

const mapDispatchToProps = (dispatch) => {
    return {
        fetchBooks: () => dispatch(fetchAllBooks()),
        deleteBook: (id) => dispatch(deleteBook(id)),
    };
};
export default connect(mapStateToProps, mapDispatchToProps)(BookManagement);
