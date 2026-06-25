
import CommonSettings from "./settings";

// This is for admin panel for the sass
const appType = window.config.app_type;

const allRoutes = appType == 'saas' ?
    [
        {
            path: 'translations',
            component: () => import("../../views/settings/translations/index.vue"),
            name: 'admin.settings.translations.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: (route) => "translations",
                permission: "translations_view",
            },
        },
        {
            path: 'modules',
            component: () => import("../../views/settings/modules/index.vue"),
            name: 'admin.settings.modules.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: (route) => "modules",
                permission: "modules_view",
            },
        },
        {
            path: 'storage',
            component: () => import("../../views/settings/storage/Edit.vue"),
            name: 'admin.settings.storage.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: (route) => "storage_settings",
                permission: "storage_edit",
            },
        },
        {
            path: 'email',
            component:() => import("../../views/settings/mail-settings/Edit.vue"),
            name: 'admin.settings.email.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: route => "email_settings",
                permission: "email_edit"
            }
        },
        {
            path: 'database-backup',
            component: () => import("../../views/settings/database-backup/index.vue"),
            name: 'admin.settings.database_backup.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: (route) => "database_backup",
                permission: "database_backup",
            },
        },
        {
            path: 'update-app',
            component: () => import("../../views/settings/update-app/index.vue"),
            name: 'admin.settings.update_app.index',
            meta: {
                requireAuth: true,
                menuParent: "settings",
                menuKey: (route) => "update_app",
                permission: "update_app",
            },
        },
    ] : [...CommonSettings];

export default allRoutes;
