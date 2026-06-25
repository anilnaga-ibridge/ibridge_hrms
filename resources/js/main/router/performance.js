export default [
    {
        path: "/admin/performance",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "dashboard",
                component: () => import("../views/performance/admin/Index.vue"),
                name: "admin.performance.dashboard",
                meta: {
                    requireAuth: true,
                    menuParent: "performance",
                    menuKey: (route) => "performance_dashboard",
                    permission: "performance_view",
                },
            },
        ],
    },
    {
        path: "/admin/self",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "performance",
                component: () => import("../views/performance/self/Index.vue"),
                name: "admin.self.performance.index",
                meta: {
                    requireAuth: true,
                    menuParent: "performance_self",
                    menuKey: (route) => "my_performance",
                },
            },
        ],
    },
];
