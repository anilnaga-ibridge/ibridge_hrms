export default [
    {
        path: "/admin/tasks/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "tasks",
                component: () =>
                    import("../views/tasks/index.vue"),
                name: "admin.tasks.index",
                meta: {
                    requireAuth: true,
                    menuParent: "tasks",
                    menuKey: (route) => "tasks",
                    permission: "admin",
                },
            },
            {
                path: "detailed-overview",
                component: () =>
                    import("../views/tasks/DetailedOverview.vue"),
                name: "admin.tasks.detailed_overview",
                meta: {
                    requireAuth: true,
                    menuParent: "tasks",
                    menuKey: (route) => "tasks",
                    permission: "admin",
                },
            },
        ],
    },
];
