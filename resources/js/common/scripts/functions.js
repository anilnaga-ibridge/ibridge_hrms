import { forEach } from "lodash-es";

const checkUserPermission = (permissionName, user) => {
    if (!user || !user.role) {
        return false;
    }
    // Case-insensitive admin role check
    const roleName = user.role.name ? user.role.name.toLowerCase() : '';
    var permissionAllowed = roleName === "admin" ? true : false;

    // If checking for 'superadmin' permission, allow any admin-role user
    if (permissionName === 'superadmin' && permissionAllowed) {
        return true;
    }

    forEach(user.role.perms, (permission) => {
        if (permission.name == permissionName) {
            permissionAllowed = true;
        }
    });

    return permissionAllowed;
};


const getUrlByAppType = (pathUrl) => {
    const appType = window.config.app_type;
    return appType == 'non-saas' ? pathUrl : `superadmin/${pathUrl}`;
}

export {
    checkUserPermission,
    getUrlByAppType,
};
