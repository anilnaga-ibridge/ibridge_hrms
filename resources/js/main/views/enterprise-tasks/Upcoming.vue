<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Upcoming Schedule" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openQuickAdd(null)">
                        <PlusOutlined /> Quick Add
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Upcoming</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="upcoming-container">
        <!-- Horizontal navigation or overview if needed, otherwise go straight to the day lists -->
        <div class="upcoming-days-list">
            <!-- Overdue Section if any exist -->
            <div 
                v-if="overdueTasks.length > 0" 
                class="day-group overdue-group"
                @dragover.prevent
                @drop="onDrop($event, 'overdue')"
            >
                <div class="day-header header-overdue">
                    <span class="day-title">Overdue</span>
                    <a-tag color="error" class="task-count">{{ overdueTasks.length }}</a-tag>
                </div>
                <div class="day-tasks-container">
                    <div 
                        v-for="task in overdueTasks" 
                        :key="task.xid"
                        class="task-card draggable-task border-red"
                        draggable="true"
                        @dragstart="onDragStart($event, task)"
                        @click="viewTaskDetails(task)"
                    >
                        <div class="task-top" style="padding-right: 70px;">
                            <a-checkbox :checked="false" @click.stop @change="toggleComplete(task)" />
                            <span class="task-title">{{ task.title }}</span>
                        </div>
                        <div class="task-meta">
                            <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                {{ task.project.name }}
                            </a-tag>
                            <span class="due-text text-red">{{ task.due_date }}</span>
                            <a-tag :color="getPriorityColor(task.priority)" size="small">{{ task.priority }}</a-tag>
                        </div>

                        <!-- Hover Action Buttons -->
                        <div class="task-action-buttons" style="display: none; align-items: center; gap: 8px; position: absolute; right: 12px; top: 12px;" @click.stop>
                            <a-tooltip title="Edit task">
                                <EditOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-tooltip title="Comment">
                                <MessageOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-dropdown :trigger="['click']" overlayClassName="todoist-context-menu">
                                <MoreOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop />
                                <template #overlay>
                                    <a-menu>
                                        <div class="menu-section-header">Date</div>
                                        <div class="date-quick-actions">
                                            <div class="date-icon-btn today-btn" title="Today" @click="setTaskDate(task, 'today')">
                                                <span class="date-day-num">{{ currentDayNumber }}</span>
                                            </div>
                                            <div class="date-icon-btn tomorrow-btn" title="Tomorrow" @click="setTaskDate(task, 'tomorrow')">☀️</div>
                                            <div class="date-icon-btn weekend-btn" title="This weekend" @click="setTaskDate(task, 'weekend')">🛋️</div>
                                            <div class="date-icon-btn nextweek-btn" title="Next week" @click="setTaskDate(task, 'next_week')">➡️</div>
                                            <div class="date-icon-btn custom-picker-btn" title="Custom date">
                                                <a-date-picker :bordered="false" size="small" style="width: 32px; overflow: hidden;" @change="(d, ds) => handleCustomDateChange(task, ds)" />
                                            </div>
                                        </div>
                                        <a-menu-divider />
                                        <div class="menu-section-header">Priority</div>
                                        <div class="priority-quick-actions">
                                            <span class="priority-flag flag-p1" title="Priority 1" @click="setTaskPriority(task, 'P1')">🚩</span>
                                            <span class="priority-flag flag-p2" title="Priority 2" @click="setTaskPriority(task, 'P2')">🚩</span>
                                            <span class="priority-flag flag-p3" title="Priority 3" @click="setTaskPriority(task, 'P3')">🚩</span>
                                            <span class="priority-flag flag-p4" title="Priority 4" @click="setTaskPriority(task, 'P4')">🚩</span>
                                        </div>
                                        <a-menu-divider />
                                        <a-menu-item key="edit" @click="viewTaskDetails(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><EditOutlined /></span>
                                                <span class="menu-title">Edit task</span>
                                                <span class="menu-shortcut">⌘ E</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="duplicate" @click="duplicateTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><FolderOutlined /></span>
                                                <span class="menu-title">Duplicate</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="copy-link" @click="copyLinkToTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><LinkOutlined /></span>
                                                <span class="menu-title">Copy link to task</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="open-new-window" @click="openInNewWindow(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><PlusOutlined /></span>
                                                <span class="menu-title">Open in new window</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-divider />
                                        <a-menu-item key="delete" danger @click="confirmDeleteTask(task)">
                                            <span class="menu-item-content" style="color: #ef4444;">
                                                <span class="menu-icon" style="color: #ef4444;"><PlusOutlined /></span>
                                                <span class="menu-title">Delete task</span>
                                            </span>
                                        </a-menu-item>
                                    </a-menu>
                                </template>
                            </a-dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next 7 Days -->
            <div 
                v-for="day in upcomingDays" 
                :key="day.dateStr"
                class="day-group"
                :class="{ 'is-today': day.isToday }"
                @dragover.prevent
                @drop="onDrop($event, day.dateStr)"
            >
                <div class="day-header">
                    <div style="display: flex; align-items: baseline; gap: 8px;">
                        <span class="day-name">{{ day.displayName }}</span>
                        <span class="day-date">{{ day.formattedDate }}</span>
                    </div>
                    <div class="day-actions">
                        <a-tag v-if="day.tasks.length > 0" color="blue" class="task-count">{{ day.tasks.length }}</a-tag>
                        <a-button type="link" size="small" class="add-day-task-btn" @click="openQuickAdd(day.dateStr)">
                            <PlusOutlined /> Add Task
                        </a-button>
                    </div>
                </div>

                <div class="day-tasks-container">
                    <div v-if="day.tasks.length === 0" class="empty-day-placeholder">
                        <span>No tasks scheduled.</span>
                    </div>
                    <div 
                        v-for="task in day.tasks" 
                        :key="task.xid"
                        class="task-card draggable-task"
                        draggable="true"
                        @dragstart="onDragStart($event, task)"
                        @click="viewTaskDetails(task)"
                    >
                        <div class="task-top" style="padding-right: 70px;">
                            <a-checkbox :checked="false" @click.stop @change="toggleComplete(task)" />
                            <span class="task-title">{{ task.title }}</span>
                        </div>
                        <div class="task-meta">
                            <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                {{ task.project.name }}
                            </a-tag>
                            <span v-if="task.due_time" class="time-text">🕒 {{ task.due_time.substring(0, 5) }}</span>
                            <a-tag :color="getPriorityColor(task.priority)" size="small">{{ task.priority }}</a-tag>
                        </div>

                        <!-- Hover Action Buttons -->
                        <div class="task-action-buttons" style="display: none; align-items: center; gap: 8px; position: absolute; right: 12px; top: 12px;" @click.stop>
                            <a-tooltip title="Edit task">
                                <EditOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-tooltip title="Comment">
                                <MessageOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-dropdown :trigger="['click']" overlayClassName="todoist-context-menu">
                                <MoreOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop />
                                <template #overlay>
                                    <a-menu>
                                        <div class="menu-section-header">Date</div>
                                        <div class="date-quick-actions">
                                            <div class="date-icon-btn today-btn" title="Today" @click="setTaskDate(task, 'today')">
                                                <span class="date-day-num">{{ currentDayNumber }}</span>
                                            </div>
                                            <div class="date-icon-btn tomorrow-btn" title="Tomorrow" @click="setTaskDate(task, 'tomorrow')">☀️</div>
                                            <div class="date-icon-btn weekend-btn" title="This weekend" @click="setTaskDate(task, 'weekend')">🛋️</div>
                                            <div class="date-icon-btn nextweek-btn" title="Next week" @click="setTaskDate(task, 'next_week')">➡️</div>
                                            <div class="date-icon-btn custom-picker-btn" title="Custom date">
                                                <a-date-picker :bordered="false" size="small" style="width: 32px; overflow: hidden;" @change="(d, ds) => handleCustomDateChange(task, ds)" />
                                            </div>
                                        </div>
                                        <a-menu-divider />
                                        <div class="menu-section-header">Priority</div>
                                        <div class="priority-quick-actions">
                                            <span class="priority-flag flag-p1" title="Priority 1" @click="setTaskPriority(task, 'P1')">🚩</span>
                                            <span class="priority-flag flag-p2" title="Priority 2" @click="setTaskPriority(task, 'P2')">🚩</span>
                                            <span class="priority-flag flag-p3" title="Priority 3" @click="setTaskPriority(task, 'P3')">🚩</span>
                                            <span class="priority-flag flag-p4" title="Priority 4" @click="setTaskPriority(task, 'P4')">🚩</span>
                                        </div>
                                        <a-menu-divider />
                                        <a-menu-item key="edit" @click="viewTaskDetails(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><EditOutlined /></span>
                                                <span class="menu-title">Edit task</span>
                                                <span class="menu-shortcut">⌘ E</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="duplicate" @click="duplicateTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><FolderOutlined /></span>
                                                <span class="menu-title">Duplicate</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="copy-link" @click="copyLinkToTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><LinkOutlined /></span>
                                                <span class="menu-title">Copy link to task</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="open-new-window" @click="openInNewWindow(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><PlusOutlined /></span>
                                                <span class="menu-title">Open in new window</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-divider />
                                        <a-menu-item key="delete" danger @click="confirmDeleteTask(task)">
                                            <span class="menu-item-content" style="color: #ef4444;">
                                                <span class="menu-icon" style="color: #ef4444;"><PlusOutlined /></span>
                                                <span class="menu-title">Delete task</span>
                                            </span>
                                        </a-menu-item>
                                    </a-menu>
                                </template>
                            </a-dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Later Section (tasks after 7 days) -->
            <div 
                class="day-group later-group"
                @dragover.prevent
                @drop="onDrop($event, 'later')"
            >
                <div class="day-header">
                    <span class="day-name">Later / Next 30 Days</span>
                    <a-tag v-if="laterTasks.length > 0" color="purple" class="task-count">{{ laterTasks.length }}</a-tag>
                </div>
                <div class="day-tasks-container">
                    <div v-if="laterTasks.length === 0" class="empty-day-placeholder">
                        <span>No later tasks.</span>
                    </div>
                    <div 
                        v-for="task in laterTasks" 
                        :key="task.xid"
                        class="task-card draggable-task"
                        draggable="true"
                        @dragstart="onDragStart($event, task)"
                        @click="viewTaskDetails(task)"
                    >
                        <div class="task-top" style="padding-right: 70px;">
                            <a-checkbox :checked="false" @click.stop @change="toggleComplete(task)" />
                            <span class="task-title">{{ task.title }}</span>
                        </div>
                        <div class="task-meta">
                            <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                {{ task.project.name }}
                            </a-tag>
                            <span class="due-text">{{ task.due_date }}</span>
                            <a-tag :color="getPriorityColor(task.priority)" size="small">{{ task.priority }}</a-tag>
                        </div>

                        <!-- Hover Action Buttons -->
                        <div class="task-action-buttons" style="display: none; align-items: center; gap: 8px; position: absolute; right: 12px; top: 12px;" @click.stop>
                            <a-tooltip title="Edit task">
                                <EditOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-tooltip title="Comment">
                                <MessageOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                            </a-tooltip>
                            <a-dropdown :trigger="['click']" overlayClassName="todoist-context-menu">
                                <MoreOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop />
                                <template #overlay>
                                    <a-menu>
                                        <div class="menu-section-header">Date</div>
                                        <div class="date-quick-actions">
                                            <div class="date-icon-btn today-btn" title="Today" @click="setTaskDate(task, 'today')">
                                                <span class="date-day-num">{{ currentDayNumber }}</span>
                                            </div>
                                            <div class="date-icon-btn tomorrow-btn" title="Tomorrow" @click="setTaskDate(task, 'tomorrow')">☀️</div>
                                            <div class="date-icon-btn weekend-btn" title="This weekend" @click="setTaskDate(task, 'weekend')">🛋️</div>
                                            <div class="date-icon-btn nextweek-btn" title="Next week" @click="setTaskDate(task, 'next_week')">➡️</div>
                                            <div class="date-icon-btn custom-picker-btn" title="Custom date">
                                                <a-date-picker :bordered="false" size="small" style="width: 32px; overflow: hidden;" @change="(d, ds) => handleCustomDateChange(task, ds)" />
                                            </div>
                                        </div>
                                        <a-menu-divider />
                                        <div class="menu-section-header">Priority</div>
                                        <div class="priority-quick-actions">
                                            <span class="priority-flag flag-p1" title="Priority 1" @click="setTaskPriority(task, 'P1')">🚩</span>
                                            <span class="priority-flag flag-p2" title="Priority 2" @click="setTaskPriority(task, 'P2')">🚩</span>
                                            <span class="priority-flag flag-p3" title="Priority 3" @click="setTaskPriority(task, 'P3')">🚩</span>
                                            <span class="priority-flag flag-p4" title="Priority 4" @click="setTaskPriority(task, 'P4')">🚩</span>
                                        </div>
                                        <a-menu-divider />
                                        <a-menu-item key="edit" @click="viewTaskDetails(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><EditOutlined /></span>
                                                <span class="menu-title">Edit task</span>
                                                <span class="menu-shortcut">⌘ E</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="duplicate" @click="duplicateTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><FolderOutlined /></span>
                                                <span class="menu-title">Duplicate</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="copy-link" @click="copyLinkToTask(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><LinkOutlined /></span>
                                                <span class="menu-title">Copy link to task</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-item key="open-new-window" @click="openInNewWindow(task)">
                                            <span class="menu-item-content">
                                                <span class="menu-icon"><PlusOutlined /></span>
                                                <span class="menu-title">Open in new window</span>
                                            </span>
                                        </a-menu-item>
                                        <a-menu-divider />
                                        <a-menu-item key="delete" danger @click="confirmDeleteTask(task)">
                                            <span class="menu-item-content" style="color: #ef4444;">
                                                <span class="menu-icon" style="color: #ef4444;"><PlusOutlined /></span>
                                                <span class="menu-title">Delete task</span>
                                            </span>
                                        </a-menu-item>
                                    </a-menu>
                                </template>
                            </a-dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Details Modal -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchUpcomingTasks"
        />

        <!-- Create Task Drawer -->
        <CreateTaskDrawer 
            v-model:open="quickAddOpen" 
            :initial-start-date="selectedQuickAddDate"
            @saved="fetchUpcomingTasks"
        />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import { 
    PlusOutlined, MoreOutlined, MessageOutlined, EditOutlined, 
    CalendarOutlined, FolderOutlined, LinkOutlined 
} from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import dayjs from 'dayjs';
import common from '../../../common/composable/common';

export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        CreateTaskDrawer,
        PlusOutlined,
        MoreOutlined,
        MessageOutlined,
        EditOutlined,
        CalendarOutlined,
        FolderOutlined,
        LinkOutlined
    },
    setup() {
        const { user } = common();
        const overdueTasks = ref([]);
        const upcomingDays = ref([]);
        const laterTasks = ref([]);

        const selectedTask = ref(null);
        const detailsVisible = ref(false);
        const quickAddOpen = ref(false);
        const selectedQuickAddDate = ref(null);

        const draggedTask = ref(null);

        // Generate the structure for next 7 days
        const setupDays = () => {
            const days = [];
            for (let i = 0; i < 7; i++) {
                const date = dayjs().add(i, 'day');
                let displayName = date.format('dddd');
                if (i === 0) displayName = 'Today';
                if (i === 1) displayName = 'Tomorrow';

                days.push({
                    dateStr: date.format('YYYY-MM-DD'),
                    displayName,
                    formattedDate: date.format('MMM D'),
                    isToday: i === 0,
                    tasks: []
                });
            }
            upcomingDays.value = days;
        };

        const fetchUpcomingTasks = async () => {
            try {
                setupDays();
                const todayStr = dayjs().format('YYYY-MM-DD');
                const userXid = user.value?.xid;
                
                // Fetch tasks from today onwards, plus overdue
                const response = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: {
                        due_date_start: todayStr,
                        parent_only: 'true',
                        x_assignee_id: userXid,
                        per_page: 150
                    }
                });

                // Also fetch overdue tasks separately
                const overdueRes = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: {
                        due_date_end: dayjs().subtract(1, 'day').format('YYYY-MM-DD'),
                        status: 'pending,in_progress,under_review,testing,on_hold', // wait, backend is single status, so get all overdue and filter on front
                        parent_only: 'true',
                        x_assignee_id: userXid,
                        per_page: 50
                    }
                });

                // Set Overdue Tasks
                overdueTasks.value = (overdueRes.data || []).filter(t => t.status !== 'completed');

                const allTasks = response.data || [];
                const activeTasks = allTasks.filter(t => t.status !== 'completed');

                const later = [];

                activeTasks.forEach(task => {
                    if (!task.due_date) {
                        // Default inbox tasks to Today for upcoming view
                        const todayGroup = upcomingDays.value.find(d => d.isToday);
                        if (todayGroup) todayGroup.tasks.push(task);
                        return;
                    }

                    const taskDateStr = dayjs(task.due_date).format('YYYY-MM-DD');
                    const matchingDay = upcomingDays.value.find(d => d.dateStr === taskDateStr);

                    if (matchingDay) {
                        matchingDay.tasks.push(task);
                    } else if (dayjs(taskDateStr).isAfter(dayjs().add(6, 'day'), 'day')) {
                        later.push(task);
                    } else if (dayjs(taskDateStr).isBefore(dayjs().format('YYYY-MM-DD'), 'day')) {
                        // Just in case it returned overdue tasks
                        overdueTasks.value.push(task);
                    }
                });

                // Sort later tasks by due date
                later.sort((a, b) => dayjs(a.due_date).diff(dayjs(b.due_date)));
                laterTasks.value = later;

                // De-duplicate overdue tasks
                const uniqueOverdue = [];
                const seenOverdue = new Set();
                overdueTasks.value.forEach(t => {
                    if (!seenOverdue.has(t.xid)) {
                        seenOverdue.add(t.xid);
                        uniqueOverdue.push(t);
                    }
                });
                overdueTasks.value = uniqueOverdue;

            } catch (error) {
                console.error(error);
                message.error('Failed to load upcoming tasks');
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

        const toggleComplete = async (task) => {
            try {
                const response = await axiosAdmin.post(`/enterprise-tasks/tasks/${task.xid}/toggle-complete`);
                if (response.success || response.status) {
                    message.success('Task completed!');
                    fetchUpcomingTasks();
                }
            } catch (error) {
                console.error(error);
                message.error('Failed to complete task');
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

        const openQuickAdd = (dateStr) => {
            window.__openQuickAdd?.(dateStr);
        };

        // Drag & Drop
        const onDragStart = (e, task) => {
            draggedTask.value = task;
            e.dataTransfer.setData('text/plain', task.xid);
        };

        const onDrop = async (e, dateTarget) => {
            const task = draggedTask.value;
            if (!task) return;

            let finalDateStr = dateTarget;
            if (dateTarget === 'overdue') {
                finalDateStr = dayjs().subtract(1, 'day').format('YYYY-MM-DD');
            } else if (dateTarget === 'later') {
                finalDateStr = dayjs().add(8, 'day').format('YYYY-MM-DD');
            }

            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, {
                    title: task.title,
                    status: task.status,
                    priority: task.priority,
                    due_date: finalDateStr,
                    x_project_id: task.project ? task.project.xid : null
                });
                message.success(`Task rescheduled to ${dateTarget === 'later' ? 'later date' : finalDateStr}`);
                fetchUpcomingTasks();
            } catch (error) {
                console.error(error);
                message.error('Failed to reschedule task');
            } finally {
                draggedTask.value = null;
            }
        };

        const currentDayNumber = ref(dayjs().date());

        const updateTaskField = async (task, field, value) => {
            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, {
                    ...task,
                    [field]: value
                });
                message.success('Task updated successfully');
                fetchUpcomingTasks();
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (error) {
                console.error(error);
                message.error('Failed to update task');
            }
        };

        const setTaskDate = (task, preset) => {
            let dateStr = null;
            if (preset === 'today') {
                dateStr = dayjs().format('YYYY-MM-DD');
            } else if (preset === 'tomorrow') {
                dateStr = dayjs().add(1, 'day').format('YYYY-MM-DD');
            } else if (preset === 'next_week') {
                dateStr = dayjs().add(1, 'week').startOf('week').add(1, 'day').format('YYYY-MM-DD');
            } else if (preset === 'weekend') {
                dateStr = dayjs().endOf('week').subtract(1, 'day').format('YYYY-MM-DD');
            }
            updateTaskField(task, 'due_date', dateStr);
        };

        const handleCustomDateChange = (task, dateString) => {
            updateTaskField(task, 'due_date', dateString || null);
        };

        const setTaskPriority = (task, priority) => {
            updateTaskField(task, 'priority', priority);
        };

        const duplicateTask = async (task) => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${task.xid}/duplicate`);
                message.success('Task duplicated');
                fetchUpcomingTasks();
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (error) {
                console.error(error);
                message.error('Failed to duplicate task');
            }
        };

        const copyLinkToTask = (task) => {
            const url = `${window.location.origin}/admin/enterprise-tasks/tasks?task=${task.xid}`;
            navigator.clipboard.writeText(url).then(() => {
                message.success('Link copied to clipboard');
            }).catch(() => {
                message.error('Failed to copy link');
            });
        };

        const openInNewWindow = (task) => {
            const url = `${window.location.origin}/admin/enterprise-tasks/tasks?task=${task.xid}`;
            window.open(url, '_blank');
        };

        const confirmDeleteTask = (task) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this task?',
                okText: 'Yes, Delete',
                okType: 'danger',
                cancelText: 'Cancel',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/tasks/${task.xid}`);
                        message.success('Task deleted successfully');
                        fetchUpcomingTasks();
                        window.dispatchEvent(new CustomEvent('task-created'));
                    } catch (error) {
                        console.error(error);
                        message.error('Failed to delete task');
                    }
                }
            });
        };

        onMounted(() => {
            fetchUpcomingTasks();
            window.addEventListener('task-created', fetchUpcomingTasks);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetchUpcomingTasks);
        });

        return {
            overdueTasks,
            upcomingDays,
            laterTasks,
            selectedTask,
            detailsVisible,
            quickAddOpen,
            selectedQuickAddDate,
            fetchUpcomingTasks,
            getPriorityColor,
            toggleComplete,
            viewTaskDetails,
            closeTaskDetails,
            openQuickAdd,
            onDragStart,
            onDrop,
            currentDayNumber,
            setTaskDate,
            handleCustomDateChange,
            setTaskPriority,
            duplicateTask,
            copyLinkToTask,
            openInNewWindow,
            confirmDeleteTask
        };
    }
});
</script>

<style scoped>
.upcoming-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.upcoming-days-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 900px;
    margin: 0 auto;
}

.day-group {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.02);
    transition: all 0.2s ease;
}

.day-group:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.is-today {
    border-left: 4px solid #4f46e5;
}

.overdue-group {
    border-left: 4px solid #ef4444;
    background: #fff5f5;
}

.day-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 10px;
    margin-bottom: 12px;
}

.header-overdue .day-title {
    color: #ef4444;
    font-weight: 700;
    font-size: 16px;
}

.day-name {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.day-date {
    font-size: 13px;
    color: #64748b;
}

.day-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.task-count {
    border-radius: 10px;
}

.add-day-task-btn {
    font-size: 12px;
    color: #4f46e5;
    padding: 0;
}

.day-tasks-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.empty-day-placeholder {
    padding: 12px 0;
    color: #94a3b8;
    font-size: 13px;
    font-style: italic;
}

.task-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.task-card:hover {
    background: white;
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.04);
}

.border-red {
    border-left: 3px solid #ef4444;
}

.task-top {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 6px;
}

.task-title {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    line-height: 1.4;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: #64748b;
    flex-wrap: wrap;
}

.time-text {
    font-weight: 500;
}

.due-text {
    font-weight: 500;
}

.text-red {
    color: #ef4444;
}

/* Hover Action Buttons and Context Menu */
.task-card {
    position: relative;
}
.task-card:hover .task-action-buttons {
    display: flex !important;
}
.action-icon-btn {
    transition: all 0.2s;
    padding: 2px;
}
.action-icon-btn:hover {
    color: #1f2937 !important;
    background-color: #f3f4f6;
    border-radius: 4px;
}

.todoist-context-menu {
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
}

.todoist-context-menu .ant-dropdown-menu-item,
.todoist-context-menu .ant-menu-item {
    padding: 8px 14px !important;
    line-height: 1.5 !important;
}

.menu-item-content {
    display: flex;
    align-items: center;
    width: 100%;
}

.menu-icon {
    display: inline-flex;
    align-items: center;
    margin-right: 12px;
    font-size: 15px;
    color: #6b7280;
}

.menu-title {
    font-size: 13.5px;
    color: #1f2937;
    flex: 1;
}

.menu-shortcut {
    font-size: 11px;
    color: #9ca3af;
    margin-left: 12px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
}

.menu-section-header {
    padding: 8px 14px 4px 14px;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-quick-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 14px 10px 14px;
}

.date-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #ffffff;
    user-select: none;
    font-size: 14px;
}

.date-icon-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    transform: translateY(-1px);
}

.today-btn {
    color: #10b981;
    font-weight: 700;
    border-color: #a7f3d0;
}

.today-btn .date-day-num {
    font-size: 11px;
    color: #10b981;
    border: 1.5px solid #10b981;
    border-radius: 3px;
    padding: 0 2px;
    line-height: 1;
    font-family: sans-serif;
}

.tomorrow-btn {
    border-color: #fde047;
}

.nextweek-btn {
    border-color: #bfdbfe;
}

.weekend-btn {
    border-color: #fed7aa;
}

.custom-picker-btn {
    padding: 0 !important;
    color: #9ca3af;
}

.custom-picker-btn .ant-picker-input {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.custom-picker-btn .ant-picker-input input {
    width: 100%;
    text-align: center;
    cursor: pointer;
    font-weight: bold;
    color: #6b7280;
}

.priority-quick-actions {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 6px 14px 10px 14px;
}

.priority-flag {
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.priority-flag:hover {
    transform: scale(1.2);
}

.flag-p1 {
    color: #ef4444;
}

.flag-p2 {
    color: #f97316;
}

.flag-p3 {
    color: #3b82f6;
}

.flag-p4 {
    color: #9ca3af;
}
</style>
