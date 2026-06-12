import { notification } from "ant-design-vue";
import { createRouter, createWebHistory } from "vue-router";
import axios from "axios";
import { find, includes, remove, replace } from "lodash-es";
import { useAuthStore } from "../store/authStore";

import AuthRoutes from "./auth";
import DashboardRoutes from "./dashboard";
import AppreciationsRoutes from "./appreciations";
import LeavesRoutes from "./leaves";
import HolidayRoutes from "./holiday";
import AttendanceRoutes from "./attendance";
import PayrollRoutes from "./payroll";
import AssetsRoutes from "./asset";
import FormRoutes from "./forms";
import NewsRoutes from "./news";
import HrmSettingRoutes from "./hrmSettings";
import UserRoutes from "./users";
import SettingRoutes from "./settings";
import CompanyPolicyRoutes from "./companyPolicies";
import AccountRoutes from "./accounts";
import StaffBarRoutes from "./staffBar";
import AssignedSurveyRoutes from "./assignedSurvey";
import Offboardings from "./offboardings";
import LetterHeadRoutes from "./letterHead";
import Reports from "./reports";
import ProjectRoutes from "./projects";
import TaskRoutes from "./tasks";
import { checkUserPermission } from "../../common/scripts/functions";

import FrontRoutes from "./front";

const appType = window.config.app_type;
const allActiveModules = window.config.modules;

const isAdminCompanySetupCorrect = (authStore) => {
    return true;
};

const isSuperAdminCompanySetupCorrect = (authStore) => {
    var appSetting = authStore.appSetting;

    if (appSetting.x_currency_id == null || appSetting.white_label_completed == false) {
        return false;
    }

    return true;
};

const basePath = window.config.path.startsWith('http') ? new URL(window.config.path).pathname : window.config.path;

const router = createRouter({
    history: createWebHistory(basePath),
    routes: [
        ...FrontRoutes,
        {
            path: "",
            redirect: "/admin/login",
        },
        ...AuthRoutes,
        ...DashboardRoutes,
        ...ProjectRoutes,
        ...TaskRoutes,
        ...AppreciationsRoutes,
        ...LeavesRoutes,
        ...HolidayRoutes,
        ...AttendanceRoutes,
        ...PayrollRoutes,
        ...HrmSettingRoutes,
        ...UserRoutes,
        ...SettingRoutes,
        ...AssetsRoutes,
        ...NewsRoutes,
        ...FormRoutes,
        ...CompanyPolicyRoutes,
        ...AccountRoutes,
        ...StaffBarRoutes,
        ...AssignedSurveyRoutes,
        ...Offboardings,
        ...LetterHeadRoutes,
        ...Reports,
    ],
    scrollBehavior: () => ({ left: 0, top: 0 }),
});

// Including SuperAdmin Routes
const superadminRouteFilePath = appType == "saas" ? "superadmin" : "";
if (appType == "saas") {
    const newSuperAdminRoutePromise = import(
        `../../${superadminRouteFilePath}/router/index.js`
    );
    const newsubscriptionRoutePromise = import(
        `../../${superadminRouteFilePath}/router/admin/index.js`
    );

    Promise.all([newSuperAdminRoutePromise, newsubscriptionRoutePromise]).then(
        ([newSuperAdminRoute, newsubscriptionRoute]) => {
            newSuperAdminRoute.default.forEach((route) =>
                router.addRoute(route)
            );
            newsubscriptionRoute.default.forEach((route) =>
                router.addRoute(route)
            );
        }
    );
}
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    // Bypass license checks and stop the loading spinner
    authStore.updateAppChecking(false);

    if (!to.name) {
        return next();
    }

    const routeParts = to.name.split('.');
    const isSaas = window.config && window.config.app_type === 'saas';
    const loginRoute = 'admin.login';
    const dashboardRoute = authStore.user && authStore.user.is_superadmin ? 'superadmin.dashboard.index' : 'admin.dashboard.index';

    // 1. Unauthenticated users trying to access protected routes
    if (to.meta.requireAuth && !authStore.isLoggedIn) {
        authStore.logoutAction();
        return next({ name: loginRoute });
    }

    // 2. Authenticated users trying to access guest-only routes (like login/register)
    if (to.meta.requireUnauth && authStore.isLoggedIn) {
        return next({ name: dashboardRoute });
    }

    // 3. Authenticated SuperAdmin
    if (routeParts[0] === 'superadmin' && authStore.user && authStore.user.is_superadmin) {
        if (!isSuperAdminCompanySetupCorrect(authStore) && routeParts[1] !== 'setup_app') {
            return next({ name: 'superadmin.setup_app.index' });
        }
        return next();
    }

    // 4. Authenticated Admin/User
    if (routeParts[0] === 'admin' && authStore.user && !authStore.user.is_superadmin) {
        if (!isAdminCompanySetupCorrect(authStore) && routeParts[1] !== 'setup_app') {
            return next({ name: 'admin.setup_app.index' });
        }
        
        if (to.name === loginRoute) {
            authStore.logoutAction();
            return next();
        }

        let permission = to.meta.permission;
        if (routeParts[1] === 'settings' && permission) {
            permission = replace(permission, '-', '_');
        }

        if (!permission || checkUserPermission(permission, authStore.user)) {
            return next();
        } else {
            return next({ name: dashboardRoute });
        }
    }

    // 5. Front or other routes
    next();
});

export default router;
