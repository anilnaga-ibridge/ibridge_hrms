<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Enterprise Tasks Dashboard" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Dashboard</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div style="margin: 20px 16px;">
        <a-tabs v-model:activeKey="activeTab" type="card">
            <!-- PERSONAL DASHBOARD -->
            <a-tab-pane key="personal" tab="My Dashboard">
                <a-row :gutter="16">
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #e0f2fe; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="My Tasks" :value="personalData.my_tasks" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #fef3c7; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Today's Tasks" :value="personalData.today_tasks" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #fee2e2; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Overdue Tasks" :value="personalData.overdue_tasks" valueStyle="color: #dc2626;" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #dcfce7; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Productivity Score" :value="personalData.productivity_score" suffix="%" valueStyle="color: #16a34a;" />
                        </a-card>
                    </a-col>
                </a-row>

                <a-row :gutter="16" style="margin-top: 16px;">
                    <a-col :xs="24" :md="12">
                        <a-card title="My Weekly Progress" :bordered="false" style="border-radius: 12px; min-height: 300px;">
                            <div style="text-align: center; padding: 20px 0;">
                                <a-progress type="circle" :percent="personalData.weekly_progress ? Math.round(personalData.weekly_progress.percentage) : 0" :stroke-color="{'0%': '#10b981', '100%': '#3b82f6'}" />
                                <div style="margin-top: 20px;">
                                    <p>Completed This Week: <strong>{{ personalData.weekly_progress ? personalData.weekly_progress.completed : 0 }}</strong></p>
                                    <p>Total Scheduled This Week: <strong>{{ personalData.weekly_progress ? personalData.weekly_progress.total : 0 }}</strong></p>
                                </div>
                            </div>
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :md="12">
                        <a-card title="Time Tracking Status" :bordered="false" style="border-radius: 12px; min-height: 300px;">
                            <a-row :gutter="16" align="middle" style="margin-top: 40px;">
                                <a-col :span="12" style="text-align: center;">
                                    <a-statistic title="Today Logged Time" :value="personalData.today_logged_hours" suffix=" hrs" valueStyle="color: #3b82f6; font-size: 28px;" />
                                </a-col>
                                <a-col :span="12" style="text-align: center;">
                                    <a-progress type="dashboard" :percent="Math.min(100, Math.round((personalData.today_logged_hours / 8) * 100))" />
                                    <div style="margin-top: 8px; font-size: 12px; color: #6b7280;">Target: 8 Hours / Day</div>
                                </a-col>
                            </a-row>
                        </a-card>
                    </a-col>
                </a-row>
            </a-tab-pane>

            <!-- MANAGER DASHBOARD -->
            <a-tab-pane key="manager" tab="Team Dashboard">
                <a-row :gutter="16">
                    <a-col :xs="24" :sm="12" :md="8">
                        <a-card :bordered="false" style="background: #f3e8ff; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Pending Team Tasks" :value="managerData.pending_tasks" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="8">
                        <a-card :bordered="false" style="background: #d1fae5; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Completed Team Tasks" :value="managerData.completed_tasks" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="8">
                        <a-card :bordered="false" style="background: #fee2e2; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Delayed Team Tasks" :value="managerData.delayed_tasks" valueStyle="color: #dc2626;" />
                        </a-card>
                    </a-col>
                </a-row>

                <a-row :gutter="16" style="margin-top: 16px;">
                    <a-col :xs="24" :md="12">
                        <a-card title="Team Workload & Capacity" :bordered="false" style="border-radius: 12px;">
                            <div v-for="item in managerData.team_workload" :key="item.name" style="margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                    <strong>{{ item.name }}</strong>
                                    <span style="color: #6b7280; font-size: 12px;">
                                        {{ item.pending_tasks }} Tasks | Est: {{ item.allocated_hours }}h | Logged: {{ item.actual_hours }}h
                                    </span>
                                </div>
                                <a-progress :percent="Math.round(item.capacity_percentage)" status="active" :stroke-color="item.capacity_percentage > 90 ? '#ef4444' : '#10b981'" />
                            </div>
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :md="12">
                        <a-card title="Team Productivity" :bordered="false" style="border-radius: 12px; text-align: center; min-height: 250px;">
                            <a-statistic title="Average Productivity Score" :value="managerData.team_productivity" suffix="%" valueStyle="color: #8b5cf6; font-size: 32px;" />
                            <div style="margin-top: 30px;">
                                <a-progress :percent="Math.round(managerData.team_productivity)" status="active" stroke-color="#8b5cf6" />
                            </div>
                        </a-card>
                    </a-col>
                </a-row>
            </a-tab-pane>

            <!-- ADMIN DASHBOARD -->
            <a-tab-pane key="admin" tab="Admin Dashboard">
                <a-row :gutter="16">
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #eff6ff; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="All Projects" :value="adminData.total_projects" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #ecfdf5; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="All Tasks" :value="adminData.total_tasks" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #fff7ed; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Project Burn Rate" :value="adminData.burn_rate" suffix="%" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="6">
                        <a-card :bordered="false" style="background: #fdf2f8; border-radius: 12px; margin-bottom: 16px;">
                            <a-statistic title="Overdue Tasks" :value="adminData.overdue_tasks" valueStyle="color: #dc2626;" />
                        </a-card>
                    </a-col>
                </a-row>

                <a-row :gutter="16" style="margin-top: 16px;">
                    <a-col :xs="24" :md="12">
                        <a-card title="Department Productivity Metrics" :bordered="false" style="border-radius: 12px;">
                            <a-table :dataSource="adminData.department_metrics" :columns="deptColumns" :pagination="false" size="small" />
                        </a-card>
                    </a-col>
                    <a-col :xs="24" :md="12">
                        <a-card title="Employee Productivity Rankings" :bordered="false" style="border-radius: 12px;">
                            <a-table :dataSource="adminData.rankings" :columns="rankColumns" :pagination="false" size="small" />
                        </a-card>
                    </a-col>
                </a-row>
            </a-tab-pane>
        </a-tabs>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';


export default defineComponent({
    components: {
        AdminPageHeader
    },
    setup() {
        const activeTab = ref('personal');
        const personalData = ref({
            my_tasks: 0,
            today_tasks: 0,
            overdue_tasks: 0,
            completed_tasks: 0,
            upcoming_tasks: 0,
            productivity_score: 0,
            weekly_progress: { completed: 0, total: 0, percentage: 0 },
            today_logged_hours: 0
        });

        const managerData = ref({
            pending_tasks: 0,
            completed_tasks: 0,
            delayed_tasks: 0,
            team_workload: [],
            team_productivity: 0
        });

        const adminData = ref({
            total_projects: 0,
            total_tasks: 0,
            completed_tasks: 0,
            overdue_tasks: 0,
            burn_rate: 0,
            productivity: 0,
            department_metrics: [],
            rankings: []
        });

        const deptColumns = [
            { title: 'Department', dataIndex: 'name', key: 'name' },
            { title: 'Total Tasks', dataIndex: 'total_tasks', key: 'total_tasks' },
            { title: 'Completed', dataIndex: 'completed_tasks', key: 'completed_tasks' },
            { title: 'Productivity', dataIndex: 'productivity_pct', key: 'productivity_pct', customRender: ({ text }) => `${text}%` }
        ];

        const rankColumns = [
            { title: 'Rank', dataIndex: 'rank', key: 'rank', customRender: ({ index }) => index + 1 },
            { title: 'Employee', dataIndex: 'name', key: 'name' },
            { title: 'Department', dataIndex: 'department', key: 'department' },
            { title: 'Score', dataIndex: 'score', key: 'score', customRender: ({ text }) => `${text}%` }
        ];

        const fetchPersonalDashboard = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/dashboard/personal');
                personalData.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchManagerDashboard = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/dashboard/manager');
                managerData.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchAdminDashboard = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/dashboard/admin');
                adminData.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const refreshDashboard = () => {
            fetchPersonalDashboard();
            fetchManagerDashboard();
            fetchAdminDashboard();
        };

        onMounted(() => {
            refreshDashboard();
            window.addEventListener('task-created', refreshDashboard);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', refreshDashboard);
        });

        return {
            activeTab,
            personalData,
            managerData,
            adminData,
            deptColumns,
            rankColumns
        };
    }
});
</script>
