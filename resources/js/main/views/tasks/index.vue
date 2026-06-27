<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.tasks`)" class="p-0">
                <template #extra>
                    <a-button type="link" @click="() => $router.push({ name: 'admin.tasks.detailed_overview' })" style="padding: 0;">
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

    <div class="stats-cards-container">
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="12" :md="4" :lg="4">
                <a-card :bordered="false" class="stats-card not-started-card">
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="icon-circle"><FieldTimeOutlined /></div>
                        </div>
                        <div class="card-value">{{ stats.notStarted }}</div>
                        <div class="card-label">Not Started</div>
                        <div class="card-meta"><UserOutlined class="sub-icon" /> {{ stats.myNotStarted }} assigned to me</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4">
                <a-card :bordered="false" class="stats-card in-progress-card">
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="icon-circle"><SyncOutlined /></div>
                        </div>
                        <div class="card-value">{{ stats.inProgress }}</div>
                        <div class="card-label">In Progress</div>
                        <div class="card-meta"><UserOutlined class="sub-icon" /> {{ stats.myInProgress }} assigned to me</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4">
                <a-card :bordered="false" class="stats-card testing-card">
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="icon-circle"><ExperimentOutlined /></div>
                        </div>
                        <div class="card-value">{{ stats.testing }}</div>
                        <div class="card-label">Testing</div>
                        <div class="card-meta"><UserOutlined class="sub-icon" /> {{ stats.myTesting }} assigned to me</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4">
                <a-card :bordered="false" class="stats-card awaiting-feedback-card">
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="icon-circle"><MessageOutlined /></div>
                        </div>
                        <div class="card-value">{{ stats.awaitingFeedback }}</div>
                        <div class="card-label">Awaiting Feedback</div>
                        <div class="card-meta"><UserOutlined class="sub-icon" /> {{ stats.myAwaitingFeedback }} assigned to me</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="4" :lg="4">
                <a-card :bordered="false" class="stats-card complete-card">
                    <div class="card-body">
                        <div class="card-icon-row">
                            <div class="icon-circle"><CheckCircleOutlined /></div>
                        </div>
                        <div class="card-value">{{ stats.complete }}</div>
                        <div class="card-label">Complete</div>
                        <div class="card-meta"><UserOutlined class="sub-icon" /> {{ stats.myComplete }} assigned to me</div>
                    </div>
                </a-card>
            </a-col>
        </a-row>
    </div>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="8">
                <a-space>
                    <a-button type="primary" @click="addItem">
                        <PlusOutlined />
                        {{ $t("task.add") }}
                    </a-button>
                    <a-button
                        v-if="viewMode === 'table' && table.selectedRowKeys.length > 0"
                        type="primary"
                        @click="showSelectedDeleteConfirm"
                        danger
                    >
                        <template #icon><DeleteOutlined /></template>
                        {{ $t("common.delete") }}
                    </a-button>
                </a-space>
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
            @voiceFill="assignNewFormData"
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
                        @dragover.prevent
                        @drop="onDrop($event, col.status)"
                    >
                        <div class="kanban-column-header">
                            <div class="column-title-row">
                                <span class="column-indicator" :style="{ background: col.color }"></span>
                                <span class="column-title">{{ col.title }}</span>
                            </div>
                            <span class="column-count" :style="{ color: col.color, background: col.color + '18' }">
                                {{ getColumnTasks(col.status).length }}
                            </span>
                        </div>
                        <div class="kanban-cards-list">
                            <div
                                v-for="task in getColumnTasks(col.status)"
                                :key="task.xid"
                                class="kanban-card"
                                :style="{ borderLeft: '3px solid ' + col.color }"
                                draggable="true"
                                @dragstart="onDragStart($event, task)"
                                @click="editItem(task)"
                            >
                                <div class="kcard-project-name">
                                    {{ task.project ? task.project.name : 'No Project' }}
                                </div>
                                <div class="kcard-task-name">{{ task.name }}</div>
                                <div class="kcard-tags" v-if="task.is_public || task.is_billable || (task.tags && task.tags.length)">
                                    <a-tag v-if="task.is_public" color="blue" class="kcard-tag">Public</a-tag>
                                    <a-tag v-if="task.is_billable" color="gold" class="kcard-tag">Billable</a-tag>
                                    <a-tag v-for="tag in task.tags" :key="tag" color="purple" class="kcard-tag">{{ tag }}</a-tag>
                                </div>
                                <div class="kcard-footer">
                                    <span class="kcard-priority">
                                        <span class="priority-dot" :style="{ background: getPriorityColorHex(task.priority) }"></span>
                                        {{ task.priority.toUpperCase() }}
                                    </span>
                                    <div class="kcard-meta-right">
                                        <span class="kcard-due" :class="{ 'overdue': isOverdue(task.due_date) }">
                                            <CalendarOutlined /> {{ task.due_date ? task.due_date.substring(0, 5) : '—' }}
                                        </span>
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
                </a-col>
            </a-row>
        </div>

        <a-row v-else>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: table.selectedRowKeys,
                            onChange: onRowSelectChange,
                            getCheckboxProps: (record) => ({
                                disabled: false,
                                name: record.xid,
                            }),
                        }"
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
                                <a-button
                                    type="primary"
                                    @click="showDeleteConfirm(record.xid)"
                                    style="margin-left: 4px"
                                    danger
                                >
                                    <template #icon><DeleteOutlined /></template>
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
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    UnorderedListOutlined,
    AppstoreOutlined,
    CalendarOutlined,
    ArrowRightOutlined,
    FieldTimeOutlined,
    SyncOutlined,
    ExperimentOutlined,
    MessageOutlined,
    CheckCircleOutlined,
    UserOutlined
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import fields from "./fields";
import crud from "../../../common/composable/crud";
import common from "../../../common/composable/common";
import AddEdit from "./AddEdit.vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        UnorderedListOutlined,
        AppstoreOutlined,
        CalendarOutlined,
        FieldTimeOutlined,
        SyncOutlined,
        ExperimentOutlined,
        MessageOutlined,
        CheckCircleOutlined,
        UserOutlined,
        AddEdit,
        AdminPageHeader,
    },
    setup() {
        const { t } = useI18n();
        const { addEditUrl, initData, columns, filterableColumns } = fields();
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
            axiosAdmin.get("projects?limit=1000").then((response) => {
                projects.value = response.data;
            });

            fetchTasks();

            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "task";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
        });

        const fetchTasks = () => {
            let baseUrl = "tasks?fields=id,xid,name,status,priority,start_date,due_date,description,project_id,project{id,xid,name},assignees,assignee_details,is_public,is_billable,task_file,task_file_url,hourly_rate,repeat_every,followers,follower_details,tags";
            if (selectedProjectFilter.value) {
                // Apply RestAPI filtering constraint
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
                const payload = {};
                for (const key in task) {
                    if (task[key] !== null && typeof task[key] === "object") {
                        continue;
                    }
                    payload[key] = task[key];
                }
                payload.status = status;
                payload._method = "PUT";

                axiosAdmin.put(`tasks/${taskXid}`, payload).then(() => {
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

        const getPriorityColorHex = (priority) => {
            switch (priority) {
                case "urgent":
                    return "#ff4d4f";
                case "high":
                    return "#fa541c";
                case "medium":
                    return "#1890ff";
                default:
                    return "#8c8c8c";
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
            getPriorityColorHex,
            isOverdue,
            stats,
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
    min-width: 260px;
}
.kanban-column {
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: 18px;
    padding: 16px 14px;
    min-height: 500px;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}
.kanban-column-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 0 4px 10px;
    border-bottom: 1px solid rgba(255,255,255,0.5);
}
.column-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
}
.column-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: block;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.column-title {
    font-weight: 700;
    font-size: 13px;
    color: rgba(0,0,0,0.7);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.column-count {
    border-radius: 8px;
    font-weight: 700;
    font-size: 12px;
    padding: 2px 10px;
    min-width: 24px;
    text-align: center;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.kanban-cards-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    min-height: 450px;
}
.kanban-card {
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
    border: 1px solid rgba(255,255,255,0.7);
    cursor: grab;
    transition: all 0.25s ease;
    position: relative;
}
.kanban-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
    background: rgba(255,255,255,0.7);
    border-color: rgba(255,255,255,0.9);
}
.kanban-card:active {
    cursor: grabbing;
    transform: translateY(0);
    background: rgba(255,255,255,0.45);
}
.kcard-project-name {
    font-size: 10px;
    font-weight: 600;
    color: rgba(0,0,0,0.35);
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.kcard-task-name {
    font-size: 14px;
    font-weight: 600;
    color: rgba(0,0,0,0.8);
    margin-bottom: 12px;
    line-height: 1.4;
}
.kcard-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 12px;
}
.kcard-tag {
    font-size: 10px;
    padding: 0 6px;
    line-height: 18px;
    border-radius: 4px;
}
.kcard-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.5);
}
.kcard-priority {
    font-size: 10px;
    font-weight: 700;
    color: rgba(0,0,0,0.4);
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.priority-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}
.kcard-meta-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.kcard-due {
    font-size: 11px;
    color: rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 4px;
}
.kcard-due.overdue {
    color: #ff4d4f;
    font-weight: 600;
}

.stats-cards-container {
    margin: 0 16px 24px 16px;
}

.stats-card {
    border-radius: 18px;
    background: #f0f2f5;
    box-shadow: 7px 7px 14px #d1d9e6, -7px -7px 14px #ffffff;
    transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: none !important;
    overflow: hidden;
    position: relative;
}

.stats-card:hover {
    box-shadow: 3px 3px 7px #d1d9e6, -3px -3px 7px #ffffff;
    transform: translateY(-2px);
}

.card-body {
    padding: 24px 20px 22px;
    text-align: center;
}

.card-icon-row {
    margin-bottom: 16px;
    display: flex;
    justify-content: center;
}

.icon-circle {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    background: #f0f2f5;
    box-shadow: inset 3px 3px 7px #d1d9e6, inset -3px -3px 7px #ffffff;
    transition: all 0.35s ease;
}

.stats-card:hover .icon-circle {
    box-shadow: inset 2px 2px 4px #d1d9e6, inset -2px -2px 4px #ffffff;
}

.not-started-card .icon-circle { color: #1890ff; }
.in-progress-card .icon-circle { color: #722ed1; }
.testing-card .icon-circle { color: #fa8c16; }
.awaiting-feedback-card .icon-circle { color: #13c2c2; }
.complete-card .icon-circle { color: #52c41a; }

.card-value {
    font-size: 36px;
    font-weight: 800;
    color: #1a1a1a;
    line-height: 1.1;
    letter-spacing: -1px;
}

.card-label {
    font-size: 12px;
    font-weight: 700;
    color: #8c8c8c;
    margin-top: 8px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
}

.card-meta {
    font-size: 12px;
    color: #bfbfbf;
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.sub-icon {
    font-size: 11px;
}

.not-started-card { border-left: none; }
.in-progress-card { border-left: none; }
.testing-card { border-left: none; }
.awaiting-feedback-card { border-left: none; }
.complete-card { border-left: none; }
</style>
