<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Reporting: My completed tasks" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Reporting</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="reports-container">
        <!-- Top Filters Toolbar -->
        <a-card :bordered="false" class="filters-card">
            <div class="filters-toolbar">
                <!-- 1. Workspace Filter -->
                <div class="filter-item">
                    <a-select 
                        v-model:value="filterWorkspace" 
                        style="min-width: 160px"
                        class="premium-select"
                        :bordered="false"
                    >
                        <a-select-option value="all">All workspaces</a-select-option>
                    </a-select>
                </div>

                <!-- 2. Project Filter -->
                <div class="filter-item">
                    <a-select 
                        v-model:value="filterProject" 
                        style="min-width: 160px"
                        class="premium-select"
                        :bordered="false"
                        @change="fetchCompletedTasks"
                    >
                        <a-select-option value="all">All projects</a-select-option>
                        <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">
                            {{ proj.name }}
                        </a-select-option>
                    </a-select>
                </div>

                <!-- 3. Assignee Filter -->
                <div class="filter-item">
                    <a-select 
                        v-model:value="filterAssignee" 
                        style="min-width: 160px"
                        class="premium-select"
                        :bordered="false"
                        @change="fetchCompletedTasks"
                    >
                        <a-select-option value="all">Everyone</a-select-option>
                        <a-select-option v-for="emp in employees" :key="emp.xid" :value="emp.xid">
                            {{ emp.name }}
                        </a-select-option>
                    </a-select>
                </div>

                <!-- 4. Status Filter -->
                <div class="filter-item">
                    <a-select 
                        v-model:value="filterStatus" 
                        style="min-width: 160px"
                        class="premium-select"
                        :bordered="false"
                        @change="fetchCompletedTasks"
                    >
                        <a-select-option value="completed">Completed tasks</a-select-option>
                        <a-select-option value="pending">Pending tasks</a-select-option>
                        <a-select-option value="in_progress">In Progress tasks</a-select-option>
                    </a-select>
                </div>

                <!-- 5. Date Filter -->
                <div class="filter-item">
                    <a-select 
                        v-model:value="filterDateRange" 
                        style="min-width: 160px"
                        class="premium-select"
                        :bordered="false"
                        @change="fetchCompletedTasks"
                    >
                        <a-select-option value="all">Any date</a-select-option>
                        <a-select-option value="today">Today</a-select-option>
                        <a-select-option value="yesterday">Yesterday</a-select-option>
                        <a-select-option value="week">This week</a-select-option>
                        <a-select-option value="month">This month</a-select-option>
                    </a-select>
                </div>
            </div>
        </a-card>

        <!-- Activities Feed Timeline -->
        <div class="timeline-container">
            <a-spin :spinning="loading">
                <div v-if="groupedTasks.length === 0" class="empty-state">
                    <HistoryOutlined style="font-size: 40px; color: #94a3b8; margin-bottom: 12px;" />
                    <h3>No tasks history found</h3>
                    <p>There are no tasks matching the selected filters.</p>
                </div>

                <div v-else class="timeline-groups">
                    <div 
                        v-for="group in groupedTasks" 
                        :key="group.date" 
                        class="day-group"
                    >
                        <!-- Date Header -->
                        <div class="day-header">
                            <span class="day-title">{{ formatDateGroupHeader(group.date) }}</span>
                            <span class="day-count">{{ group.tasks.length }}</span>
                        </div>

                        <!-- Task Items List -->
                        <div class="day-tasks">
                            <div 
                                v-for="task in group.tasks" 
                                :key="task.xid" 
                                class="activity-row"
                                @click="viewTaskDetails(task.xid)"
                            >
                                <div class="activity-left">
                                    <!-- User Avatar -->
                                    <a-avatar 
                                        :src="getCompleterAvatar(task)" 
                                        :size="36" 
                                        class="activity-avatar"
                                    >
                                        {{ getCompleterInitials(task) }}
                                    </a-avatar>
                                    
                                    <div class="activity-details">
                                        <div class="activity-user-text">
                                            <span class="completer-name">
                                                {{ getCompleterName(task) }}
                                            </span>
                                            <span class="action-text">
                                                {{ getActionText(task) }}
                                            </span>
                                        </div>
                                        <div class="task-title-wrapper">
                                            <span class="task-title-link">{{ task.title }}</span>
                                            <a-tag v-if="task.project" :color="task.project.color || 'blue'" class="project-tag">
                                                {{ task.project.name }}
                                            </a-tag>
                                        </div>
                                    </div>
                                </div>
                                <div class="activity-right">
                                    <span class="relative-time">{{ formatRelativeTime(task.completion_date || task.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom End Indicator -->
                    <div class="feed-end-indicator">
                        <span class="end-text">That's it. No more history to load.</span>
                    </div>
                </div>
            </a-spin>
        </div>
    </div>

    <!-- Task Details Modal -->
    <TaskDetailsModal
        :visible="detailsVisible"
        :task-id="selectedTaskId"
        @close="closeDetails"
        @updated="fetchCompletedTasks"
    />
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import { HistoryOutlined } from '@ant-design/icons-vue';
import common from '../../../common/composable/common';

export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        HistoryOutlined
    },
    setup() {
        const { user } = common();

        const projects = ref([]);
        const employees = ref([]);
        const tasks = ref([]);
        const loading = ref(false);

        // Filter state variables
        const filterWorkspace = ref('all');
        const filterProject = ref('all');
        const filterAssignee = ref('all');
        const filterStatus = ref('completed');
        const filterDateRange = ref('all');

        // Task details modal state
        const selectedTaskId = ref(null);
        const detailsVisible = ref(false);

        // Fetch projects
        const fetchProjects = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        // Fetch employees
        const fetchEmployees = async () => {
            try {
                const response = await axiosAdmin.get('/get-all-employees');
                employees.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        // Fetch completed/filtered tasks
        const fetchCompletedTasks = async () => {
            loading.value = true;
            try {
                const params = {
                    status: filterStatus.value,
                    per_page: 150, // Retrieve a high number to render a rich history feed
                    order_by: filterStatus.value === 'completed' ? 'completion_date' : 'updated_at',
                    order_dir: 'desc'
                };

                if (filterProject.value !== 'all') {
                    params.x_project_id = filterProject.value;
                }

                // If filtering by assignee
                if (filterAssignee.value !== 'all') {
                    params.x_completed_by = filterAssignee.value;
                }

                const response = await axiosAdmin.get('/enterprise-tasks/tasks', { params });
                
                // Keep only tasks matching selected date range if not 'all'
                let filteredList = response.data || [];
                
                if (filterDateRange.value !== 'all') {
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(startOfWeek.getDate() - today.getDay()); // Sunday as start
                    
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);

                    filteredList = filteredList.filter(task => {
                        const targetDate = new Date(task.completion_date || task.updated_at);
                        targetDate.setHours(0,0,0,0);

                        if (filterDateRange.value === 'today') {
                            return targetDate.getTime() === today.getTime();
                        } else if (filterDateRange.value === 'yesterday') {
                            return targetDate.getTime() === yesterday.getTime();
                        } else if (filterDateRange.value === 'week') {
                            return targetDate.getTime() >= startOfWeek.getTime();
                        } else if (filterDateRange.value === 'month') {
                            return targetDate.getTime() >= startOfMonth.getTime();
                        }
                        return true;
                    });
                }

                tasks.value = filteredList;
            } catch (error) {
                console.error(error);
            } finally {
                loading.value = false;
            }
        };

        // Group tasks by completion date (or updated date)
        const groupedTasks = computed(() => {
            const groups = {};
            tasks.value.forEach(task => {
                const dateVal = task.completion_date || task.updated_at;
                if (!dateVal) return;
                const dateStr = dateVal.split('T')[0];
                if (!groups[dateStr]) {
                    groups[dateStr] = [];
                }
                groups[dateStr].push(task);
            });

            // Sort dates descending
            const sortedKeys = Object.keys(groups).sort((a, b) => new Date(b) - new Date(a));
            return sortedKeys.map(key => ({
                date: key,
                tasks: groups[key]
            }));
        });

        // Helper methods for Completer (updatedByUser or createdByUser fallback)
        const getCompleter = (task) => {
            return task.updatedByUser || task.createdByUser || null;
        };

        const getCompleterName = (task) => {
            const completer = getCompleter(task);
            if (!completer) return 'System';
            return user.value && user.value.xid === completer.xid ? 'You' : completer.name;
        };

        const getCompleterAvatar = (task) => {
            const completer = getCompleter(task);
            return completer ? completer.profile_image_url : null;
        };

        const getCompleterInitials = (task) => {
            const completer = getCompleter(task);
            if (!completer) return 'S';
            return completer.name ? completer.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'U';
        };

        const getActionText = (task) => {
            if (task.status === 'completed') {
                return 'completed';
            } else if (task.status === 'in_progress') {
                return 'started';
            } else {
                return 'updated';
            }
        };

        // Formatting utilities
        const formatDateGroupHeader = (dateStr) => {
            const date = new Date(dateStr);
            const today = new Date();
            today.setHours(0,0,0,0);
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            
            const options = { day: 'numeric', month: 'short' };
            const dateFormatted = date.toLocaleDateString('en-US', options); // e.g. "26 Jun"
            
            // Format weekday
            const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const weekday = weekdays[date.getDay()];

            // Check Today vs Yesterday
            const compareDate = new Date(dateStr);
            compareDate.setHours(0,0,0,0);

            if (compareDate.getTime() === today.getTime()) {
                return `${dateFormatted} ‧ Today ‧ ${weekday}`;
            } else if (compareDate.getTime() === yesterday.getTime()) {
                return `${dateFormatted} ‧ Yesterday ‧ ${weekday}`;
            } else {
                return `${dateFormatted} ‧ ${weekday}`;
            }
        };

        const formatRelativeTime = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
            if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            
            const diffDays = Math.floor(diffHours / 24);
            if (diffDays === 1) return 'Yesterday';
            return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
        };

        // Task details modal trigger
        const viewTaskDetails = (xid) => {
            selectedTaskId.value = xid;
            detailsVisible.value = true;
        };

        const closeDetails = () => {
            detailsVisible.value = false;
            selectedTaskId.value = null;
        };

        onMounted(() => {
            fetchProjects();
            fetchEmployees();
            fetchCompletedTasks();
        });

        return {
            projects,
            employees,
            groupedTasks,
            loading,

            // Filters
            filterWorkspace,
            filterProject,
            filterAssignee,
            filterStatus,
            filterDateRange,
            fetchCompletedTasks,

            // Helpers
            getCompleterName,
            getCompleterAvatar,
            getCompleterInitials,
            getActionText,
            formatDateGroupHeader,
            formatRelativeTime,

            // Details modal
            selectedTaskId,
            detailsVisible,
            viewTaskDetails,
            closeDetails
        };
    }
});
</script>

<style scoped>
.reports-container {
    margin: 20px 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Filters Card */
.filters-card {
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    background: #ffffff;
    margin-bottom: 24px;
    padding: 8px 12px;
}

.filters-toolbar {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background-color: #fafafa;
    overflow: hidden;
    transition: all 0.2s ease;
}

.filter-item:hover {
    border-color: #cbd5e1;
    background-color: #f1f5f9;
}

.premium-select :deep(.ant-select-selector) {
    background-color: transparent !important;
    font-weight: 550;
    color: #475569;
}

/* Timeline Activity Feed */
.timeline-container {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.03);
    padding: 24px;
    min-height: 450px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    color: #64748b;
    text-align: center;
}

.empty-state h3 {
    font-weight: 600;
    margin-bottom: 4px;
    color: #334155;
}

.day-group {
    margin-bottom: 28px;
}

.day-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #f1f5f9;
}

.day-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
}

.day-count {
    font-size: 11px;
    background-color: #f1f5f9;
    color: #475569;
    border-radius: 9999px;
    padding: 1px 8px;
    font-weight: 600;
}

.day-tasks {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-radius: 12px;
    background-color: #f8fafc;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
    cursor: pointer;
}

.activity-row:hover {
    background-color: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
}

.activity-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
}

.activity-avatar {
    background-color: #db4c3f;
    color: #ffffff;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.activity-details {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.activity-user-text {
    font-size: 13px;
    color: #64748b;
}

.completer-name {
    font-weight: 600;
    color: #334155;
    margin-right: 4px;
}

.action-text {
    color: #64748b;
}

.task-title-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.task-title-link {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    transition: color 0.15s ease;
}

.activity-row:hover .task-title-link {
    color: #db4c3f;
}

.project-tag {
    font-size: 11px;
    border-radius: 4px;
    line-height: 16px;
}

.activity-right {
    display: flex;
    align-items: center;
}

.relative-time {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
}

/* Feed End */
.feed-end-indicator {
    text-align: center;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
    margin-top: 8px;
}

.end-text {
    font-size: 13px;
    color: #94a3b8;
}
</style>
