<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Quick Tasks" class="p-0">
                <template #extra>
                    <a-radio-group v-model:value="filterStatus" size="small" button-style="solid">
                        <a-radio-button value="active">Active</a-radio-button>
                        <a-radio-button value="completed">Completed</a-radio-button>
                    </a-radio-group>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Quick Tasks</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="employee-tasks-container">
        <div class="stats-bar">
            <div class="stat-pill">
                <span class="stat-num">{{ tasks.length }}</span>
                <span class="stat-label">Total Assigned</span>
            </div>
            <div class="stat-pill stat-pill--urgent">
                <span class="stat-num">{{ urgentCount }}</span>
                <span class="stat-label">P1 Urgent</span>
            </div>
            <div class="stat-pill stat-pill--overdue">
                <span class="stat-num">{{ overdueCount }}</span>
                <span class="stat-label">Overdue</span>
            </div>
            <div class="stat-pill stat-pill--today">
                <span class="stat-num">{{ dueTodayCount }}</span>
                <span class="stat-label">Due Today</span>
            </div>
        </div>

        <div v-if="loading" class="loading-state">
            <a-spin size="large" />
            <p>Loading your tasks...</p>
        </div>

        <div v-else-if="tasks.length === 0" class="empty-state">
            <div class="empty-icon">🎉</div>
            <h3>{{ filterStatus === 'completed' ? 'No completed tasks yet' : 'No tasks assigned to you yet!' }}</h3>
            <p>{{ filterStatus === 'completed' ? 'Complete some tasks to see them here.' : 'Your manager will assign tasks to you here. Check back soon!' }}</p>
        </div>

        <div v-else class="tasks-wrapper">
            <div v-for="group in groupedTasks" :key="group.priority" class="priority-group">
                <div class="group-header">
                    <div class="group-title">
                        <span class="priority-flag" :style="{ color: getPriorityColorHex(group.priority) }">🚩</span>
                        <span class="group-label">{{ group.label }}</span>
                        <a-tag :color="getPriorityColor(group.priority)" size="small">{{ group.tasks.length }}</a-tag>
                    </div>
                </div>
                <div class="task-list">
                    <div
                        v-for="task in group.tasks"
                        :key="task.xid"
                        class="task-row"
                        :class="{
                            'task-row--overdue': isOverdue(task),
                            'task-row--completed': task.status === 'completed'
                        }"
                        @click="openTask(task)"
                    >
                        <a-checkbox :checked="task.status === 'completed'" @click.stop @change="toggleComplete(task)" class="task-checkbox" />
                        <div class="task-info">
                            <div class="task-title" :class="{ 'task-title--done': task.status === 'completed' }">
                                {{ task.title }}
                            </div>
                            <div class="task-meta-row">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small" class="project-tag">
                                    #{{ task.project.name }}
                                </a-tag>
                                <span v-if="task.due_date" class="due-chip" :class="{ 'due-chip--overdue': isOverdue(task), 'due-chip--today': isDueToday(task) }">
                                    <CalendarOutlined /> Due: {{ formatDueDate(task.due_date) }}
                                </span>
                                <span v-if="task.deadline" class="deadline-chip" :class="{ 'deadline-chip--overdue': isDeadlineOverdue(task) }" style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #ef4444; font-weight: 600; margin-left: 8px;">
                                    <CalendarOutlined /> Deadline: {{ formatDueDate(task.deadline) }}
                                </span>
                            </div>
                        </div>
                        <div class="task-actions" @click.stop>
                            <a-tooltip title="View details">
                                <a-button type="text" size="small" @click.stop="openTask(task)">
                                    <template #icon><RightOutlined /></template>
                                </a-button>
                            </a-tooltip>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTask"
            @updated="fetchTasks"
        />
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import { CalendarOutlined, RightOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';
import common from '../../../common/composable/common';

export default defineComponent({
    components: { AdminPageHeader, TaskDetailsModal, CalendarOutlined, RightOutlined },
    setup() {
        const { user } = common();
        const tasks = ref([]);
        const loading = ref(false);
        const filterStatus = ref('active');
        const selectedTask = ref(null);
        const detailsVisible = ref(false);

        const fetchTasks = async () => {
            loading.value = true;
            try {
                const userXid = user.value?.xid;
                const params = { x_assignee_id: userXid, parent_only: 'true', per_page: 200 };
                if (filterStatus.value === 'completed') {
                    params.status = 'completed';
                } else {
                    params.exclude_status = 'completed';
                }
                const res = await window.axiosAdmin.get('/enterprise-tasks/tasks', { params });
                tasks.value = res.data || [];
            } catch (err) {
                console.error(err);
                message.error('Failed to load tasks');
            } finally {
                loading.value = false;
            }
        };

        const groupedTasks = computed(() => {
            const priorities = ['P1', 'P2', 'P3', 'P4'];
            const labels = { P1: 'Priority 1 — Urgent', P2: 'Priority 2 — High', P3: 'Priority 3 — Normal', P4: 'Priority 4 — Low' };
            return priorities.map(p => ({ priority: p, label: labels[p], tasks: tasks.value.filter(t => t.priority === p) })).filter(g => g.tasks.length > 0);
        });

        const urgentCount = computed(() => tasks.value.filter(t => t.priority === 'P1').length);
        const overdueCount = computed(() => tasks.value.filter(t => isOverdue(t)).length);
        const dueTodayCount = computed(() => tasks.value.filter(t => isDueToday(t)).length);

        const isOverdue = (task) => {
            if (!task.due_date || task.status === 'completed') return false;
            return dayjs(task.due_date).isBefore(dayjs(), 'day');
        };
        const isDeadlineOverdue = (task) => {
            if (!task.deadline || task.status === 'completed') return false;
            return dayjs(task.deadline).isBefore(dayjs(), 'day');
        };
        const isDueToday = (task) => task.due_date && dayjs(task.due_date).isSame(dayjs(), 'day');
        const formatDueDate = (date) => {
            const d = dayjs(date);
            if (d.isSame(dayjs(), 'day')) return 'Today';
            if (d.isSame(dayjs().add(1, 'day'), 'day')) return 'Tomorrow';
            if (d.isBefore(dayjs(), 'day')) return d.format('MMM D') + ' (overdue)';
            return d.format('MMM D');
        };
        const getPriorityColor = (p) => ({ P1: 'red', P2: 'orange', P3: 'blue', P4: 'default' }[p] || 'default');
        const getPriorityColorHex = (p) => ({ P1: '#ef4444', P2: '#f97316', P3: '#3b82f6', P4: '#9ca3af' }[p] || '#9ca3af');

        const toggleComplete = async (task) => {
            const newStatus = task.status === 'completed' ? 'pending' : 'completed';
            try {
                await window.axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, { ...task, status: newStatus });
                message.success(newStatus === 'completed' ? '✅ Task completed!' : 'Task marked as active');
                fetchTasks();
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (err) {
                message.error('Failed to update task');
            }
        };

        const openTask = (task) => { selectedTask.value = task; detailsVisible.value = true; };
        const closeTask = () => { selectedTask.value = null; detailsVisible.value = false; };

        watch(filterStatus, fetchTasks);
        onMounted(() => { fetchTasks(); window.addEventListener('task-created', fetchTasks); });
        onUnmounted(() => window.removeEventListener('task-created', fetchTasks));

        return {
            tasks, loading, filterStatus, groupedTasks,
            urgentCount, overdueCount, dueTodayCount,
            selectedTask, detailsVisible,
            fetchTasks, isOverdue, isDeadlineOverdue, isDueToday, formatDueDate,
            getPriorityColor, getPriorityColorHex,
            toggleComplete, openTask, closeTask
        };
    }
});
</script>

<style scoped>
.employee-tasks-container { padding: 20px 24px; background: #f8fafc; min-height: calc(100vh - 100px); max-width: 860px; margin: 0 auto; }
.stats-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.stat-pill { background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 20px; display: flex; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); flex: 1; min-width: 120px; }
.stat-pill--urgent { border-left: 4px solid #ef4444; }
.stat-pill--overdue { border-left: 4px solid #f97316; }
.stat-pill--today { border-left: 4px solid #4f46e5; }
.stat-num { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
.stat-label { font-size: 12px; color: #64748b; font-weight: 500; }
.loading-state, .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 20px; gap: 16px; color: #64748b; text-align: center; }
.empty-icon { font-size: 64px; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.empty-state p { color: #64748b; max-width: 380px; margin: 0; }
.priority-group { margin-bottom: 28px; }
.group-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #f1f5f9; }
.group-title { display: flex; align-items: center; gap: 10px; }
.group-label { font-size: 14px; font-weight: 700; color: #374151; }
.task-list { display: flex; flex-direction: column; gap: 6px; }
.task-row { display: flex; align-items: center; gap: 12px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; cursor: pointer; transition: all 0.18s ease; position: relative; }
.task-row:hover { border-color: #c7d2fe; box-shadow: 0 2px 8px rgba(79,70,229,0.08); transform: translateY(-1px); }
.task-row--overdue { border-left: 3px solid #ef4444; background: #fffafa; }
.task-row--completed { opacity: 0.55; background: #f9fafb; }
.task-info { flex: 1; min-width: 0; }
.task-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 5px; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.task-title--done { text-decoration: line-through; color: #94a3b8; font-weight: 400; }
.task-meta-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.due-chip { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #64748b; font-weight: 500; }
.due-chip--today { color: #4f46e5; font-weight: 700; }
.due-chip--overdue { color: #ef4444; font-weight: 700; }
.task-actions { flex-shrink: 0; opacity: 0; transition: opacity 0.15s; }
.task-row:hover .task-actions { opacity: 1; }
</style>
