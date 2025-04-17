import { useState, useEffect, useRef } from "react";
import {
    Table,
    Button,
    Modal,
    Form,
    Input,
    Select,
    message,
    Space,
    Checkbox,
} from "antd";
import { PlusOutlined, SearchOutlined } from "@ant-design/icons";
import styled from "styled-components";
import Highlighter from "react-highlight-words";
import colors from "../constants/colors";
import dayjs from "dayjs";

const { Option } = Select;

const StyledCard = styled.div`
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
`;

const DataTable = ({
    columns,
    data,
    loading = false,
    onAdd,
    onUpdate,
    onDelete,
    formFields = [],
}) => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deleteKey, setDeleteKey] = useState(null);
    const [editingRecord, setEditingRecord] = useState(null);
    const [modalLoading, setModalLoading] = useState(false);
    const [form] = Form.useForm();
    const [searchText, setSearchText] = useState("");
    const [searchedColumn, setSearchedColumn] = useState("");
    const [filteredData, setFilteredData] = useState(data);
    const [pagination, setPagination] = useState({ current: 1, pageSize: 5 });
    const searchInput = useRef(null);

    const hasAdd = typeof onAdd === "function";
    const hasUpdate = typeof onUpdate === "function";
    const hasDelete = typeof onDelete === "function";

    useEffect(() => {
        setFilteredData(data);
    }, [data]);

    const handleSearchAll = (value) => {
        setSearchText(value);
        setSearchedColumn("");
        const filtered = data.filter((item) =>
            Object.values(item).some(
                (val) =>
                    val &&
                    val.toString().toLowerCase().includes(value.toLowerCase())
            )
        );
        setFilteredData(filtered);
        setPagination({ ...pagination, current: 1 });
    };

    const showModal = (record = null) => {
        setEditingRecord(record);
        if (record) {
            form.setFieldsValue(record);
        } else {
            form.resetFields();
        }
        setIsModalOpen(true);
    };

    const handleOk = async () => {
        try {
            setModalLoading(true);
            const values = await form.validateFields();

            if (editingRecord && hasUpdate) {
                await onUpdate({ ...values, id: editingRecord.id });
            } else if (!editingRecord && hasAdd) {
                await onAdd(values);
            }

            setIsModalOpen(false);
            form.resetFields();
        } catch (error) {
            message.error(error.message || "Đã có lỗi xảy ra!");
        } finally {
            setModalLoading(false);
        }
    };

    const showDeleteConfirm = (key) => {
        setDeleteKey(key);
        setIsDeleteModalOpen(true);
    };

    const handleDeleteConfirm = () => {
        setModalLoading(true);
        onDelete(deleteKey)
            .catch((error) => {
                message.error(error.message || "Đã có lỗi xảy ra!");
            })
            .finally(() => {
                setModalLoading(false);
                setIsDeleteModalOpen(false);
                setDeleteKey(null);
            });
    };

    const getColumnSearchProps = (dataIndex) => ({
        filterDropdown: ({
            setSelectedKeys,
            selectedKeys,
            confirm,
            clearFilters,
        }) => (
            <div style={{ padding: 8 }}>
                <Input
                    ref={searchInput}
                    placeholder={`Tìm ${dataIndex}`}
                    value={selectedKeys[0]}
                    onChange={(e) =>
                        setSelectedKeys(e.target.value ? [e.target.value] : [])
                    }
                    onPressEnter={() =>
                        handleColumnSearch(selectedKeys, confirm, dataIndex)
                    }
                    style={{ marginBottom: 8, display: "block" }}
                />
                <Space>
                    <Button
                        type="primary"
                        onClick={() =>
                            handleColumnSearch(selectedKeys, confirm, dataIndex)
                        }
                        icon={<SearchOutlined />}
                        size="small"
                        style={{ width: 90 }}
                    >
                        Tìm
                    </Button>
                    <Button
                        onClick={() =>
                            handleColumnReset(clearFilters, dataIndex)
                        }
                        size="small"
                        style={{ width: 90 }}
                    >
                        Xóa
                    </Button>
                </Space>
            </div>
        ),
        filterIcon: (filtered) => (
            <SearchOutlined
                style={{ color: filtered ? "#1890ff" : undefined }}
            />
        ),
        onFilter: (value, record) => {
            const val = record[dataIndex];
            return val
                ? val.toString().toLowerCase().includes(value.toLowerCase())
                : false;
        },
        filterDropdownProps: {
            onOpenChange: (visible) => {
                if (visible) {
                    setTimeout(() => searchInput.current?.select(), 100);
                }
            },
        },
        render: (text) =>
            searchedColumn === dataIndex ? (
                <Highlighter
                    highlightStyle={{ backgroundColor: "#ffc069", padding: 0 }}
                    searchWords={[searchText]}
                    autoEscape
                    textToHighlight={text ? text.toString() : ""}
                />
            ) : (
                text
            ),
    });

    const handleColumnSearch = (selectedKeys, confirm, dataIndex) => {
        confirm();
        const searchValue = selectedKeys[0] || "";
        setSearchText(searchValue);
        setSearchedColumn(dataIndex);

        const filtered = data.filter((item) => {
            const val = item[dataIndex];
            return val
                ? val
                      .toString()
                      .toLowerCase()
                      .includes(searchValue.toLowerCase())
                : false;
        });
        setFilteredData(filtered);
        setPagination({ ...pagination, current: 1 });
    };

    const handleColumnReset = (clearFilters, dataIndex) => {
        clearFilters();
        setSearchText("");
        setSearchedColumn("");
        setFilteredData(data);
        setPagination({ ...pagination, current: 1 });
    };

    const actionColumn =
        hasUpdate || hasDelete
            ? {
                  title: "Hành động",
                  key: "action",
                  fixed: "right",
                  width: 120,
                  render: (_, record) => (
                      <Space>
                          {hasUpdate && (
                              <Button
                                  type="link"
                                  style={{ color: colors.primary }}
                                  onClick={() => showModal(record)}
                                  disabled={loading || modalLoading}
                              >
                                  Sửa
                              </Button>
                          )}
                          {hasDelete && (
                              <Button
                                  type="link"
                                  danger
                                  onClick={() => showDeleteConfirm(record.key)}
                                  disabled={loading || modalLoading}
                              >
                                  Xóa
                              </Button>
                          )}
                      </Space>
                  ),
              }
            : null;

    const enhancedColumns = columns.map((col) => {
        const { dataIndex } = col;
        const hasCustomSorter = !!col.sorter;
        return {
            ...col,
            width: col.width || 150,
            sorter: hasCustomSorter
                ? col.sorter
                : (a, b) => {
                      const aVal = a[dataIndex];
                      const bVal = b[dataIndex];
                      if (
                          typeof aVal === "string" &&
                          typeof bVal === "string"
                      ) {
                          return aVal.localeCompare(bVal);
                      }
                      if (
                          typeof aVal === "number" &&
                          typeof bVal === "number"
                      ) {
                          return aVal - bVal;
                      }
                      return 0;
                  },
            sortDirections: ["ascend", "descend"],
            ...(dataIndex ? getColumnSearchProps(dataIndex) : {}),
            render:
                col.type === "date"
                    ? (value) =>
                          value ? dayjs(value).format("HH:mm DD/MM/YYYY") : ""
                    : col.render,
        };
    });

    return (
        <StyledCard>
            <div
                style={{
                    marginBottom: 16,
                    display: "flex",
                    justifyContent: "space-between",
                }}
            >
                <Input
                    placeholder="Tìm kiếm toàn bộ..."
                    prefix={<SearchOutlined />}
                    value={searchText}
                    onChange={(e) => handleSearchAll(e.target.value)}
                    style={{ width: 250 }}
                />
                {hasAdd && (
                    <Button
                        type="primary"
                        icon={<PlusOutlined />}
                        onClick={() => showModal()}
                        style={{ background: colors.primary, border: "none" }}
                        disabled={loading || modalLoading}
                    >
                        Thêm mới
                    </Button>
                )}
            </div>

            <Table
                columns={
                    actionColumn
                        ? [...enhancedColumns, actionColumn]
                        : enhancedColumns
                }
                dataSource={filteredData}
                loading={loading}
                pagination={{
                    ...pagination,
                    total: filteredData.length,
                    onChange: (page, pageSize) =>
                        setPagination({ current: page, pageSize }),
                }}
                scroll={{ x: "max-content" }}
            />

            <Modal
                title={editingRecord ? "Chỉnh sửa" : "Thêm mới"}
                open={isModalOpen}
                onOk={handleOk}
                onCancel={() => {
                    setIsModalOpen(false);
                    form.resetFields();
                    setEditingRecord(null);
                }}
                okText={editingRecord ? "Cập nhật" : "Thêm"}
                cancelText="Hủy"
                confirmLoading={modalLoading}
            >
                <Form form={form} layout="vertical">
                    {formFields.length > 0 ? (
                        formFields.map((field) => (
                            <Form.Item
                                key={field.name}
                                name={field.name}
                                label={field.label}
                                rules={field.rules || []}
                            >
                                {field.type === "checkbox-group" ? (
                                    <Checkbox.Group options={field.options} />
                                ) : field.type === "select" ? (
                                    <Select placeholder={field.placeholder}>
                                        {field.options?.map((option) => (
                                            <Option
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </Option>
                                        ))}
                                    </Select>
                                ) : (
                                    <Input
                                        placeholder={field.placeholder}
                                        type={field.type || "text"}
                                    />
                                )}
                            </Form.Item>
                        ))
                    ) : (
                        <p>Không có trường nhập liệu nào được định nghĩa.</p>
                    )}
                </Form>
            </Modal>

            <Modal
                title="Xác nhận xóa"
                open={isDeleteModalOpen}
                onOk={handleDeleteConfirm}
                onCancel={() => {
                    setIsDeleteModalOpen(false);
                    setDeleteKey(null);
                }}
                okText="Xóa"
                okType="danger"
                cancelText="Hủy"
                confirmLoading={modalLoading}
            >
                <p>
                    Bạn có chắc chắn muốn xóa? Hành động này không thể hoàn tác.
                </p>
            </Modal>
        </StyledCard>
    );
};

export default DataTable;
