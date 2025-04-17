import React, { Component } from "react";
import { connect } from "react-redux";
import DataManagementPage from "../components/DataManagementPage";

import { message, Spin } from "antd";
import { Navigate } from "react-router-dom";
import { toast } from "react-toastify";
import {
    addPublisher,
    deletePublisher,
    fetchPublishers,
    updatePublisher,
} from "../../store/actions/publisher/publisherAction";
import "@ant-design/v5-patch-for-react-19";
import { parsePermissionsForPage } from "../utils/permissionHelper";
import { getPermissionsFromApi } from "src/store/actions/user/userActions";

class PublisherManagement extends Component {
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

            const parsed = parsePermissionsForPage(permissions, "publishers");

            this.setState({ ...parsed, permissionsLoaded: true }, () => {
                console.log("State sau khi set:", this.state);
                if (this.state.access) {
                    this.props.fetchPublishers();
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
                    `Có lỗi xảy ra khi tải dữ liệu nhà xuất bản: ${error}`
                );
            }
        }
        if (
            prevProps.actionState !== this.props.actionState &&
            this.props.actionState === true
        ) {
            message.success("Thao tác thành công!");
            this.props.fetchPublishers();
        }

        if (
            prevProps.publishers !== this.props.publishers &&
            Array.isArray(this.props.publishers?.data)
        ) {
            console.log("prevProps.publishers", this.props.publishers?.data);
            const columns = this.generateColumns(this.props.publishers.data);
            this.setState({ columns });
        }
    }
    generateColumns(publishers) {
        return [
            {
                title: "STT",
                key: "stt",
                width: 60,
                render: (text, record, index) => index + 1,
            },
            {
                title: "Tên nhà xuất bản",
                dataIndex: "name",
                key: "name",
                width: 200,
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
            label: "Tên nhà xuất bản",
            placeholder: "Nhập tên nhà xuất bản",
            rules: [
                { required: true, message: "Vui lòng nhập tên nhà xuất bản!" },
            ],
        },
    ];

    handleAdd = async (values) => {
        const publisher = {
            name: values.name,
        };
        await this.props.addPublisher(publisher);
        // message.success(`Đã thêm nhà xuất bản: ${values.name}`);
    };

    handleUpdate = async (values) => {
        console.log("values", values);
        const id = values.id;
        const publisher = {
            name: values.name,
        };
        await this.props.updatePublisher(id, publisher);
    };

    handleDelete = async (key) => {
        await this.props.deletePublisher(key);
    };

    render() {
        const { publishers, loading } = this.props;
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

        const dataWithKey = Array.isArray(publishers?.data)
            ? publishers.data.map((item) => ({
                  ...item,
                  key: item.id,
              }))
            : [];

        return (
            <DataManagementPage
                title="Quản lý nhà xuất bản"
                subtitle="Xem và quản lý các nhà xuất bản trong hệ thống."
                columns={columns}
                data={dataWithKey}
                rowKey="key"
                formFields={this.formFields}
                loading={loading}
                onAdd={this.state.create === true ? this.handleAdd : null}
                onUpdate={this.state.update === true ? this.handleUpdate : null}
                onDelete={this.state.delete === true ? this.handleDelete : null}
            />
        );
    }
}

const mapStateToProps = (state) => ({
    publishers: state.publisherReducer.publishers,
    loading: state.publisherReducer.loading,
    error: state.publisherReducer.error,
    actionState: state.publisherReducer.actionState,
});

const mapDispatchToProps = (dispatch) => {
    return {
        fetchPublishers: () => dispatch(fetchPublishers()),
        addPublisher: (publisher) => dispatch(addPublisher(publisher)),
        updatePublisher: (id, data) => dispatch(updatePublisher(id, data)),
        deletePublisher: (id) => dispatch(deletePublisher(id)),
    };
};
export default connect(
    mapStateToProps,
    mapDispatchToProps
)(PublisherManagement);
