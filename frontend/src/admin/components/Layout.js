// src/admin/components/MainLayout.jsx
import React, { Component } from "react";
import { Layout as AntLayout, Dropdown, message } from "antd";
import {
    BankOutlined,
    BookOutlined,
    DashboardOutlined,
    EditOutlined,
    FolderOutlined,
    OrderedListOutlined,
} from "@ant-design/icons";
import { Link, Outlet } from "react-router-dom";
import UserInfoCard from "./UserInfoCard";
import HeaderSection from "./HeaderSection";
import SidebarMenu from "./SidebarMenu";
import { getPermissionsFromApi } from "../../store/actions/user/userActions";
import { parsePermissionsForPage } from "../utils/permissionHelper";
import "../styles/admin.css";
import withNavigate from "src/store/HOC/withNavigate";
import { FaJediOrder } from "react-icons/fa";

const { Content } = AntLayout;

class MainLayout extends Component {
    constructor(props) {
        super(props);

        const storedUser = sessionStorage.getItem("user");
        const parsedUser = storedUser ? JSON.parse(storedUser) : null;

        this.state = {
            userInfo: parsedUser
                ? {
                      name: parsedUser.full_name || "Người dùng",
                      avatar: parsedUser.avatar || null,
                      roles: parsedUser.roles || ["Admin"],
                      currentRole: parsedUser.roles?.[0] || "Admin",
                  }
                : null,
            collapsed: false,
            permissions: [], // Danh sách quyền hiện tại của currentRole
        };
    }

    componentDidMount() {
        this.handleResize();
        window.addEventListener("resize", this.handleResize);

        if (!this.state.userInfo) {
            this.props.navigate("/login");
            return;
        }

        this.loadPermissions(this.state.userInfo.currentRole);
    }

    componentWillUnmount() {
        window.removeEventListener("resize", this.handleResize);
    }

    handleResize = () => {
        const width = window.innerWidth;
        this.setState({ collapsed: width <= 768 });
    };

    toggleCollapse = () => {
        this.setState((prev) => ({ collapsed: !prev.collapsed }));
    };

    handleRoleChange = (newRole) => {
        this.setState(
            (prev) => ({
                userInfo: {
                    ...prev.userInfo,
                    currentRole: newRole,
                },
            }),
            () => {
                this.loadPermissions(newRole);
            }
        );
    };

    handleLogout = () => {
        sessionStorage.removeItem("user");
        message.success("Đăng xuất thành công");
        this.props.navigate("/login");
    };

    // Gọi API để lấy permission dựa trên role
    loadPermissions = async (currentRole) => {
        const roles = this.state.userInfo?.roles || [];
        const permissions = await getPermissionsFromApi(roles);
        this.setState({ permissions });
    };

    render() {
        const { userInfo, collapsed, permissions } = this.state;

        if (!userInfo) return null;
        console.log("Permissions:", permissions);
        // Phân quyền cho từng trang
        const bookPerm = parsePermissionsForPage(permissions, "books");
        const categoryPerm = parsePermissionsForPage(permissions, "categories");
        const publisherPerm = parsePermissionsForPage(
            permissions,
            "publishers"
        );

        const roleMenuItems = userInfo.roles.map((role) => ({
            key: role,
            label: role,
            onClick: () => this.handleRoleChange(role),
        }));

        const userMenu = {
            items: [
                {
                    key: "1",
                    label: <Link to="/admin/profile">Thông tin cá nhân</Link>,
                },
                {
                    key: "2",
                    label: "Đăng xuất",
                    onClick: this.handleLogout,
                },
            ],
        };

        // Tạo menu theo quyền
        const menuItems = [
            {
                key: "1",
                icon: <DashboardOutlined />,
                label: <Link to="/admin">Dashboard</Link>,
            },
            ...(categoryPerm.access
                ? [
                      {
                          key: "2",
                          icon: <FolderOutlined />,
                          label: (
                              <Link to="/admin/categories">
                                  Quản lý danh mục
                              </Link>
                          ),
                      },
                  ]
                : []),
            ...(bookPerm.access
                ? [
                      {
                          key: "3",
                          icon: <EditOutlined />,
                          label: (
                              <Link to="/admin/authors">Quản lý tác giả</Link>
                          ),
                      },
                  ]
                : []),
            ...(publisherPerm.access
                ? [
                      {
                          key: "4",
                          icon: <BankOutlined />,
                          label: (
                              <Link to="/admin/publishers">
                                  Quản lý nhà xuất bản
                              </Link>
                          ),
                      },
                  ]
                : []),
            ...(bookPerm.access
                ? [
                      {
                          key: "5",
                          icon: <BookOutlined />,
                          label: <Link to="/admin/books">Quản lý sách</Link>,
                      },
                  ]
                : []),
            ...(bookPerm.access
                ? [
                      {
                          key: "6",
                          icon: <OrderedListOutlined />,
                          label: (
                              <Link to="/admin/orders">Quản lý đơn hàng</Link>
                          ),
                      },
                  ]
                : []),
            // {
            //     key: "10",
            //     icon: <OrderedListOutlined />,
            //     label: "Quản lý đơn hàng",
            //     children: [
            //         // ...(classPerm.access
            //         //     ? [
            //         //           {
            //         //               key: "4",
            //         //               label: (
            //         //                   <Link to="/admin/classes/list">
            //         //                       Quản lý lớp học
            //         //                   </Link>
            //         //               ),
            //         //           },
            //         //       ]
            //         //     : []),
            //         // ...(mentorPerm.access
            //         //     ? [
            //         //           {
            //         //               key: "5",
            //         //               label: (
            //         //                   <Link to="/admin/mentors">
            //         //                       Quản lý giáo viên
            //         //                   </Link>
            //         //               ),
            //         //           },
            //         //       ]
            //         //     : []),
            //         // ...(studentPerm.access
            //         //     ? [
            //         //           {
            //         //               key: "6",
            //         //               label: (
            //         //                   <Link to="/admin/students">
            //         //                       Quản lý học viên
            //         //                   </Link>
            //         //               ),
            //         //           },
            //         //       ]
            //         //     : []),
            //         // ...(coursePerm.access || subjectPerm.access
            //         //     ? [
            //         //           {
            //         //               key: "7",
            //         //               label: "Quản lý khoá học",
            //         //               children: [
            //         //                   ...(coursePerm.access
            //         //                       ? [
            //         //                             {
            //         //                                 key: "8",
            //         //                                 label: (
            //         //                                     <Link to="/admin/courses">
            //         //                                         Quản lý khoá học
            //         //                                     </Link>
            //         //                                 ),
            //         //                             },
            //         //                         ]
            //         //                       : []),
            //         //                   ...(subjectPerm.access
            //         //                       ? [
            //         //                             {
            //         //                                 key: "9",
            //         //                                 label: (
            //         //                                     <Link to="/admin/subjects">
            //         //                                         Quản lý bộ môn
            //         //                                     </Link>
            //         //                                 ),
            //         //                             },
            //         //                         ]
            //         //                       : []),
            //         //               ],
            //         //           },
            //         //       ]
            //         //     : []),
            //     ],
            // },
        ];

        return (
            <AntLayout style={{ minHeight: "100vh" }}>
                <SidebarMenu
                    menuItems={menuItems}
                    collapsed={collapsed}
                    onCollapse={this.toggleCollapse}
                >
                    <Dropdown
                        menu={{ items: roleMenuItems }}
                        trigger={["click"]}
                    >
                        <UserInfoCard
                            userInfo={userInfo}
                            roleMenuItems={roleMenuItems}
                            collapsed={collapsed}
                        />
                    </Dropdown>
                </SidebarMenu>
                <AntLayout>
                    <HeaderSection
                        userInfo={userInfo}
                        userMenu={userMenu}
                        collapsed={collapsed}
                        toggleCollapse={this.toggleCollapse}
                    />
                    <Content
                        style={{
                            margin: "24px 16px",
                            padding: 24,
                            background: "#f0f2f5",
                        }}
                    >
                        <Outlet />
                    </Content>
                </AntLayout>
            </AntLayout>
        );
    }
}

export default withNavigate(MainLayout);
