<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="My Day & Today" class="p-0">
                <template #extra>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <!-- Productivity streak indicator if available -->
                        <div v-if="streakData" class="streak-badge" title="Your current completion streak">
                            <span class="streak-fire">🔥</span>
                            <span class="streak-count">{{ streakData.current_streak || 0 }} Day Streak</span>
                        </div>
                        <a-button type="primary" @click="openQuickAdd">
                            <PlusOutlined /> Quick Add
                        </a-button>
                    </div>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Today</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="today-container">
        <!-- Date / Productivity Summary Banner -->
        <div class="summary-banner">
            <div class="date-header">
                <CalendarOutlined class="calendar-icon" />
                <span class="formatted-date">{{ formattedDate }}</span>
            </div>
            <div class="productivity-stats">
                <div class="stat-item">
                    <span class="stat-num text-red">{{ overdueCount }}</span>
                    <span class="stat-label">Overdue</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num text-blue">{{ todayCount }}</span>
                    <span class="stat-label">Today</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num text-green">{{ completedCount }}</span>
                    <span class="stat-label">Completed Today</span>
                </div>
            </div>
        </div>

        <!-- Columns Grid -->
        <a-row :gutter="16" class="columns-row">
            <!-- 1. Overdue Column -->
            <a-col :xs="24" :lg="8">
                <div 
                    class="column-card overdue-col"
                    @dragover.prevent
                    @drop="onDrop($event, 'overdue')"
                >
                    <div class="column-header">
                        <span class="header-indicator border-red"></span>
                        <h3>Overdue</h3>
                        <a-tag color="error" class="task-count-tag">{{ overdueTasks.length }}</a-tag>
                    </div>
                    
                    <div class="tasks-list">
                        <div v-if="overdueTasks.length === 0" class="empty-state">
                            <CheckCircleOutlined style="font-size: 28px; color: #10b981;" />
                            <p>No overdue tasks. Awesome job!</p>
                        </div>
                        <div 
                            v-for="task in overdueTasks" 
                            :key="task.xid"
                            class="task-card draggable-task"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @click="viewTaskDetails(task)"
                        >
                            <div class="task-top">
                                <a-checkbox 
                                    :checked="task.status === 'completed'" 
                                    @click.stop
                                    @change="toggleComplete(task)"
                                />
                                <span class="task-title">{{ task.title }}</span>
                            </div>
                            <div class="task-meta">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                    {{ task.project.name }}
                                </a-tag>
                                <span class="due-date text-red">
                                    <ClockCircleOutlined /> {{ task.due_date }}
                                </span>
                                <a-tag :color="getPriorityColor(task.priority)" size="small">
                                    {{ task.priority }}
                                </a-tag>
                            </div>
                            <!-- Quick Reschedule Actions -->
                            <div class="quick-actions" @click.stop>
                                <a-tooltip title="Reschedule to Today">
                                    <a-button size="small" type="dashed" class="action-btn" @click="rescheduleTask(task, 'today')">
                                        Today
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip title="Reschedule to Tomorrow">
                                    <a-button size="small" type="dashed" class="action-btn" @click="rescheduleTask(task, 'tomorrow')">
                                        Tomorrow
                                    </a-button>
                                </a-tooltip>
                            </div>
                        </div>
                    </div>
                </div>
            </a-col>

            <!-- 2. Today Column -->
            <a-col :xs="24" :lg="8">
                <div 
                    class="column-card today-col"
                    @dragover.prevent
                    @drop="onDrop($event, 'today')"
                >
                    <div class="column-header">
                        <span class="header-indicator border-blue"></span>
                        <h3>Today</h3>
                        <a-tag color="processing" class="task-count-tag">{{ todayTasks.length }}</a-tag>
                    </div>

                    <div class="tasks-list">
                        <div v-if="todayTasks.length === 0" class="empty-state">
                            <InboxOutlined style="font-size: 28px; color: #9ca3af;" />
                            <p>No tasks for today. Add one or take a break!</p>
                        </div>
                        <div 
                            v-for="task in todayTasks" 
                            :key="task.xid"
                            class="task-card draggable-task"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @click="viewTaskDetails(task)"
                        >
                            <div class="task-top">
                                <a-checkbox 
                                    :checked="task.status === 'completed'" 
                                    @click.stop
                                    @change="toggleComplete(task)"
                                />
                                <span class="task-title">{{ task.title }}</span>
                            </div>
                            <div class="task-meta">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                    {{ task.project.name }}
                                </a-tag>
                                <span class="due-date">
                                    <ClockCircleOutlined /> Today {{ task.due_time ? task.due_time.substring(0, 5) : '' }}
                                </span>
                                <a-tag :color="getPriorityColor(task.priority)" size="small">
                                    {{ task.priority }}
                                </a-tag>
                            </div>
                            <div class="quick-actions" @click.stop>
                                <a-tooltip title="Reschedule to Tomorrow">
                                    <a-button size="small" type="dashed" class="action-btn" @click="rescheduleTask(task, 'tomorrow')">
                                        Tomorrow
                                    </a-button>
                                </a-tooltip>
                            </div>
                        </div>
                    </div>
                </div>
            </a-col>

            <!-- 3. Completed Today Column -->
            <a-col :xs="24" :lg="8">
                <div 
                    class="column-card completed-col"
                    @dragover.prevent
                    @drop="onDrop($event, 'completed')"
                >
                    <div class="column-header">
                        <span class="header-indicator border-green"></span>
                        <h3>Completed Today</h3>
                        <a-tag color="success" class="task-count-tag">{{ completedTasks.length }}</a-tag>
                    </div>

                    <div class="tasks-list">
                        <div v-if="completedTasks.length === 0" class="empty-state">
                            <CheckCircleOutlined style="font-size: 28px; color: #d1d5db;" />
                            <p>Drag tasks here or check them off to complete!</p>
                        </div>
                        <div 
                            v-for="task in completedTasks" 
                            :key="task.xid"
                            class="task-card completed-task-card draggable-task"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @click="viewTaskDetails(task)"
                        >
                            <div class="task-top">
                                <a-checkbox 
                                    :checked="true" 
                                    @click.stop
                                    @change="toggleComplete(task)"
                                />
                                <span class="task-title strikethrough">{{ task.title }}</span>
                            </div>
                            <div class="task-meta">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                    {{ task.project.name }}
                                </a-tag>
                                <span class="due-date text-green">
                                    <CheckOutlined /> Done
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a-col>
        </a-row>

        <!-- Task Details Modal -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchTodayTasks"
        />

        <!-- Create Task Drawer -->
        <CreateTaskDrawer 
            v-model:open="quickAddOpen" 
            :initial-start-date="todayDateStr"
            @saved="fetchTodayTasks"
        />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted, computed } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import { 
    PlusOutlined, 
    CalendarOutlined, 
    ClockCircleOutlined, 
    CheckCircleOutlined,
    InboxOutlined,
    CheckOutlined
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

import common from '../../../common/composable/common';

export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        CreateTaskDrawer,
        PlusOutlined,
        CalendarOutlined,
        ClockCircleOutlined,
        CheckCircleOutlined,
        InboxOutlined,
        CheckOutlined
    },
    setup() {
        const { user } = common();
        const overdueTasks = ref([]);
        const todayTasks = ref([]);
        const completedTasks = ref([]);
        const streakData = ref(null);
        
        const selectedTask = ref(null);
        const detailsVisible = ref(false);
        const quickAddOpen = ref(false);
        const todayDateStr = ref(dayjs().format('YYYY-MM-DD'));
        
        const draggedTask = ref(null);

        const formattedDate = computed(() => {
            return dayjs().format('dddd, MMM D');
        });

        const overdueCount = computed(() => overdueTasks.value.length);
        const todayCount = computed(() => todayTasks.value.length);
        const completedCount = computed(() => completedTasks.value.length);

        const fetchTodayTasks = async () => {
            try {
                const todayStr = dayjs().format('YYYY-MM-DD');
                const userXid = user.value?.xid;

                // 1. Fetch active tasks due today and overdue
                const activeRes = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: {
                        due_date_end: todayStr,
                        include_overdue: 'true',
                        parent_only: 'true',
                        x_assignee_id: userXid,
                        per_page: 100
                    }
                });

                // Separate active into Overdue and Today
                const allActive = activeRes.data || [];
                
                overdueTasks.value = allActive.filter(task => {
                    if (task.status === 'completed') return false;
                    if (!task.due_date) return false;
                    return dayjs(task.due_date).isBefore(todayStr, 'day');
                });

                todayTasks.value = allActive.filter(task => {
                    if (task.status === 'completed') return false;
                    if (!task.due_date) return true; // Inbox/Default to Today
                    return dayjs(task.due_date).isSame(todayStr, 'day');
                });

                // 2. Fetch tasks completed today
                const completedRes = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: {
                        completed_on: todayStr,
                        parent_only: 'true',
                        x_assignee_id: userXid,
                        per_page: 100
                    }
                });
                completedTasks.value = completedRes.data || [];

                // 3. Fetch achievements for current streak
                const achievementsRes = await axiosAdmin.get('/enterprise-tasks/achievements');
                streakData.value = achievementsRes.streak || null;
            } catch (error) {
                console.error(error);
                message.error('Failed to load tasks');
            }
        };

        const getPriorityColor = (priority) => {
            switch (priority) {
                case 'P1': return 'red';
                case 'P2': return 'orange';
                case 'P3': return 'blue';
                case 'P4': return 'default';
                default: return 'default';
            }
        };

        // Complete toggling
        const toggleComplete = async (task) => {
            try {
                const response = await axiosAdmin.post(`/enterprise-tasks/tasks/${task.xid}/toggle-complete`);
                if (response.success || response.status) {
                    message.success(task.status === 'completed' ? 'Task reopened' : 'Task completed!');
                    fetchTodayTasks();
                }
            } catch (error) {
                console.error(error);
                message.error('Failed to toggle completion status');
            }
        };

        // Reschedule
        const rescheduleTask = async (task, target) => {
            let targetDate = dayjs();
            if (target === 'tomorrow') {
                targetDate = dayjs().add(1, 'day');
            } else if (target === 'next_week') {
                targetDate = dayjs().add(7, 'day');
            }

            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, {
                    title: task.title,
                    status: task.status,
                    priority: task.priority,
                    due_date: targetDate.format('YYYY-MM-DD'),
                    x_project_id: task.project ? task.project.xid : null
                });
                message.success('Task rescheduled');
                fetchTodayTasks();
            } catch (error) {
                console.error(error);
                message.error('Failed to reschedule task');
            }
        };

        // Modal triggers
        const viewTaskDetails = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
        };

        const openQuickAdd = () => {
            window.__openQuickAdd?.(todayDateStr.value);
        };

        // Drag & Drop
        const onDragStart = (e, task) => {
            draggedTask.value = task;
            e.dataTransfer.setData('text/plain', task.xid);
        };

        const onDrop = async (e, column) => {
            const task = draggedTask.value;
            if (!task) return;

            try {
                if (column === 'completed') {
                    if (task.status !== 'completed') {
                        await toggleComplete(task);
                    }
                } else if (column === 'today') {
                    // Reschedule to today
                    const todayStr = dayjs().format('YYYY-MM-DD');
                    const updates = {
                        title: task.title,
                        priority: task.priority,
                        due_date: todayStr,
                        x_project_id: task.project ? task.project.xid : null
                    };
                    
                    if (task.status === 'completed') {
                        // Reopen task as pending
                        updates.status = 'pending';
                    } else {
                        updates.status = task.status;
                    }

                    await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, updates);
                    message.success('Task scheduled for Today');
                    fetchTodayTasks();
                } else if (column === 'overdue') {
                    // Reschedule to yesterday so it goes to overdue
                    const yesterdayStr = dayjs().subtract(1, 'day').format('YYYY-MM-DD');
                    const updates = {
                        title: task.title,
                        priority: task.priority,
                        due_date: yesterdayStr,
                        x_project_id: task.project ? task.project.xid : null
                    };

                    if (task.status === 'completed') {
                        updates.status = 'pending';
                    } else {
                        updates.status = task.status;
                    }

                    await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, updates);
                    message.success('Task rescheduled to Overdue');
                    fetchTodayTasks();
                }
            } catch (error) {
                console.error(error);
                message.error('Failed to move task');
            } finally {
                draggedTask.value = null;
            }
        };

        onMounted(() => {
            fetchTodayTasks();
            window.addEventListener('task-created', fetchTodayTasks);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetchTodayTasks);
        });

        return {
            overdueTasks,
            todayTasks,
            completedTasks,
            streakData,
            selectedTask,
            detailsVisible,
            quickAddOpen,
            todayDateStr,
            formattedDate,
            overdueCount,
            todayCount,
            completedCount,
            fetchTodayTasks,
            getPriorityColor,
            toggleComplete,
            rescheduleTask,
            viewTaskDetails,
            closeTaskDetails,
            openQuickAdd,
            onDragStart,
            onDrop
        };
    }
});
</script>

<style scoped>
.today-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.streak-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 13px;
    font-weight: 600;
    color: #d97706;
}

.summary-banner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.date-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.calendar-icon {
    font-size: 24px;
    color: #4f46e5;
}

.formatted-date {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.productivity-stats {
    display: flex;
    gap: 24px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-num {
    font-size: 18px;
    font-weight: 700;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
}

.text-red { color: #ef4444; }
.text-blue { color: #3b82f6; }
.text-green { color: #10b981; }

.columns-row {
    margin-top: 10px;
}

.column-card {
    background: #f1f5f9;
    border-radius: 12px;
    padding: 16px;
    min-height: 500px;
    display: flex;
    flex-direction: column;
    box-shadow: inset 0 1px 2px 0 rgba(0, 0, 0, 0.02);
}

.column-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.header-indicator {
    width: 6px;
    height: 20px;
    border-radius: 3px;
}

.border-red { background: #ef4444; }
.border-blue { background: #3b82f6; }
.border-green { background: #10b981; }

.column-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #334155;
    flex-grow: 1;
}

.task-count-tag {
    border-radius: 10px;
    padding: 0 8px;
}

.tasks-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex-grow: 1;
    overflow-y: auto;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 10px;
    text-align: center;
    color: #94a3b8;
}

.empty-state p {
    margin-top: 8px;
    font-size: 13px;
}

.task-card {
    background: white;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02);
}

.task-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border-color: #cbd5e1;
}

.completed-task-card {
    opacity: 0.75;
    background: #f8fafc;
}

.task-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
}

.task-title {
    font-size: 14px;
    font-weight: 500;
    color: #191f2c;
    line-height: 1.4;
    word-break: break-word;
}

.strikethrough {
    text-decoration: line-through;
    color: #94a3b8;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 11px;
    color: #64748b;
}

.due-date {
    display: flex;
    align-items: center;
    gap: 4px;
}

.quick-actions {
    display: flex;
    gap: 6px;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px dashed #f1f5f9;
}

.action-btn {
    font-size: 11px;
    height: 22px;
    padding: 0 8px;
    border-radius: 4px;
    color: #4f46e5;
}

.action-btn:hover {
    color: white;
    background: #4f46e5;
    border-color: #4f46e5;
}
</style>
