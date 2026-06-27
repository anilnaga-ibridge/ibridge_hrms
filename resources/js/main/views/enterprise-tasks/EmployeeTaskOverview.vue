<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="My Task Overview" class="p-0">
                <template #extra>
                    <a-button @click="navigateBack"><LeftOutlined /> Back to Tasks</a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.enterprise_tasks.employee_tasks' }">Quick Tasks</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Task Overview</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="overview-container">
        <div v-if="loading" style="text-align: center; padding: 80px 0;">
            <a-spin size="large" />
            <p style="margin-top: 16px; color: #64748b;">Loading overview...</p>
        </div>

        <template v-else>
            <div class="stats-row">
                <div class="stat-card stat-card--total">
                    <div class="stat-num">{{ tasks.length }}</div>
                    <div class="stat-label">Total Assigned</div>
                </div>
                <div class="stat-card stat-card--completed">
                    <div class="stat-num">{{ completedTasks.length }}</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card stat-card--pending">
                    <div class="stat-num">{{ pendingTasks.length }}</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card stat-card--overdue">
                    <div class="stat-num">{{ overdueTasks.length }}</div>
                    <div class="stat-label">Overdue</div>
                </div>
                <div class="stat-card stat-card--time">
                    <div class="stat-num">{{ totalTimeSpent }}</div>
                    <div class="stat-label">Total Hours Logged</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <a-tag color="green">Completed ({{ completedTasks.length }})</a-tag>
                </div>
                <div v-if="completedTasks.length === 0" class="empty-section">No completed tasks</div>
                <div v-else class="task-list">
                    <div v-for="task in completedTasks" :key="task.xid" class="task-row">
                        <div class="task-main">
                            <div class="task-title">{{ task.title }}</div>
                            <div class="task-meta">
                                <a-tag color="green">Completed</a-tag>
                                <span v-if="task.actual_hours" class="time-badge"><ClockCircleOutlined /> {{ task.actual_hours }}h spent</span>
                                <span v-if="task.due_date" class="due-badge" :class="completedOnTime(task) ? 'due-ok' : 'due-late'">
                                    <CalendarOutlined /> Due: {{ formatDate(task.due_date) }}
                                    {{ completedOnTime(task) ? '(On time)' : '(Overdue)' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <a-tag color="orange">In Progress / Pending ({{ pendingTasks.length }})</a-tag>
                </div>
                <div v-if="pendingTasks.length === 0" class="empty-section">No pending tasks</div>
                <div v-else class="task-list">
                    <div v-for="task in pendingTasks" :key="task.xid" class="task-row">
                        <div class="task-main">
                            <div class="task-title">{{ task.title }}</div>
                            <div class="task-meta">
                                <a-tag :color="statusColor(task.status)">{{ task.status }}</a-tag>
                                <a-tag :color="priorityColor(task.priority)">{{ task.priority }}</a-tag>
                                <span v-if="task.actual_hours" class="time-badge"><ClockCircleOutlined /> {{ task.actual_hours }}h spent</span>
                                <span v-if="task.due_date" class="due-badge" :class="isOverdue(task) ? 'due-late' : 'due-ok'">
                                    <CalendarOutlined /> Due: {{ formatDate(task.due_date) }}
                                    <span v-if="isOverdue(task)" style="color:#ef4444;font-weight:700;">(Overdue)</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <a-tag color="red">Overdue ({{ overdueTasks.length }})</a-tag>
                </div>
                <div v-if="overdueTasks.length === 0" class="empty-section">No overdue tasks</div>
                <div v-else class="task-list">
                    <div v-for="task in overdueTasks" :key="task.xid" class="task-row">
                        <div class="task-main">
                            <div class="task-title">{{ task.title }}</div>
                            <div class="task-meta">
                                <a-tag :color="statusColor(task.status)">{{ task.status }}</a-tag>
                                <a-tag :color="priorityColor(task.priority)">{{ task.priority }}</a-tag>
                                <span v-if="task.actual_hours" class="time-badge"><ClockCircleOutlined /> {{ task.actual_hours }}h spent</span>
                                <span class="due-badge due-late">
                                    <CalendarOutlined /> Was due: {{ formatDate(task.due_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { CalendarOutlined, ClockCircleOutlined, LeftOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';
import common from '../../../common/composable/common';

export default defineComponent({
    components: { AdminPageHeader, CalendarOutlined, ClockCircleOutlined, LeftOutlined },
    setup() {
        const { user } = common();
        const router = useRouter();
        const tasks = ref([]);
        const loading = ref(true);

        const fetchTasks = async () => {
            loading.value = true;
            try {
                const userXid = user.value?.xid;
                const res = await window.axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: { x_assignee_id: userXid, per_page: 500 }
                });
                tasks.value = res.data || [];
            } catch (err) {
                console.error(err);
                message.error('Failed to load tasks');
            } finally {
                loading.value = false;
            }
        };

        const completedTasks = computed(() =>
            tasks.value.filter(t => t.status === 'completed')
        );

        const pendingTasks = computed(() =>
            tasks.value.filter(t => t.status !== 'completed' && !isOverdue(t))
        );

        const overdueTasks = computed(() =>
            tasks.value.filter(t => t.status !== 'completed' && isOverdue(t))
        );

        const isOverdue = (task) => {
            if (!task.due_date || task.status === 'completed') return false;
            return dayjs(task.due_date).isBefore(dayjs(), 'day');
        };

        const completedOnTime = (task) => {
            if (!task.due_date) return true;
            return dayjs(task.due_date).isAfter(dayjs(task.updated_at)) || dayjs(task.due_date).isSame(dayjs(task.updated_at), 'day');
        };

        const totalTimeSpent = computed(() => {
            const total = tasks.value.reduce((sum, t) => sum + (parseFloat(t.actual_hours) || 0), 0);
            return total.toFixed(1) + 'h';
        });

        const formatDate = (date) => date ? dayjs(date).format('MMM D, YYYY') : '—';

        const statusColor = (s) => ({
            pending: 'default', in_progress: 'blue', under_review: 'purple',
            testing: 'orange', completed: 'green', cancelled: 'red', on_hold: 'gold'
        }[s] || 'default');

        const priorityColor = (p) => ({
            P1: 'red', P2: 'orange', P3: 'blue', P4: 'default'
        }[p] || 'default');

        const navigateBack = () => router.push({ name: 'admin.enterprise_tasks.employee_tasks' });

        onMounted(fetchTasks);

        return {
            tasks, loading, completedTasks, pendingTasks, overdueTasks,
            totalTimeSpent, isOverdue, completedOnTime, formatDate,
            statusColor, priorityColor, navigateBack
        };
    }
});
</script>

<style scoped>
.overview-container { padding: 20px 24px; background: #f8fafc; min-height: calc(100vh - 100px); max-width: 960px; margin: 0 auto; }
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 28px; }
.stat-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 16px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.stat-card--total { border-left: 4px solid #6366f1; }
.stat-card--completed { border-left: 4px solid #22c55e; }
.stat-card--pending { border-left: 4px solid #f59e0b; }
.stat-card--overdue { border-left: 4px solid #ef4444; }
.stat-card--time { border-left: 4px solid #8b5cf6; }
.stat-num { font-size: 28px; font-weight: 800; color: #1e293b; line-height: 1.2; }
.stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.section { margin-bottom: 32px; }
.section-title { margin-bottom: 12px; }
.empty-section { color: #94a3b8; font-style: italic; padding: 24px; text-align: center; background: white; border-radius: 8px; border: 1px dashed #e2e8f0; }
.task-list { display: flex; flex-direction: column; gap: 6px; }
.task-row { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; }
.task-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
.task-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.time-badge { font-size: 12px; color: #8b5cf6; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
.due-badge { font-size: 12px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
.due-ok { color: #22c55e; }
.due-late { color: #ef4444; }
</style>