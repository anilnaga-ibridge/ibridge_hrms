<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Calendar Scheduler" class="p-0">
                <template #extra>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <a-button-group>
                            <a-button @click="prevMonth"><LeftOutlined /></a-button>
                            <a-button style="cursor: default; font-weight: bold;">{{ currentMonthYear }}</a-button>
                            <a-button @click="nextMonth"><RightOutlined /></a-button>
                        </a-button-group>
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
                <a-breadcrumb-item>Calendar</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div style="margin: 20px 16px;">
        <!-- Calendar Grid Layout -->
        <a-card :bordered="false" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); padding: 12px;">
            <!-- Days of Week Header -->
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; font-size: 13px; color: #4b5563;">
                <div v-for="day in daysOfWeek" :key="day">{{ day }}</div>
            </div>

            <!-- Month Cells -->
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: 110px; border-left: 1px solid #f3f4f6; border-top: 1px solid #f3f4f6;">
                <div
                    v-for="(cell, idx) in calendarCells"
                    :key="idx"
                    style="border-right: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; padding: 6px; display: flex; flex-direction: column; overflow: hidden; background: #ffffff;"
                    :style="{ background: cell.isCurrentMonth ? '#ffffff' : '#f9fafb' }"
                    @dragover.prevent
                    @drop="onDrop($event, cell.date)"
                >
                    <!-- Date Number -->
                    <div style="text-align: right; font-size: 12px; font-weight: 500; margin-bottom: 4px;" :style="{ color: cell.isToday ? '#3b82f6' : (cell.isCurrentMonth ? '#374151' : '#9ca3af') }">
                        <span v-if="cell.isToday" style="background: #3b82f6; color: #fff; border-radius: 50%; padding: 2px 6px; font-size: 11px;">{{ cell.dayNum }}</span>
                        <span v-else>{{ cell.dayNum }}</span>
                    </div>

                    <!-- Tasks List in cell -->
                    <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; padding-bottom: 4px;">
                        <div
                            v-for="task in getTasksForDate(cell.date)"
                            :key="task.xid"
                            draggable="true"
                            @dragstart="onDragStart($event, task)"
                            @click="viewTaskDetails(task)"
                            style="background: #eff6ff; color: #1e40af; font-size: 10px; padding: 3px 6px; border-radius: 4px; border-left: 3px solid #3b82f6; cursor: grab; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                            :style="{ borderLeftColor: getPriorityColorHex(task.priority), background: getPriorityBgHex(task.priority), color: getPriorityTextHex(task.priority) }"
                            :title="task.title"
                        >
                            {{ task.title }}
                        </div>
                    </div>
                </div>
            </div>
        </a-card>

        <!-- TASK DETAILS MODAL -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchTasks"
        />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted, computed } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import { LeftOutlined, RightOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        LeftOutlined,
        RightOutlined
    },
    setup() {
        const tasks = ref([]);
        const currentDate = ref(dayjs());
        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        // Detail Modal
        const selectedTask = ref(null);
        const detailsVisible = ref(false);

        // Drag and drop state
        const draggedTask = ref(null);

        const currentMonthYear = computed(() => {
            return currentDate.value.format('MMMM YYYY');
        });

        const calendarCells = computed(() => {
            const startOfMonth = currentDate.value.startOf('month');
            const endOfMonth = currentDate.value.endOf('month');
            
            const startDay = startOfMonth.day();
            const daysInMonth = startOfMonth.daysInMonth();
            
            const cells = [];

            // Previous month prefix cells
            const prevMonthDate = currentDate.value.subtract(1, 'month');
            const prevMonthDays = prevMonthDate.daysInMonth();
            for (let i = startDay - 1; i >= 0; i--) {
                const date = prevMonthDate.date(prevMonthDays - i);
                cells.push({
                    date: date.format('YYYY-MM-DD'),
                    dayNum: prevMonthDays - i,
                    isCurrentMonth: false,
                    isToday: date.isSame(dayjs(), 'day')
                });
            }

            // Current month cells
            for (let i = 1; i <= daysInMonth; i++) {
                const date = currentDate.value.date(i);
                cells.push({
                    date: date.format('YYYY-MM-DD'),
                    dayNum: i,
                    isCurrentMonth: true,
                    isToday: date.isSame(dayjs(), 'day')
                });
            }

            // Next month suffix cells
            const totalCells = cells.length;
            const remaining = 42 - totalCells; // support standard 6 weeks layout grid
            const nextMonthDate = currentDate.value.add(1, 'month');
            for (let i = 1; i <= remaining; i++) {
                const date = nextMonthDate.date(i);
                cells.push({
                    date: date.format('YYYY-MM-DD'),
                    dayNum: i,
                    isCurrentMonth: false,
                    isToday: date.isSame(dayjs(), 'day')
                });
            }

            return cells;
        });

        const fetchTasks = async () => {
            try {
                // Fetch all tasks for the current month window
                const start = currentDate.value.startOf('month').subtract(7, 'day').format('YYYY-MM-DD');
                const end = currentDate.value.endOf('month').add(7, 'day').format('YYYY-MM-DD');

                const response = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: { per_page: 300 }
                });

                tasks.value = response.data.filter(t => t.due_date);
            } catch (error) {
                console.error(error);
                message.error('Error fetching calendar tasks');
            }
        };

        const getTasksForDate = (dateString) => {
            return tasks.value.filter(t => dayjs(t.due_date).format('YYYY-MM-DD') === dateString);
        };

        const prevMonth = () => {
            currentDate.value = currentDate.value.subtract(1, 'month');
            fetchTasks();
        };

        const nextMonth = () => {
            currentDate.value = currentDate.value.add(1, 'month');
            fetchTasks();
        };

        // Drag and Drop
        const onDragStart = (e, task) => {
            draggedTask.value = task;
        };

        const onDrop = async (e, dateString) => {
            if (!draggedTask.value) return;

            // Optimistic update
            const oldDate = draggedTask.value.due_date;
            draggedTask.value.due_date = dateString;

            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${draggedTask.value.xid}`, {
                    ...draggedTask.value,
                    due_date: dateString
                });
                message.success('Task rescheduled successfully');
                fetchTasks();
            } catch (error) {
                console.error(error);
                message.error('Error rescheduling task');
                draggedTask.value.due_date = oldDate;
                fetchTasks();
            } finally {
                draggedTask.value = null;
            }
        };

        // Detail Modal
        const viewTaskDetails = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
        };

        // Style helper
        const getPriorityColorHex = (priority) => {
            const colors = { P1: '#ef4444', P2: '#f59e0b', P3: '#3b82f6', P4: '#10b981' };
            return colors[priority] || '#d1d5db';
        };

        const getPriorityBgHex = (priority) => {
            const colors = { P1: '#fee2e2', P2: '#fef3c7', P3: '#dbeafe', P4: '#dcfce7' };
            return colors[priority] || '#f3f4f6';
        };

        const getPriorityTextHex = (priority) => {
            const colors = { P1: '#991b1b', P2: '#92400e', P3: '#1e40af', P4: '#065f46' };
            return colors[priority] || '#374151';
        };

        onMounted(() => {
            fetchTasks();
            window.addEventListener('task-created', fetchTasks);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetchTasks);
        });

        return {
            daysOfWeek,
            calendarCells,
            currentMonthYear,
            selectedTask,
            detailsVisible,
            prevMonth,
            nextMonth,
            getTasksForDate,
            onDragStart,
            onDrop,
            viewTaskDetails,
            closeTaskDetails,
            getPriorityColorHex,
            getPriorityBgHex,
            getPriorityTextHex,
            fetchTasks
        };
    }
});
</script>
