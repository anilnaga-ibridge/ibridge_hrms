<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Tasks List" class="p-0">
                <template #extra>
                    <a-space>
                        <a-button type="primary" @click="openCreateTaskModal">
                            <PlusOutlined /> Create Task
                        </a-button>
                    </a-space>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Tasks List</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div style="margin: 20px 16px;">
        <!-- Filters Card -->
        <a-card :bordered="false" style="border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);">
            <a-row :gutter="[12, 12]">
                <a-col :xs="24" :sm="12" :md="6">
                    <a-input-search v-model:value="filters.search" placeholder="Search tasks..." @search="fetchTasks" />
                </a-col>
                <a-col :xs="24" :sm="12" :md="4">
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <a-select v-model:value="filters.x_project_id" placeholder="Project" style="width: 100%" allowClear @change="fetchTasks">
                            <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">{{ proj.name }}</a-select-option>
                        </a-select>
                        <router-link v-if="filters.x_project_id" :to="{ name: 'admin.enterprise_tasks.project_details', params: { id: filters.x_project_id } }" style="font-size: 11px; align-self: flex-start; padding-left: 2px;">
                            <SettingOutlined /> Configure Sections
                        </router-link>
                    </div>
                </a-col>
                <a-col :xs="24" :sm="12" :md="4">
                    <a-select v-model:value="filters.status" placeholder="Status" style="width: 100%" allowClear @change="fetchTasks">
                        <a-select-option value="pending">Pending</a-select-option>
                        <a-select-option value="in_progress">In Progress</a-select-option>
                        <a-select-option value="under_review">Under Review</a-select-option>
                        <a-select-option value="testing">Testing</a-select-option>
                        <a-select-option value="completed">Completed</a-select-option>
                        <a-select-option value="cancelled">Cancelled</a-select-option>
                        <a-select-option value="on_hold">On Hold</a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="12" :md="4">
                    <a-select v-model:value="filters.priority" placeholder="Priority" style="width: 100%" allowClear @change="fetchTasks">
                        <a-select-option value="P1">P1 Critical</a-select-option>
                        <a-select-option value="P2">P2 High</a-select-option>
                        <a-select-option value="P3">P3 Medium</a-select-option>
                        <a-select-option value="P4">P4 Low</a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="12" :md="4">
                    <a-select v-model:value="filters.x_assignee_id" placeholder="Assignee" style="width: 100%" allowClear @change="fetchTasks">
                        <a-select-option v-for="user in users" :key="user.xid" :value="user.xid">{{ user.name }}</a-select-option>
                    </a-select>
                </a-col>
                <a-col :xs="24" :sm="12" :md="2">
                    <a-button type="link" @click="resetFilters" style="padding: 0;">Reset Filters</a-button>
                </a-col>
            </a-row>

            <div style="margin-top: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; color: #6b7280;">Group By:</span>
                    <a-radio-group v-model:value="groupBy" size="small" button-style="solid" @change="applyGrouping">
                        <a-radio-button value="none">None</a-radio-button>
                        <a-radio-button value="status">Status</a-radio-button>
                        <a-radio-button value="project">Project</a-radio-button>
                        <a-radio-button value="priority">Priority</a-radio-button>
                    </a-radio-group>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 13px; color: #6b7280;">Saved Views:</span>
                    <a-select placeholder="Select saved view" style="width: 200px" size="small" @change="applySavedView" allowClear>
                        <a-select-option v-for="view in savedViews" :key="view.xid" :value="view.xid">{{ view.name }}</a-select-option>
                    </a-select>

                    <a-button size="small" type="dashed" @click="openSaveFilterModal">
                        <SaveOutlined /> Save View
                    </a-button>
                </div>
            </div>
        </a-card>

        <!-- Bulk action bar -->
        <div v-if="selectedRowKeys.length > 0" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <span>Selected <strong>{{ selectedRowKeys.length }}</strong> tasks</span>
            <a-space>
                <a-select v-model:value="bulkActionData.status" placeholder="Change Status" style="width: 140px" allowClear>
                    <a-select-option value="pending">Pending</a-select-option>
                    <a-select-option value="in_progress">In Progress</a-select-option>
                    <a-select-option value="under_review">Under Review</a-select-option>
                    <a-select-option value="testing">Testing</a-select-option>
                    <a-select-option value="completed">Completed</a-select-option>
                </a-select>
                <a-select v-model:value="bulkActionData.priority" placeholder="Change Priority" style="width: 140px" allowClear>
                    <a-select-option value="P1">P1 Critical</a-select-option>
                    <a-select-option value="P2">P2 High</a-select-option>
                    <a-select-option value="P3">P3 Medium</a-select-option>
                    <a-select-option value="P4">P4 Low</a-select-option>
                </a-select>
                <a-button type="primary" size="small" @click="applyBulkUpdate">Apply</a-button>
                <a-button type="primary" danger size="small" @click="applyBulkDelete">Delete All</a-button>
                <a-button size="small" @click="selectedRowKeys = []">Cancel</a-button>
            </a-space>
        </div>

        <!-- Grouped Tasks or Standard Table -->
        <div v-if="groupBy !== 'none'">
            <div v-for="(group, key) in groupedTasks" :key="key" style="margin-bottom: 24px;">
                <div style="font-size: 16px; font-weight: bold; color: #374151; margin-bottom: 8px; border-bottom: 2px solid #e5e7eb; padding-bottom: 6px;">
                    {{ key.replace('_', ' ').toUpperCase() }} ({{ group.length }} tasks)
                </div>
                <a-table
                    :columns="columns"
                    :data-source="group"
                    row-key="xid"
                    :pagination="false"
                    size="middle"
                    :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'task_number'">
                            <code>{{ record.task_number }}</code>
                        </template>
                        <template v-if="column.key === 'title'">
                            <a style="font-weight: 500;" @click="viewTaskDetails(record)">{{ record.title }}</a>
                        </template>
                        <template v-if="column.key === 'project'">
                            <span>{{ record.project ? record.project.name : 'N/A' }}</span>
                        </template>
                        <template v-if="column.key === 'status'">
                            <a-tag :color="getStatusColor(record.status)">{{ record.status.replace('_', ' ').toUpperCase() }}</a-tag>
                        </template>
                        <template v-if="column.key === 'priority'">
                            <a-tag :color="getPriorityColor(record.priority)">{{ record.priority }}</a-tag>
                        </template>
                        <template v-if="column.key === 'due_date'">
                            <span :style="{ color: isOverdue(record) ? '#ef4444' : '#374151', fontWeight: isOverdue(record) ? 'bold' : 'normal' }">
                                {{ record.due_date ? record.due_date : 'N/A' }}
                            </span>
                        </template>
                    </template>
                </a-table>
            </div>
        </div>

        <div v-else>
            <a-table
                :columns="columns"
                :data-source="tasks"
                row-key="xid"
                :pagination="pagination"
                @change="handleTableChange"
                :row-selection="{ selectedRowKeys: selectedRowKeys, onChange: onSelectChange }"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'task_number'">
                        <code>{{ record.task_number }}</code>
                    </template>
                    <template v-if="column.key === 'title'">
                        <a style="font-weight: 500;" @click="viewTaskDetails(record)">{{ record.title }}</a>
                    </template>
                    <template v-if="column.key === 'project'">
                        <span>{{ record.project ? record.project.name : 'N/A' }}</span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-tag :color="getStatusColor(record.status)">{{ record.status.replace('_', ' ').toUpperCase() }}</a-tag>
                    </template>
                    <template v-if="column.key === 'priority'">
                        <a-tag :color="getPriorityColor(record.priority)">{{ record.priority }}</a-tag>
                    </template>
                    <template v-if="column.key === 'due_date'">
                        <span :style="{ color: isOverdue(record) ? '#ef4444' : '#374151', fontWeight: isOverdue(record) ? 'bold' : 'normal' }">
                            {{ record.due_date ? record.due_date : 'N/A' }}
                        </span>
                    </template>
                </template>
            </a-table>
        </div>

        <!-- TASK DETAILS MODAL -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchTasks"
        />

        <!-- CREATE TASK DRAWER -->
        <CreateTaskDrawer
            v-model:open="createModalOpen"
            @saved="fetchTasks"
        />

        <!-- SAVE FILTER MODAL -->
        <a-modal v-model:open="saveFilterOpen" title="Save Current View" @ok="handleSaveFilter">
            <a-form layout="vertical">
                <a-form-item label="View Name" required>
                    <a-input v-model:value="saveFilterName" placeholder="e.g. My Urgent Tasks View" />
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import { PlusOutlined, SaveOutlined, SettingOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        CreateTaskDrawer,
        PlusOutlined,
        SaveOutlined,
        SettingOutlined
    },
    setup() {
        const route = useRoute();
        const tasks = ref([]);
        const projects = ref([]);
        const users = ref([]);
        const groupBy = ref('none');
        const groupedTasks = ref({});
        const templates = ref([]);
        const savedViews = ref([]);
        const selectedTemplateXid = ref(undefined);

        const pagination = ref({
            current: 1,
            pageSize: 15,
            total: 0
        });

        const filters = ref({
            search: '',
            x_project_id: undefined,
            status: undefined,
            priority: undefined,
            x_assignee_id: undefined
        });

        const selectedRowKeys = ref([]);
        const bulkActionData = ref({
            status: undefined,
            priority: undefined
        });

        const selectedTask = ref(null);
        const detailsVisible = ref(false);

        const createModalOpen = ref(false);
        const saveFilterOpen = ref(false);
        const saveFilterName = ref('');

        const columns = [
            { title: 'Task #', dataIndex: 'task_number', key: 'task_number', width: 100 },
            { title: 'Title', dataIndex: 'title', key: 'title' },
            { title: 'Project', key: 'project' },
            { title: 'Status', dataIndex: 'status', key: 'status', width: 120 },
            { title: 'Priority', dataIndex: 'priority', key: 'priority', width: 100 },
            { title: 'Due Date', dataIndex: 'due_date', key: 'due_date', width: 130 }
        ];

        const fetchTasks = async () => {
            selectedRowKeys.value = [];
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: {
                        page: pagination.value.current,
                        per_page: pagination.value.pageSize,
                        parent_only: 'true',
                        ...filters.value
                    }
                });
                tasks.value = response.data;
                pagination.value.total = response.total;
                applyGrouping();
            } catch (error) {
                console.error(error);
                message.error('Error fetching tasks');
            }
        };

        const fetchProjects = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchUsers = async () => {
            try {
                const response = await axiosAdmin.get('/get-all-employees');
                users.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchTemplates = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/templates');
                templates.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchSavedViews = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/saved-views');
                savedViews.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const applyGrouping = () => {
            if (groupBy.value === 'none') {
                groupedTasks.value = {};
                return;
            }
            const groups = {};
            tasks.value.forEach(task => {
                let key = 'Unassigned';
                if (groupBy.value === 'status') {
                    key = task.status;
                } else if (groupBy.value === 'project') {
                    key = task.project ? task.project.name : 'No Project';
                } else if (groupBy.value === 'priority') {
                    key = task.priority;
                }
                if (!groups[key]) {
                    groups[key] = [];
                }
                groups[key].push(task);
            });
            groupedTasks.value = groups;
        };

        const applySavedView = (viewXid) => {
            if (!viewXid) {
                resetFilters();
                return;
            }
            const view = savedViews.value.find(v => v.xid === viewXid);
            if (view && view.filters) {
                filters.value = { ...filters.value, ...view.filters };
                if (view.grouping) groupBy.value = view.grouping;
                fetchTasks();
            }
        };

        const handleTableChange = (pag) => {
            pagination.value.current = pag.current;
            fetchTasks();
        };

        const onSelectChange = (keys) => {
            selectedRowKeys.value = keys;
        };

        const resetFilters = () => {
            filters.value = {
                search: '',
                x_project_id: undefined,
                status: undefined,
                priority: undefined,
                x_assignee_id: undefined
            };
            groupBy.value = 'none';
            pagination.value.current = 1;
            fetchTasks();
        };

        const applyBulkUpdate = async () => {
            try {
                await axiosAdmin.post('/enterprise-tasks/tasks/bulk-update', {
                    xids: selectedRowKeys.value,
                    status: bulkActionData.value.status,
                    priority: bulkActionData.value.priority
                });
                message.success('Bulk update applied successfully');
                selectedRowKeys.value = [];
                bulkActionData.value = { status: undefined, priority: undefined };
                fetchTasks();
            } catch (error) {
                console.error(error);
                message.error('Error applying bulk update');
            }
        };

        const applyBulkDelete = () => {
            Modal.confirm({
                title: 'Are you sure you want to delete these tasks?',
                okText: 'Yes, Delete',
                okType: 'danger',
                async onOk() {
                    try {
                        await axiosAdmin.post('/enterprise-tasks/tasks/bulk-delete', {
                            xids: selectedRowKeys.value
                        });
                        message.success('Selected tasks deleted successfully');
                        selectedRowKeys.value = [];
                        fetchTasks();
                    } catch (error) {
                        console.error(error);
                        message.error('Error deleting tasks');
                    }
                }
            });
        };

        const openCreateTaskModal = () => {
            window.__openQuickAdd?.();
        };

        const viewTaskDetails = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
        };

        const openSaveFilterModal = () => {
            saveFilterName.value = '';
            saveFilterOpen.value = true;
        };

        const handleSaveFilter = async () => {
            if (!saveFilterName.value) {
                message.error('Please enter a name for your saved view');
                return;
            }
            try {
                await axiosAdmin.post('/enterprise-tasks/saved-views', {
                    name: saveFilterName.value,
                    filters: filters.value,
                    grouping: groupBy.value
                });
                message.success('View saved successfully');
                saveFilterOpen.value = false;
                fetchSavedViews();
            } catch (error) {
                console.error(error);
                message.error('Error saving view');
            }
        };

        const getStatusColor = (status) => {
            const colors = {
                pending: 'blue',
                in_progress: 'orange',
                under_review: 'purple',
                testing: 'cyan',
                completed: 'green',
                cancelled: 'red',
                on_hold: 'pink'
            };
            return colors[status] || 'default';
        };

        const getPriorityColor = (priority) => {
            const colors = {
                P1: 'red',
                P2: 'orange',
                P3: 'blue',
                P4: 'green'
            };
            return colors[priority] || 'default';
        };

        const isOverdue = (record) => {
            if (!record.due_date || record.status === 'completed') return false;
            return new Date(record.due_date) < new Date().setHours(0,0,0,0);
        };

        onMounted(() => {
            if (route.query) {
                if (route.query.search) filters.value.search = route.query.search;
                if (route.query.x_project_id) filters.value.x_project_id = route.query.x_project_id;
                if (route.query.status) filters.value.status = route.query.status;
                if (route.query.priority) filters.value.priority = route.query.priority;
                if (route.query.x_assignee_id) filters.value.x_assignee_id = route.query.x_assignee_id;
            }
            fetchTasks();
            fetchProjects();
            fetchUsers();
            fetchTemplates();
            fetchSavedViews();
            window.addEventListener('task-created', fetchTasks);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetchTasks);
        });

        return {
            tasks,
            projects,
            users,
            groupBy,
            groupedTasks,
            templates,
            savedViews,
            selectedTemplateXid,
            pagination,
            filters,
            selectedRowKeys,
            bulkActionData,
            selectedTask,
            detailsVisible,
            createModalOpen,
            saveFilterOpen,
            saveFilterName,
            columns,
            fetchTasks,
            applyGrouping,
            applySavedView,
            handleTableChange,
            onSelectChange,
            resetFilters,
            applyBulkUpdate,
            applyBulkDelete,
            openCreateTaskModal,
            viewTaskDetails,
            closeTaskDetails,
            openSaveFilterModal,
            handleSaveFilter,
            getStatusColor,
            getPriorityColor,
            isOverdue
        };
    }
});
</script>

<style scoped>
.section-row:hover {
    background: #fafafa !important;
}
</style>
