export default [
    {
        path: "/admin/projects/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "projects",
                component: () =>
                    import("../views/projects/index.vue"),
                name: "admin.projects.index",
                meta: {
                    requireAuth: true,
                    menuParent: "projects",
                    menuKey: (route) => "projects",
                    permission: "admin",
                },
            },
        ],
    },
];
