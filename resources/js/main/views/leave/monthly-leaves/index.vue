<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.monthly_leaves`)" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t(`menu.leaves`) }}</a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t(`menu.monthly_leaves`) }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <a-button
                        v-if="permsArray.includes('admin') || permsArray.includes('leaves_edit')"
                        type="primary"
                        @click="processMonthlyLeaves"
                        :loading="processLoading"
                    >
                        <template #icon><SyncOutlined /></template>
                        {{ filters.user_id ? $t('leave.process_user_leaves') : $t('leave.process_all_leaves') }}
                    </a-button>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col
                        :xs="24" :sm="24" :md="12" :lg="8" :xl="8"
                        v-if="permsArray.includes('admin') || permsArray.includes('leaves_view')"
                    >
                        <a-select
                            v-model:value="filters.user_id"
                            @change="onUserChange"
                            show-search
                            style="width: 100%"
                            :placeholder="$t('common.select_default_text', [$t('leave.user')])"
                            :allowClear="true"
                            optionFilterProp="title"
                        >
                            <a-select-option
                                v-for="allUser in allUsers"
                                :key="allUser.xid"
                                :value="allUser.xid"
                                :title="allUser.name"
                            >
                                <user-list-display :user="allUser" whereToShow="select" />
                            </a-select-option>
                        </a-select>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>

        <a-tabs v-model:activeKey="activeTab" @change="onTabChange">
            <!-- ═══════════════════════════════════════════════════
                 LEAVES TAB
            ════════════════════════════════════════════════════ -->
            <a-tab-pane key="leaves" :tab="$t('menu.monthly_leaves')">

        <!-- ═══════════════════════════════════════════════════
             EMPLOYEE DETAIL VIEW  (an employee is selected)
        ════════════════════════════════════════════════════ -->
        <div v-if="filters.user_id">

            <!-- Summary Cards -->
            <a-row :gutter="[16, 16]" class="ml-summary-row">
                <a-col :xs="12" :sm="12" :md="6">
                    <a-skeleton :loading="summaryLoading" active :paragraph="{ rows: 2 }">
                        <div class="ml-stat-card ml-stat-card--active" @click="filters.status = 'ACTIVE'; setUrlData()">
                            <div class="ml-stat-icon">
                                <CheckCircleFilled />
                            </div>
                            <div class="ml-stat-body">
                                <div class="ml-stat-value">{{ summary.active_count }}</div>
                                <div class="ml-stat-label">{{ $t('leave.active_leaves') }}</div>
                            </div>
                        </div>
                    </a-skeleton>
                </a-col>
                <a-col :xs="12" :sm="12" :md="6">
                    <a-skeleton :loading="summaryLoading" active :paragraph="{ rows: 2 }">
                        <div class="ml-stat-card ml-stat-card--used" @click="filters.status = 'USED'; setUrlData()">
                            <div class="ml-stat-icon">
                                <ClockCircleFilled />
                            </div>
                            <div class="ml-stat-body">
                                <div class="ml-stat-value">{{ summary.used_count }}</div>
                                <div class="ml-stat-label">{{ $t('leave.used_leaves') }}</div>
                            </div>
                        </div>
                    </a-skeleton>
                </a-col>
                <a-col :xs="12" :sm="12" :md="6">
                    <a-skeleton :loading="summaryLoading" active :paragraph="{ rows: 2 }">
                        <div class="ml-stat-card ml-stat-card--expired" @click="filters.status = 'EXPIRED'; setUrlData()">
                            <div class="ml-stat-icon">
                                <CloseCircleFilled />
                            </div>
                            <div class="ml-stat-body">
                                <div class="ml-stat-value">{{ summary.expired_count }}</div>
                                <div class="ml-stat-label">{{ $t('leave.expired_leaves') }}</div>
                            </div>
                        </div>
                    </a-skeleton>
                </a-col>
                <a-col :xs="12" :sm="12" :md="6">
                    <a-skeleton :loading="summaryLoading" active :paragraph="{ rows: 2 }">
                        <div class="ml-stat-card ml-stat-card--next">
                            <div class="ml-stat-icon">
                                <CalendarFilled />
                            </div>
                            <div class="ml-stat-body">
                                <div class="ml-stat-value ml-stat-value--date">{{ summary.next_credit_date }}</div>
                                <div class="ml-stat-label">
                                    {{ $t('leave.next_credit_date') }}
                                    <span v-if="summary.monthly_quota" style="font-size: 11px; text-transform: none; display: block; color: #888; margin-top: 2px;">
                                        {{ $t('leave.monthly_leaves') }}: <strong>{{ summary.monthly_quota }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a-skeleton>
                </a-col>
            </a-row>

            <!-- ── Breakdown Panel ── -->
            <div v-if="!summaryLoading && summary.cycle_breakdown" class="ml-breakdown-panel">
                <a-row :gutter="[16, 0]" align="top">
                    <!-- Active Leaves Column -->
                    <a-col :xs="24" :sm="24" :md="12">
                        <div class="ml-bp-section">
                            <div class="ml-bp-label">{{ $t('leave.current_month_cycle_status', [Object.keys(summary.cycle_breakdown).length]) }}</div>

                            <div
                                v-for="(item, key) in summary.cycle_breakdown"
                                :key="key"
                                class="ml-bp-row"
                                :class="getCycleRowClass(item.status)"
                            >
                                <component :is="getCycleIcon(item.status)" :class="getCycleIconClass(item.status)" />
                                <span class="ml-bp-month" :class="getCycleMonthClass(item.status)">
                                    {{ item.month_name }}
                                </span>
                                <div class="ml-bp-meta">
                                    <span class="ml-bp-tag" :class="getCycleTagClass(key)">{{ item.label }}</span>
                                    <span class="ml-status-tag" :class="getStatusTagClass(item.status)">
                                        {{ getStatusText(item) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a-col>

                    <!-- Info Message Column -->
                    <a-col :xs="24" :sm="24" :md="12">
                        <div
                            class="ml-bp-info"
                            :class="(summary.carry_forward_leaves && summary.carry_forward_leaves.length > 0) ? 'ml-bp-info--warn' : 'ml-bp-info--info'"
                            v-if="summary.info_message"
                        >
                            <InfoCircleOutlined class="ml-bp-info-icon" />
                            <span>{{ summary.info_message }}</span>
                        </div>

                        <!-- Recent Expired -->
                        <div v-if="summary.expired_leaves && summary.expired_leaves.length > 0" class="ml-bp-expired-list">
                            <div class="ml-bp-label ml-bp-label--expired">{{ $t('leave.recently_expired') }}</div>
                            <div
                                v-for="month in summary.expired_leaves"
                                :key="month"
                                class="ml-bp-row ml-bp-row--expired"
                            >
                                <CloseCircleFilled class="ml-bp-icon ml-bp-icon--expired" />
                                <span class="ml-bp-month ml-bp-month--expired">{{ month }}</span>
                            </div>
                        </div>
                    </a-col>
                </a-row>
            </div>

            <!-- Status Filter Tabs -->

            <a-tabs v-model:activeKey="filters.status" @change="setUrlData" class="ml-tabs">
                <a-tab-pane key="ACTIVE">
                    <template #tab>
                        <span class="ml-tab ml-tab--active"><span class="ml-tab-dot ml-dot--active"></span> {{ $t('leave.active') }}</span>
                    </template>
                </a-tab-pane>
                <a-tab-pane key="USED">
                    <template #tab>
                        <span class="ml-tab ml-tab--used"><span class="ml-tab-dot ml-dot--used"></span> {{ $t('leave.used') }}</span>
                    </template>
                </a-tab-pane>
                <a-tab-pane key="EXPIRED">
                    <template #tab>
                        <span class="ml-tab ml-tab--expired"><span class="ml-tab-dot ml-dot--expired"></span> {{ $t('leave.expired') }}</span>
                    </template>
                </a-tab-pane>
                <a-tab-pane key="">
                    <template #tab>
                        <span class="ml-tab">📋 {{ $t('leave.all_records') }}</span>
                    </template>
                </a-tab-pane>
            </a-tabs>

            <!-- Detail Table -->
            <a-table
                :columns="detailColumns"
                :row-key="(record) => record.xid"
                :data-source="table.data"
                :pagination="table.pagination"
                :loading="table.loading"
                @change="handleTableChange"
                bordered
                size="middle"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'credited_date'">
                        <strong>{{ formatCreditedMonth(record.credited_date) }}</strong>
                    </template>
                    <template v-if="column.dataIndex === 'status'">
                        <a-tag :color="getStatusColor(record.status)" style="font-weight:600">
                            {{ record.status }}
                        </a-tag>
                    </template>
                    <template v-if="column.dataIndex === 'used_date'">
                        {{ record.used_date ? formatDate(record.used_date) : "—" }}
                    </template>
                    <template v-if="column.dataIndex === 'used_in_leave'">
                        <span v-if="record.used_in_leave">
                            {{ formatDate(record.used_in_leave.start_date) }} → {{ formatDate(record.used_in_leave.end_date) }}
                        </span>
                        <span v-else style="color:#bbb">—</span>
                    </template>
                </template>
            </a-table>
        </div>

        <!-- ═══════════════════════════════════════════════════
             OVERVIEW TABLE  (no employee selected)
        ════════════════════════════════════════════════════ -->
        <div v-else>
            <div class="ml-overview-hint">
                <InfoCircleOutlined style="margin-right: 6px;" />
                {{ $t('leave.showing_all_employees_hint') }}
            </div>

            <a-table
                :columns="overviewColumns"
                :row-key="(record) => record.xid"
                :data-source="overviewData"
                :loading="overviewLoading"
                :pagination="{ pageSize: 25, showSizeChanger: false }"
                bordered
                size="middle"
                :custom-row="(record) => ({
                    style: { cursor: 'pointer' },
                    onClick: () => selectEmployee(record),
                })"
            >
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'active_count'">
                        <span v-if="record.active_count > 0" class="ml-badge ml-badge--active">{{ record.active_count }}</span>
                        <span v-else class="ml-badge ml-badge--zero">0</span>
                    </template>
                    <template v-if="column.dataIndex === 'used_count'">
                        <span class="ml-badge ml-badge--used">{{ record.used_count }}</span>
                    </template>
                    <template v-if="column.dataIndex === 'expired_count'">
                        <span v-if="record.expired_count > 0" class="ml-badge ml-badge--expired">{{ record.expired_count }}</span>
                        <span v-else class="ml-badge ml-badge--zero">0</span>
                    </template>
                    <template v-if="column.dataIndex === 'action'">
                        <a-button type="link" size="small" @click.stop="selectEmployee(record)">
                            {{ $t('leave.view_history') }} →
                        </a-button>
                    </template>
                </template>
            </a-table>
        </div>

            </a-tab-pane>

            <!-- ═══════════════════════════════════════════════════
                 SETTINGS TAB
            ════════════════════════════════════════════════════ -->
            <a-tab-pane key="settings" :tab="$t('common.settings')" v-if="permsArray.includes('admin') || permsArray.includes('leaves_edit')">
                <MonthlyLeaveSettings />
            </a-tab-pane>
        </a-tabs>

    </admin-page-table-content>
</template>

<script>
import { onMounted, ref, computed } from "vue";
import {
    SyncOutlined,
    CheckCircleFilled,
    ClockCircleFilled,
    CloseCircleFilled,
    CalendarFilled,
    InfoCircleOutlined,
    MinusCircleOutlined,
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { notification } from "ant-design-vue";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import UserInfo from "../../../../common/components/user/UserInfo.vue";
import UserListDisplay from "../../../../common/components/user/UserListDisplay.vue";
import MonthlyLeaveSettings from "./MonthlyLeaveSettings.vue";

export default {
    components: {
        SyncOutlined,
        CheckCircleFilled,
        ClockCircleFilled,
        CloseCircleFilled,
        CalendarFilled,
        InfoCircleOutlined,
        MinusCircleOutlined,
        UserInfo,
        UserListDisplay,
        MonthlyLeaveSettings,
    },
    setup() {
        const { t } = useI18n();
        const { permsArray, formatDate, dayjs } = common();
        const crudVariables = crud();

        const processLoading   = ref(false);
        const summaryLoading   = ref(false);
        const overviewLoading  = ref(false);
        const allUsers         = ref([]);
        const overviewData     = ref([]);
        const activeTab        = ref("leaves");

        const summary = ref({
            active_count:         0,
            used_count:           0,
            expired_count:        0,
            current_month_leave:  null,
            carry_forward_leaves: [],
            expired_leaves:       [],
            next_credit_date:     "—",
            last_used_date:       null,
            info_message:         "",
            cycle_breakdown:      null,
        });

        const filters = ref({
            user_id: undefined,
            status: "ACTIVE", // default tab
        });

        // ── Columns ────────────────────────────────────────────────────────────

        const detailColumns = computed(() => {
            const cols = [
                { title: t("leave.month"), dataIndex: "credited_date", width: 180 },
            ];

            if (filters.value.status === "USED") {
                cols.push({ title: t("leave.used_date"),   dataIndex: "used_date",     width: 140 });
                cols.push({ title: t("leave.leave_period"), dataIndex: "used_in_leave", width: 220 });
            } else if (filters.value.status === "" || filters.value.status === "ACTIVE" || filters.value.status === "EXPIRED") {
                cols.push({ title: t("leave.status"), dataIndex: "status", width: 120 });
                if (filters.value.status === "") {
                    cols.push({ title: t("leave.used_date"),   dataIndex: "used_date",     width: 140 });
                    cols.push({ title: t("leave.leave_period"), dataIndex: "used_in_leave", width: 220 });
                }
            }

            return cols;
        });

        const overviewColumns = computed(() => [
            { title: t("leave.employee"),          dataIndex: "employee_name", key: "employee_name" },
            { title: "🟢 " + t("leave.available"),      dataIndex: "active_count",  key: "active_count",  align: "center", width: 120 },
            { title: "🔵 " + t("leave.used"),           dataIndex: "used_count",    key: "used_count",    align: "center", width: 100 },
            { title: "🔴 " + t("leave.expired"),        dataIndex: "expired_count", key: "expired_count", align: "center", width: 110 },
            { title: "",                  dataIndex: "action",        key: "action",        align: "center", width: 130 },
        ]);

        // ── Data fetchers ──────────────────────────────────────────────────────

        const fetchSummary = async () => {
            if (!filters.value.user_id) return;
            summaryLoading.value = true;
            try {
                const res = await axiosAdmin.get("employee-monthly-leaves/summary", {
                    params: { user_id: filters.value.user_id },
                });
                summary.value = res.data;
            } catch (_) {
                // silent
            } finally {
                summaryLoading.value = false;
            }
        };

        const fetchOverview = async () => {
            overviewLoading.value = true;
            try {
                const res = await axiosAdmin.get("employee-monthly-leaves/employee-summary");
                overviewData.value = res.data;
            } catch (_) {
                // silent
            } finally {
                overviewLoading.value = false;
            }
        };

        // ── Table URL ──────────────────────────────────────────────────────────

        const setUrlData = () => {
            if (!filters.value.user_id) return;

            const statusFilter = {};
            if (filters.value.status) {
                statusFilter.status = filters.value.status;
            }

            crudVariables.tableUrl.value = {
                url: "employee-monthly-leaves?fields=id,xid,employee_id,x_employee_id,credited_date,status,used_date,used_in_leave_id,x_used_in_leave_id,used_in_leave{id,xid,start_date,end_date}",
                filters: statusFilter,
                extraFilters: { user_id: filters.value.user_id },
            };

            crudVariables.fetch({ page: 1 });
        };

        // ── Interactions ───────────────────────────────────────────────────────

        const onUserChange = () => {
            filters.value.status = "ACTIVE";
            if (filters.value.user_id) {
                fetchSummary();
                setUrlData();
            } else {
                fetchOverview();
            }
        };

        const selectEmployee = (record) => {
            filters.value.user_id = record.xid;
            onUserChange();
        };

        const processMonthlyLeaves = () => {
            processLoading.value = true;
            axiosAdmin
                .post("employee-monthly-leaves/process", { user_id: filters.value.user_id })
                .then((response) => {
                    processLoading.value = false;
                    notification.success({
                        message: t("common.success"),
                        description: response.data.message,
                        placement: "bottomRight",
                    });
                    if (filters.value.user_id) {
                        fetchSummary();
                        setUrlData();
                    } else {
                        fetchOverview();
                    }
                })
                .catch(() => {
                    processLoading.value = false;
                });
        };

        // ── Helpers ────────────────────────────────────────────────────────────

        const getStatusColor = (status) => {
            if (status === "ACTIVE")  return "green";
            if (status === "USED")    return "blue";
            return "red";
        };

        const formatCreditedMonth = (date) => {
            if (!date) return "";
            return dayjs(date).format("MMMM YYYY");
        };

        const getCycleRowClass = (status) => {
            if (status === "ACTIVE")  return "ml-bp-row--active";
            if (status === "USED")    return "ml-bp-row--used";
            if (status === "EXPIRED") return "ml-bp-row--expired";
            return "ml-bp-row--notcredited";
        };

        const getCycleIcon = (status) => {
            if (status === "ACTIVE")  return "CheckCircleFilled";
            if (status === "USED")    return "ClockCircleFilled";
            if (status === "EXPIRED") return "CloseCircleFilled";
            return "MinusCircleOutlined";
        };

        const getCycleIconClass = (status) => {
            if (status === "ACTIVE")  return "ml-bp-icon ml-bp-icon--active";
            if (status === "USED")    return "ml-bp-icon ml-bp-icon--used";
            if (status === "EXPIRED") return "ml-bp-icon ml-bp-icon--expired";
            return "ml-bp-icon ml-bp-icon--minus";
        };

        const getCycleMonthClass = (status) => {
            if (status === "EXPIRED" || status === "NOT_CREDITED") return "ml-bp-month--muted";
            return "";
        };

        const getStatusTagClass = (status) => {
            if (status === "ACTIVE")  return "ml-status-tag--active";
            if (status === "USED")    return "ml-status-tag--used";
            if (status === "EXPIRED") return "ml-status-tag--expired";
            return "ml-status-tag--notcredited";
        };

        const getStatusText = (item) => {
            if (item.status === "ACTIVE") return t("leave.available");
            if (item.status === "USED") {
                return item.used_date ? t("leave.used_on") + ` ${item.used_date}` : t("leave.used");
            }
            if (item.status === "EXPIRED") return t("leave.expired");
            return t("leave.not_credited");
        };

        // ── Mount ──────────────────────────────────────────────────────────────

        const getCycleTagClass = (key) => {
            if (key === 'current') return 'ml-bp-tag--current';
            if (key === 'prev') return 'ml-bp-tag--prev';
            if (key === 'prev2') return 'ml-bp-tag--prev2';
            return 'ml-bp-tag--prev-more';
        };

        onMounted(() => {
            axiosAdmin.get("users?limit=10000&fields=id,xid,name,profile_image,profile_image_url").then((res) => {
                allUsers.value = res.data;
            });
            fetchOverview();
        });

        return {
            filters,
            allUsers,
            processLoading,
            summaryLoading,
            overviewLoading,
            summary,
            overviewData,
            overviewColumns,
            detailColumns,
            permsArray,
            formatDate,
            formatCreditedMonth,
            getStatusColor,
            setUrlData,
            onUserChange,
            selectEmployee,
            processMonthlyLeaves,
            getCycleRowClass,
            getCycleIcon,
            getCycleIconClass,
            getCycleMonthClass,
            getStatusTagClass,
            getStatusText,
            getCycleTagClass,
            activeTab,
            onTabChange: (key) => {
                if (key === "leaves" && filters.value.user_id) {
                    setUrlData();
                }
            },

            table: crudVariables.table,
            handleTableChange: crudVariables.handleTableChange,
        };
    },
};
</script>

<style scoped>
/* ── Summary Cards ── */
.ml-summary-row {
    margin-bottom: 20px;
}

.ml-stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 20px;
    border-radius: 12px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-left: 5px solid transparent;
    background: var(--card-bg, #fff);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
}

.ml-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.ml-stat-card--active  { border-left-color: #52c41a; }
.ml-stat-card--used    { border-left-color: #1890ff; }
.ml-stat-card--expired { border-left-color: #ff4d4f; }
.ml-stat-card--next    { border-left-color: #7b2a72; cursor: default; }

.ml-stat-icon {
    font-size: 30px;
    flex-shrink: 0;
}

.ml-stat-card--active  .ml-stat-icon { color: #52c41a; }
.ml-stat-card--used    .ml-stat-icon { color: #1890ff; }
.ml-stat-card--expired .ml-stat-icon { color: #ff4d4f; }
.ml-stat-card--next    .ml-stat-icon { color: #7b2a72; }

.ml-stat-body {
    display: flex;
    flex-direction: column;
}

.ml-stat-value {
    font-size: 34px;
    font-weight: 800;
    line-height: 1.1;
}

.ml-stat-value--date {
    font-size: 18px;
    font-weight: 700;
    color: #7b2a72;
}

.ml-stat-label {
    font-size: 13px;
    color: #888;
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ml-stat-card--active  .ml-stat-value { color: #52c41a; }
.ml-stat-card--used    .ml-stat-value { color: #1890ff; }
.ml-stat-card--expired .ml-stat-value { color: #ff4d4f; }

/* ── Status Tabs ── */
.ml-tabs {
    margin-bottom: 4px;
}

.ml-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.ml-tab-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.ml-dot--active  { background: #52c41a; }
.ml-dot--used    { background: #1890ff; }
.ml-dot--expired { background: #ff4d4f; }

/* ── Overview Hint ── */
.ml-overview-hint {
    padding: 10px 14px;
    background: #f5f0ff;
    border-radius: 8px;
    color: #555;
    font-size: 13px;
    margin-bottom: 16px;
    border-left: 4px solid #7b2a72;
}

/* ── Overview Badges ── */
.ml-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 28px;
    padding: 0 10px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 14px;
}

.ml-badge--active  { background: #f6ffed; color: #52c41a; border: 1.5px solid #b7eb8f; }
.ml-badge--used    { background: #e6f4ff; color: #1890ff; border: 1.5px solid #91caff; }
.ml-badge--expired { background: #fff1f0; color: #ff4d4f; border: 1.5px solid #ffa39e; }
.ml-badge--zero    { background: #fafafa; color: #bbb;    border: 1.5px solid #e8e8e8; }

/* ── Breakdown Panel ── */
.ml-breakdown-panel {
    background: #fdfbff;
    border: 1.5px solid #ede0ff;
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
}

.ml-bp-section { padding-right: 8px; }

.ml-bp-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #aaa;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ml-bp-label--expired { color: #d9d9d9; }

.ml-bp-carry-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #7b2a72;
    color: #fff;
    border-radius: 8px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    font-size: 10px;
}

.ml-bp-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 5px;
    border: 1px solid transparent;
}

.ml-bp-row--active  { background: #f6ffed; border-color: #d9f7be; }
.ml-bp-row--carry   { background: #f9f0ff; border-color: #efdbff; }
.ml-bp-row--used    { background: #e6f7ff; border-color: #91d5ff; }
.ml-bp-row--expired { background: #fafafa; border-color: #f0f0f0; }
.ml-bp-row--notcredited { background: #fafafa; border-color: #f0f0f0; opacity: 0.7; }

.ml-bp-icon { font-size: 15px; flex-shrink: 0; }
.ml-bp-icon--active  { color: #52c41a; }
.ml-bp-icon--carry   { color: #9254de; }
.ml-bp-icon--used    { color: #1890ff; }
.ml-bp-icon--expired { color: #d9d9d9; }
.ml-bp-icon--minus   { color: #bfbfbf; }

.ml-bp-month--muted {
    color: #999;
    font-weight: 500;
}

.ml-bp-meta {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ml-bp-tag--prev {
    background: #f9f0ff;
    color: #9254de;
    border: 1px solid #d3adf7;
}

.ml-bp-tag--prev2 {
    background: #fff0f6;
    color: #eb2f96;
    border: 1px solid #ffadd2;
}

.ml-status-tag {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: uppercase;
}

.ml-status-tag--active {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.ml-status-tag--used {
    background: #e6f7ff;
    color: #1890ff;
    border: 1px solid #91d5ff;
}

.ml-status-tag--expired {
    background: #fff2e8;
    color: #fa541c;
    border: 1px solid #ffbb96;
}

.ml-status-tag--notcredited {
    background: #f5f5f5;
    color: #bfbfbf;
    border: 1px solid #d9d9d9;
}

.ml-bp-month {
    flex: 1;
    font-weight: 600;
    font-size: 14px;
}

.ml-bp-month--expired {
    color: #ccc;
    font-weight: 400;
    text-decoration: line-through;
}

.ml-bp-tag {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.ml-bp-tag--current { background: #e6f4ff; color: #1890ff; border: 1px solid #91caff; }
.ml-bp-tag--carry   { background: #f9f0ff; color: #9254de; border: 1px solid #d3adf7; }
.ml-bp-tag--prev-more { background: #f5f5f5; color: #666; border: 1px solid #d9d9d9; }

.ml-bp-no-carry {
    font-size: 12px;
    color: #ccc;
    padding: 4px 0;
}

.ml-bp-info {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.6;
    margin-bottom: 12px;
}

.ml-bp-info--warn {
    background: #fffbe6;
    border: 1.5px solid #ffe58f;
    color: #7c4a03;
}

.ml-bp-info--info {
    background: #e6f7ff;
    border: 1.5px solid #91d5ff;
    color: #003a8c;
}

.ml-bp-info-icon { font-size: 14px; flex-shrink: 0; margin-top: 2px; }
.ml-bp-info--warn .ml-bp-info-icon { color: #fa8c16; }
.ml-bp-info--info .ml-bp-info-icon { color: #1890ff; }

.ml-bp-expired-list { margin-top: 4px; }
</style>
