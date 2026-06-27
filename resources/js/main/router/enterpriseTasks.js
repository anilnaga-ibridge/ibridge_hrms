export default [
    {
        path: "/admin/enterprise-tasks/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "",
                redirect: "/admin/enterprise-tasks/dashboard"
            },
            {
                path: "dashboard",
                component: () => import("../views/enterprise-tasks/Dashboard.vue"),
                name: "admin.enterprise_tasks.dashboard",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_dashboard",
                    permission: "admin",
                }
            },
            {
                path: "projects",
                component: () => import("../views/enterprise-tasks/Projects.vue"),
                name: "admin.enterprise_tasks.projects",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_projects",
                    permission: "admin",
                }
            },
            {
                path: "projects/:id",
                component: () => import("../views/enterprise-tasks/ProjectDetails.vue"),
                name: "admin.enterprise_tasks.project_details",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_kanban",
                    permission: "admin",
                }
            },
            {
                path: "kanban",
                component: () => import("../views/enterprise-tasks/Kanban.vue"),
                name: "admin.enterprise_tasks.kanban",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_kanban",
                    permission: "admin",
                }
            },
            {
                path: "list",
                component: () => import("../views/enterprise-tasks/TasksList.vue"),
                name: "admin.enterprise_tasks.list",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_list",
                    permission: "admin",
                }
            },
            {
                path: "calendar",
                component: () => import("../views/enterprise-tasks/Calendar.vue"),
                name: "admin.enterprise_tasks.calendar",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_calendar",
                    permission: "admin",
                }
            },
            {
                path: "reports",
                component: () => import("../views/enterprise-tasks/Reports.vue"),
                name: "admin.enterprise_tasks.reports",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_reports",
                    permission: "admin",
                }
            },
            {
                path: "filters-labels",
                component: () => import("../views/enterprise-tasks/FiltersLabels.vue"),
                name: "admin.enterprise_tasks.filters_labels",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_filters_labels",
                    permission: "admin",
                }
            },
            {
                path: "today",
                component: () => import("../views/enterprise-tasks/Today.vue"),
                name: "admin.enterprise_tasks.today",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_today",
                    permission: "admin",
                }
            },
            {
                path: "upcoming",
                component: () => import("../views/enterprise-tasks/Upcoming.vue"),
                name: "admin.enterprise_tasks.upcoming",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_upcoming",
                    permission: "admin",
                }
            },
            {
                path: "favorites",
                component: () => import("../views/enterprise-tasks/Favorites.vue"),
                name: "admin.enterprise_tasks.favorites",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_favorites",
                    permission: "admin",
                }
            },
            {
                path: "my-day",
                component: () => import("../views/enterprise-tasks/MyDay.vue"),
                name: "admin.enterprise_tasks.my_day",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_my_day",
                    permission: "admin",
                }
            },
            {
                path: "notification-settings",
                component: () => import("../views/enterprise-tasks/NotificationSettings.vue"),
                name: "admin.enterprise_tasks.notification_settings",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_notification_settings",
                    permission: "admin",
                }
            },
            {
                path: "achievements",
                component: () => import("../views/enterprise-tasks/Achievements.vue"),
                name: "admin.enterprise_tasks.achievements",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_achievements",
                    permission: "admin",
                }
            },
            {
                path: "ai-assistant",
                component: () => import("../views/enterprise-tasks/AIAssistant.vue"),
                name: "admin.enterprise_tasks.ai_assistant",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_ai_assistant",
                    permission: "admin",
                }
            },
            {
                path: "gantt",
                component: () => import("../views/enterprise-tasks/GanttChart.vue"),
                name: "admin.enterprise_tasks.gantt",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_gantt",
                    permission: "admin",
                }
            },
            {
                path: "observability",
                component: () => import("../views/enterprise-tasks/ObservabilityDashboard.vue"),
                name: "admin.enterprise_tasks.observability",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_observability",
                    permission: "admin",
                }
            },
            {
                path: "my-assigned-tasks",
                component: () => import("../views/enterprise-tasks/EmployeeTasks.vue"),
                name: "admin.enterprise_tasks.employee_tasks",
                meta: {
                    requireAuth: true,
                    menuParent: "enterprise_tasks",
                    menuKey: (route) => "enterprise_tasks_employee_tasks",
                    // No permission required — accessible to all authenticated users
                }
            }
        ]
    }
];

