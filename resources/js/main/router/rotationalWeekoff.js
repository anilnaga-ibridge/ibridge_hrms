export default [
    {
        path: "/admin/rotational-weekoff",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "teams",
                component: () => import("../views/rotational-weekoff/teams/index.vue"),
                name: "admin.rotational-weekoff.teams",
                meta: { requireAuth: true, menuParent: "rotational-weekoff", menuKey: () => "rotational-weekoff-teams", permission: "rotational_teams_view" },
            },
            {
                path: "teams/:xid/members",
                component: () => import("../views/rotational-weekoff/teams/members.vue"),
                name: "admin.rotational-weekoff.teams.members",
                meta: { requireAuth: true, menuParent: "rotational-weekoff", menuKey: () => "rotational-weekoff-teams", permission: "rotational_teams_view" },
            },
            {
                path: "schedule",
                component: () => import("../views/rotational-weekoff/schedule/index.vue"),
                name: "admin.rotational-weekoff.schedule",
                meta: { requireAuth: true, menuParent: "rotational-weekoff", menuKey: () => "rotational-weekoff-schedule", permission: "rotational_teams_view" },
            },
        ],
    },
];
