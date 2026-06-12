<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.tasks`)" class="p-0">
                <template #extra>
                    <a-button type="link" @click="() => $router.push({ name: 'admin.self.tasks.detailed_overview' })" style="padding: 0;">
                        Tasks Overview <ArrowRightOutlined />
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t(`menu.tasks`) }}
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="stats-cards-container" style="margin: 0 16px 24px 16px;">
        <a-row :gutter="16">
            <a-col :xs="24" :sm="12" :md="4" :lg="4" style="flex: 1 1 20%;">
                <a-card :bordered="false" class="stats-card not-started-tasks">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.notStarted }}</div>
                        <div class="card-label">Not Started</div>
                        <div class="card-sub-label">My Tasks: {{ stats.myNotStarted }}</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4" style="flex: 1 1 20%;">
                <a-card :bordered="false" class="stats-card in-progress-tasks">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.inProgress }}</div>
                        <div class="card-label">In Progress</div>
                        <div class="card-sub-label">My Tasks: {{ stats.myInProgress }}</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4" style="flex: 1 1 20%;">
                <a-card :bordered="false" class="stats-card testing-tasks">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.testing }}</div>
                        <div class="card-label">Testing</div>
                        <div class="card-sub-label">My Tasks: {{ stats.myTesting }}</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4" style="flex: 1 1 20%;">
                <a-card :bordered="false" class="stats-card awaiting-feedback-tasks">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.awaitingFeedback }}</div>
                        <div class="card-label">Awaiting Feedback</div>
                        <div class="card-sub-label">My Tasks: {{ stats.myAwaitingFeedback }}</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4" style="flex: 1 1 20%;">
                <a-card :bordered="false" class="stats-card complete-tasks">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.complete }}</div>
                        <div class="card-label">Complete</div>
                        <div class="card-sub-label">My Tasks: {{ stats.myComplete }}</div>
                    </div>
                </a-card>
            </a-col>
        </a-row>
    </div>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="8">
            </a-col>
            <a-col :xs="24" :sm="24" :md="16">
                <a-row :gutter="[16, 16]" justify="end" align="middle">
                    <a-col :xs="24" :sm="12" :md="8" :lg="6">
                        <a-select
                            v-model:value="selectedProjectFilter"
                            placeholder="Filter by Project"
                            style="width: 100%"
                            allowClear
                            @change="filterByProject"
                        >
                            <a-select-option
                                v-for="project in projects"
                                :key="project.xid"
                                :value="project.xid"
                            >
                                {{ project.name }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :xs="24" :sm="12" :md="8" :lg="6" v-if="viewMode === 'table'">
                        <a-input-search
                            v-model:value="table.searchString"
                            show-search
                            @change="onTableSearch"
                            @search="onTableSearch"
                            :loading="table.filterLoading"
                            :placeholder="$t('common.placeholder_search_text', [$t('task.name')])"
                        />
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="8" :lg="6" style="text-align: right;">
                        <a-radio-group v-model:value="viewMode" button-style="solid">
                            <a-radio-button value="table">
                                <UnorderedListOutlined /> List
                            </a-radio-button>
                            <a-radio-button value="kanban">
                                <AppstoreOutlined /> Kanban
                            </a-radio-button>
                        </a-radio-group>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <AddEdit
            :addEditType="addEditType"
            :visible="addEditVisible"
            :url="addEditUrl"
            @addEditSuccess="addEditSuccess"
            @closed="onCloseAddEdit"
            :formData="formData"
            :data="viewData"
            :pageTitle="pageTitle"
            :successMessage="successMessage"
        />

        <div v-if="viewMode === 'kanban'" class="kanban-wrapper">
            <a-row :gutter="16">
                <a-col
                    v-for="col in kanbanColumns"
                    :key="col.status"
                    :xs="24"
                    :sm="12"
                    :md="8"
                    :lg="4"
                    class="kanban-col-wrapper"
                >
                    <div
                        class="kanban-column"
                        :style="{ borderTop: '4px solid ' + col.color }"
                        @dragover.prevent
                        @drop="onDrop($event, col.status)"
                    >
                        <div class="kanban-column-header">
                            <span class="column-title">{{ col.title }}</span>
                            <a-tag :color="col.color" class="column-count">
                                {{ getColumnTasks(col.status).length }}
                            </a-tag>
                        </div>
                        <div class="kanban-cards-list">
                            <div
                                v-for="task in getColumnTasks(col.status)"
                                :key="task.xid"
                                class="kanban-card"
                                draggable="true"
                                @dragstart="onDragStart($event, task)"
                                @click="editItem(task)"
                            >
                                <div class="card-project-name">
                                    {{ task.project ? task.project.name : 'No Project' }}
                                </div>
                                <div class="card-task-name">{{ task.name }}</div>
                                <div style="margin-bottom: 8px;">
                                    <a-tag v-if="task.is_public" color="blue" style="font-size: 10px; padding: 0 4px;">Public</a-tag>
                                    <a-tag v-if="task.is_billable" color="gold" style="font-size: 10px; padding: 0 4px;">Billable</a-tag>
                                    <a-tag v-for="tag in task.tags" :key="tag" color="purple" style="font-size: 10px; padding: 0 4px;">{{ tag }}</a-tag>
                                </div>
                                <div class="card-meta">
                                    <span class="priority-indicator">
                                        <a-tag :color="getPriorityColor(task.priority)">
                                            {{ task.priority.toUpperCase() }}
                                        </a-tag>
                                    </span>
                                    <div class="card-footer">
                                        <div class="due-date" :class="{ 'overdue': isOverdue(task.due_date) }">
                                            <CalendarOutlined style="margin-right: 4px;" />
                                            {{ task.due_date ? task.due_date.substring(0, 10) : 'No Due Date' }}
                                        </div>
                                        <div class="card-assignees">
                                            <a-avatar-group :max-count="2" size="small">
                                                <a-tooltip
                                                    v-for="assignee in task.assignee_details"
                                                    :key="assignee.xid"
                                                    :title="assignee.name"
                                                >
                                                    <a-avatar :src="assignee.profile_image_url" />
                                                </a-tooltip>
                                            </a-avatar-group>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a-col>
            </a-row>
        </div>

        <a-row v-else>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="table.data"
                        :pagination="table.pagination"
                        :loading="table.loading"
                        @change="handleTableChange"
                        bordered
                        size="middle"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'name'">
                                <div>
                                    <span style="font-weight: 600;">{{ record.name }}</span>
                                    <div style="margin-top: 4px;">
                                        <a-tag v-if="record.is_public" color="blue">Public</a-tag>
                                        <a-tag v-if="record.is_billable" color="gold">Billable</a-tag>
                                        <a-tag v-for="tag in record.tags" :key="tag" color="purple">{{ tag }}</a-tag>
                                    </div>
                                </div>
                            </template>
                            <template v-if="column.dataIndex === 'project'">
                                {{ record.project ? record.project.name : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'status'">
                                <a-tag :color="getStatusColor(record.status)">
                                    {{ getStatusLabel(record.status) }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'priority'">
                                <a-tag :color="getPriorityColor(record.priority)">
                                    {{ record.priority.toUpperCase() }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'start_date'">
                                {{ record.start_date ? record.start_date.substring(0,10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'due_date'">
                                {{ record.due_date ? record.due_date.substring(0,10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'assignees'">
                                <a-avatar-group :max-count="3">
                                    <a-tooltip
                                        v-for="assignee in record.assignee_details"
                                        :key="assignee.xid"
                                        :title="assignee.name"
                                    >
                                        <a-avatar :src="assignee.profile_image_url" />
                                    </a-tooltip>
                                </a-avatar-group>
                            </template>
                            <template v-if="column.dataIndex === 'action'">
                                <a-button
                                    type="primary"
                                    @click="editItem(record)"
                                    style="margin-left: 4px"
                                >
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
</template>

<script>
import { onMounted, ref, watch, reactive } from "vue";
import {
    EditOutlined,
    UnorderedListOutlined,
    AppstoreOutlined,
    CalendarOutlined,
    ArrowRightOutlined
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import fields from "../../tasks/fields";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import AddEdit from "../../tasks/AddEdit.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        EditOutlined,
        UnorderedListOutlined,
        AppstoreOutlined,
        CalendarOutlined,
        AddEdit,
        AdminPageHeader,
        ArrowRightOutlined,
    },
    setup() {
        const { t } = useI18n();
        const { columns, filterableColumns, initData } = fields();
        const crudVariables = crud();
        const { permsArray, user } = common();

        const viewMode = ref("kanban"); // default to Kanban
        const projects = ref([]);
        const selectedProjectFilter = ref(undefined);

        const stats = reactive({
            notStarted: 0,
            myNotStarted: 0,
            inProgress: 0,
            myInProgress: 0,
            testing: 0,
            myTesting: 0,
            awaitingFeedback: 0,
            myAwaitingFeedback: 0,
            complete: 0,
            myComplete: 0,
        });

        const updateStats = () => {
            if (crudVariables.table.data) {
                const data = crudVariables.table.data;
                const userXid = user.value ? user.value.xid : null;

                stats.notStarted = data.filter(t => t.status === 'not_started').length;
                stats.myNotStarted = data.filter(t => t.status === 'not_started' && t.assignees && t.assignees.includes(userXid)).length;

                stats.inProgress = data.filter(t => t.status === 'in_progress').length;
                stats.myInProgress = data.filter(t => t.status === 'in_progress' && t.assignees && t.assignees.includes(userXid)).length;

                stats.testing = data.filter(t => t.status === 'testing').length;
                stats.myTesting = data.filter(t => t.status === 'testing' && t.assignees && t.assignees.includes(userXid)).length;

                stats.awaitingFeedback = data.filter(t => t.status === 'awaiting_feedback').length;
                stats.myAwaitingFeedback = data.filter(t => t.status === 'awaiting_feedback' && t.assignees && t.assignees.includes(userXid)).length;

                stats.complete = data.filter(t => t.status === 'complete').length;
                stats.myComplete = data.filter(t => t.status === 'complete' && t.assignees && t.assignees.includes(userXid)).length;
            }
        };

        watch(() => crudVariables.table.data, () => {
            updateStats();
        }, { deep: true });

        const kanbanColumns = [
            { status: "not_started", title: "Not Started", color: "#1890ff" },
            { status: "in_progress", title: "In Progress", color: "#722ed1" },
            { status: "testing", title: "Testing", color: "#fa8c16" },
            { status: "awaiting_feedback", title: "Awaiting Feedback", color: "#13c2c2" },
            { status: "complete", title: "Complete", color: "#52c41a" },
        ];

        onMounted(() => {
            columns.value = [
                ...columns.value,
                {
                    title: t("common.action"),
                    dataIndex: "action",
                },
            ];

            // Load projects for filtering
            axiosAdmin.get("self/projects?limit=1000").then((response) => {
                projects.value = response.data;
            });

            fetchTasks();

            crudVariables.crudUrl.value = "self/tasks";
            crudVariables.langKey.value = "task";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
        });

        const fetchTasks = () => {
            let baseUrl = "self/tasks?fields=id,xid,name,status,priority,start_date,due_date,description,project_id,project{id,xid,name},assignees,assignee_details,is_public,is_billable,task_file,task_file_url,hourly_rate,repeat_every,followers,follower_details,tags";
            if (selectedProjectFilter.value) {
                baseUrl += `&filters=project_id eq "${selectedProjectFilter.value}"`;
            }
            baseUrl += "&limit=1000";

            crudVariables.tableUrl.value = {
                url: baseUrl,
            };
            crudVariables.table.filterableColumns = filterableColumns;

            crudVariables.fetch({
                page: 1,
            });
        };

        const filterByProject = () => {
            fetchTasks();
        };

        const getColumnTasks = (status) => {
            if (!crudVariables.table.data) return [];
            return crudVariables.table.data.filter(t => t.status === status);
        };

        // Native drag & drop
        const onDragStart = (event, task) => {
            event.dataTransfer.setData("text/plain", task.xid);
        };

        const onDrop = (event, status) => {
            const taskXid = event.dataTransfer.getData("text/plain");
            const task = crudVariables.table.data.find(t => t.xid === taskXid);
            if (task && task.status !== status) {
                axiosAdmin.put(`self/tasks/${taskXid}`, {
                    ...task,
                    status: status,
                    _method: "PUT"
                }).then(() => {
                    fetchTasks();
                });
            }
        };

        const getStatusColor = (status) => {
            switch (status) {
                case "in_progress":
                    return "purple";
                case "testing":
                    return "orange";
                case "awaiting_feedback":
                    return "cyan";
                case "complete":
                    return "green";
                default:
                    return "blue";
            }
        };

        const getStatusLabel = (status) => {
            switch (status) {
                case "in_progress":
                    return "In Progress";
                case "testing":
                    return "Testing";
                case "awaiting_feedback":
                    return "Awaiting Feedback";
                case "complete":
                    return "Complete";
                default:
                    return "Not Started";
            }
        };

        const getPriorityColor = (priority) => {
            switch (priority) {
                case "urgent":
                    return "red";
                case "high":
                    return "volcano";
                case "medium":
                    return "blue";
                default:
                    return "gray";
            }
        };

        const isOverdue = (dueDate) => {
            if (!dueDate) return false;
            return new Date(dueDate) < new Date() && new Date(dueDate).toDateString() !== new Date().toDateString();
        };

        return {
            columns,
            ...crudVariables,
            filterableColumns,
            permsArray,
            viewMode,
            projects,
            selectedProjectFilter,
            kanbanColumns,
            fetchTasks,
            filterByProject,
            getColumnTasks,
            onDragStart,
            onDrop,
            getStatusColor,
            getStatusLabel,
            getPriorityColor,
            isOverdue,
            stats,
            // Override addEditSuccess to use our custom fetchTasks instead of generic fetch
            addEditSuccess: () => {
                crudVariables.addEditVisible.value = false;
                crudVariables.restFormData();
                fetchTasks();
            },
        };
    },
};
</script>

<style scoped>
.kanban-wrapper {
    overflow-x: auto;
    padding-bottom: 15px;
}
.kanban-col-wrapper {
    min-width: 250px;
}
.kanban-column {
    background: #f5f5f5;
    border-radius: 12px;
    padding: 12px;
    min-height: 500px;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.02);
}
.kanban-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 0 4px;
}
.column-title {
    font-weight: 700;
    font-size: 14px;
    color: #434343;
}
.column-count {
    border-radius: 10px;
    font-weight: 700;
}
.kanban-cards-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 450px;
}
.kanban-card {
    background: #ffffff;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    cursor: grab;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #f0f0f0;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.kanban-card:active {
    cursor: grabbing;
}
.card-project-name {
    font-size: 11px;
    color: #8c8c8c;
    margin-bottom: 4px;
}
.card-task-name {
    font-size: 13px;
    font-weight: 600;
    color: #262626;
    margin-bottom: 12px;
    line-height: 1.4;
}
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}
.due-date {
    font-size: 11px;
    color: #bfbfbf;
    display: flex;
    align-items: center;
}
.due-date.overdue {
    color: #ff4d4f;
    font-weight: 600;
}

.stats-cards-container {
    background: transparent;
}
.stats-card {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    background: #ffffff;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}
.card-inner {
    padding: 10px;
}
.card-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
}
.card-label {
    font-size: 14px;
    font-weight: 600;
    color: #8c8c8c;
    margin-top: 4px;
}
.card-sub-label {
    font-size: 12px;
    color: #8c8c8c;
    margin-top: 4px;
}

.not-started-tasks {
    border-left: 5px solid #1890ff;
}
.in-progress-tasks {
    border-left: 5px solid #722ed1;
}
.testing-tasks {
    border-left: 5px solid #fa8c16;
}
.awaiting-feedback-tasks {
    border-left: 5px solid #13c2c2;
}
.complete-tasks {
    border-left: 5px solid #52c41a;
}
</style>
