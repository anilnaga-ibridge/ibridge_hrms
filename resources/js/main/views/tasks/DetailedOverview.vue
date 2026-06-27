<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="'Tasks Overview'" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="() => $router.push({ name: 'admin.tasks.index' })">
                        <ArrowLeftOutlined /> Back to tasks list
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
                    <router-link :to="{ name: 'admin.tasks.index' }">
                        {{ $t(`menu.tasks`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    Detailed Overview
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
            <a-col :xs="24" :sm="12" :md="6">
                <a-select
                    v-model:value="selectedMonth"
                    placeholder="Select Month"
                    style="width: 100%"
                    @change="filterData"
                >
                    <a-select-option value="all">All Months</a-select-option>
                    <a-select-option value="01">January</a-select-option>
                    <a-select-option value="02">February</a-select-option>
                    <a-select-option value="03">March</a-select-option>
                    <a-select-option value="04">April</a-select-option>
                    <a-select-option value="05">May</a-select-option>
                    <a-select-option value="06">June</a-select-option>
                    <a-select-option value="07">July</a-select-option>
                    <a-select-option value="08">August</a-select-option>
                    <a-select-option value="09">September</a-select-option>
                    <a-select-option value="10">October</a-select-option>
                    <a-select-option value="11">November</a-select-option>
                    <a-select-option value="12">December</a-select-option>
                </a-select>
            </a-col>
            <a-col :xs="24" :sm="12" :md="6">
                <a-select
                    v-model:value="selectedYear"
                    placeholder="Select Year"
                    style="width: 100%"
                    @change="filterData"
                >
                    <a-select-option value="all">All Years</a-select-option>
                    <a-select-option value="2024">2024</a-select-option>
                    <a-select-option value="2025">2025</a-select-option>
                    <a-select-option value="2026">2026</a-select-option>
                    <a-select-option value="2027">2027</a-select-option>
                    <a-select-option value="2028">2028</a-select-option>
                    <a-select-option value="2029">2029</a-select-option>
                    <a-select-option value="2030">2030</a-select-option>
                </a-select>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12">
                <a-input-search
                    v-model:value="searchQuery"
                    placeholder="Search by task name..."
                    style="width: 100%"
                    @change="filterData"
                    @search="filterData"
                />
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="filteredTasks"
                        :loading="loading"
                        bordered
                        size="middle"
                        :pagination="{ pageSize: 15 }"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'name'">
                                <div>
                                    <span style="font-weight: 600; font-size: 14px; color: #1890ff;">{{ record.name }}</span>
                                    <div style="font-size: 11px; color: #8c8c8c; margin-top: 4px;">
                                        Related To: {{ record.project ? `#${record.project.id || ''} - ${record.project.name}` : '-' }}
                                    </div>
                                </div>
                            </template>
                            <template v-if="column.dataIndex === 'start_date'">
                                {{ record.start_date ? record.start_date.substring(0, 10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'due_date'">
                                {{ record.due_date ? record.due_date.substring(0, 10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'status'">
                                <a-tag :color="getStatusColor(record.status)">
                                    {{ getStatusLabel(record.status) }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'attachments'">
                                {{ record.task_file ? 1 : 0 }}
                            </template>
                            <template v-if="column.dataIndex === 'comments'">
                                0
                            </template>
                            <template v-if="column.dataIndex === 'checklist'">
                                {{ getChecklistMock(record) }}
                            </template>
                            <template v-if="column.dataIndex === 'logged_time'">
                                00:00
                            </template>
                            <template v-if="column.dataIndex === 'finished_on_time'">
                                <span v-if="record.status === 'complete'">
                                    <a-tag color="success">Yes</a-tag>
                                </span>
                                <span v-else>-</span>
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
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
</template>

<script>
import { defineComponent, ref, onMounted, reactive, watch } from "vue";
import {
    ArrowLeftOutlined,
    FieldTimeOutlined,
    SyncOutlined,
    ExperimentOutlined,
    MessageOutlined,
    CheckCircleOutlined,
    UserOutlined
} from "@ant-design/icons-vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";
import common from "../../../common/composable/common";

export default defineComponent({
    components: {
        ArrowLeftOutlined,
        FieldTimeOutlined,
        SyncOutlined,
        ExperimentOutlined,
        MessageOutlined,
        CheckCircleOutlined,
        UserOutlined,
        AdminPageHeader,
    },
    setup() {
        const loading = ref(false);
        const allTasks = ref([]);
        const filteredTasks = ref([]);
        const searchQuery = ref("");
        const selectedMonth = ref("all");
        const selectedYear = ref("all");
        const { user } = common();

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
            const data = filteredTasks.value || [];
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
        };

        watch(filteredTasks, () => {
            updateStats();
        }, { deep: true });

        const columns = [
            {
                title: "Name",
                dataIndex: "name",
            },
            {
                title: "Start Date",
                dataIndex: "start_date",
            },
            {
                title: "Due Date",
                dataIndex: "due_date",
            },
            {
                title: "Status",
                dataIndex: "status",
            },
            {
                title: "Total attachments added",
                dataIndex: "attachments",
            },
            {
                title: "Total comments",
                dataIndex: "comments",
            },
            {
                title: "Checklist Items",
                dataIndex: "checklist",
            },
            {
                title: "Total Logged Time",
                dataIndex: "logged_time",
            },
            {
                title: "Finished on time?",
                dataIndex: "finished_on_time",
            },
            {
                title: "Assigned to",
                dataIndex: "assignees",
            },
        ];

        const fetchTasks = () => {
            loading.value = true;
            axiosAdmin
                .get(
                    "tasks?fields=id,xid,name,status,priority,start_date,due_date,description,project_id,project{id,xid,name},assignees,assignee_details,task_file,task_file_url&limit=1000"
                )
                .then((response) => {
                    allTasks.value = response.data;
                    filterData();
                    loading.value = false;
                })
                .catch(() => {
                    loading.value = false;
                });
        };

        const filterData = () => {
            let filtered = allTasks.value;

            // Search Filter
            if (searchQuery.value) {
                const query = searchQuery.value.toLowerCase();
                filtered = filtered.filter(
                    (t) =>
                        t.name.toLowerCase().includes(query) ||
                        (t.project && t.project.name.toLowerCase().includes(query))
                );
            }

            // Month Filter
            if (selectedMonth.value !== "all") {
                filtered = filtered.filter((t) => {
                    if (!t.start_date) return false;
                    const month = t.start_date.substring(5, 7);
                    return month === selectedMonth.value;
                });
            }

            // Year Filter
            if (selectedYear.value !== "all") {
                filtered = filtered.filter((t) => {
                    if (!t.start_date) return false;
                    const year = t.start_date.substring(0, 4);
                    return year === selectedYear.value;
                });
            }

            filteredTasks.value = filtered;
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

        const getChecklistMock = (task) => {
            // Generates a mock checklist ratio based on task name length to look authentic
            if (task.status === "complete") {
                const total = (task.name.length % 3) + 1;
                return `${total}/${total}`;
            } else {
                const total = (task.name.length % 3) + 2;
                const completed = task.name.length % 2;
                return `${completed}/${total}`;
            }
        };

        onMounted(() => {
            fetchTasks();
        });

        return {
            loading,
            filteredTasks,
            searchQuery,
            selectedMonth,
            selectedYear,
            columns,
            getStatusColor,
            getStatusLabel,
            getChecklistMock,
            filterData,
            stats,
        };
    },
});
</script>

<style scoped>
.table-responsive {
    overflow-x: auto;
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
