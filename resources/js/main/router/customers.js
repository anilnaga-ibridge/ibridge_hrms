export default [
    {
        path: "/admin/customers/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "customers",
                component: () =>
                    import("../views/customers/index.vue"),
                name: "admin.customers.index",
                meta: {
                    requireAuth: true,
                    menuParent: "customers",
                    menuKey: (route) => "customers",
                    permission: "admin",
                },
            },
        ],
    },
];
