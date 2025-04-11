// src/utils/permissionHelper.js
export const parsePermissionsForPage = (permissions, page) => {
    const result = {
        access: false,
        create: false,
        update: false,
        delete: false,
    };

    // Duyệt qua từng quyền
    permissions.forEach((perm) => {
        // Kiểm tra xem quyền có thuộc module cần kiểm tra hay không
        if (perm.startsWith(`${page}:`)) {
            // Nếu user có bất kỳ quyền nào của module này thì coi như đã có "access"
            result.access = true;
            if (perm.endsWith("create")) result.create = true;
            if (perm.endsWith("update")) result.update = true;
            if (perm.endsWith("delete")) result.delete = true;
        }
    });

    return result;
};
