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

    <admin-page-table-content>

        <!-- ══════════════════════════════════════════
             MAIN BALANCE CARD
        ══════════════════════════════════════════ -->
        <a-skeleton :loading="summaryLoading" active :paragraph="{ rows: 7 }">
            <div class="slf-card">

                <!-- Card Header -->
                <div class="slf-card-header">
                    <div class="slf-header-title">
                        <CalendarFilled class="slf-header-icon" />
                        {{ $t('leave.monthly_leave_balance') }}
                    </div>
                    <div class="slf-available-pill" :class="summary.active_count > 0 ? 'slf-pill--positive' : 'slf-pill--zero'">
                        <span class="slf-pill-number">{{ summary.active_count }}</span>
                        <span class="slf-pill-label">{{ $t('leave.available') }}</span>
                    </div>
                </div>

                <div class="slf-next-date">
                    {{ $t('leave.next_credit_date') }}:
                    <strong>{{ summary.next_credit_date }}</strong>
                    <span v-if="summary.monthly_quota" style="margin-left: 8px; color: #8c8c8c; font-weight: normal; font-size: 13px;">
                        ({{ $t('leave.monthly_leaves') }}: <strong>{{ summary.monthly_quota }}</strong>)
                    </span>
                </div>

                <div class="slf-divider"></div>

                <!-- ══════════════════════════════════════════
                     CYCLE STATUS BREAKDOWN
                ══════════════════════════════════════════ -->
                <div v-if="summary.cycle_breakdown" class="slf-section">
                    <div class="slf-section-label">{{ $t('leave.current_month_cycle_status', [Object.keys(summary.cycle_breakdown).length]) }}</div>
                    
                    <div
                        v-for="(item, key) in summary.cycle_breakdown"
                        :key="key"
                        class="slf-leave-row"
                        :class="getCycleRowClass(item.status)"
                    >
                        <component :is="getCycleIcon(item.status)" :class="getCycleIconClass(item.status)" />
                        <span class="slf-month-name" :class="getCycleMonthClass(item.status)">
                            {{ item.month_name }}
                        </span>
                        <div class="slf-cycle-meta">
                            <span class="slf-badge" :class="getCycleTagClass(key)">{{ item.label }}</span>
                            <span class="slf-status-tag" :class="getStatusTagClass(item.status)">
                                {{ getStatusText(item) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ── Info Alert ── -->
                <div v-if="summary.info_message" class="slf-info-box" :class="summary.carry_forward_leaves.length > 0 ? 'slf-info-box--warn' : 'slf-info-box--info'">
                    <InfoCircleOutlined class="slf-info-icon" />
                    <span>{{ summary.info_message }}</span>
                </div>

                <div class="slf-divider"></div>

                <!-- ── Recently Expired ── -->
                <div v-if="summary.expired_leaves.length > 0" class="slf-section">
                    <div class="slf-section-label slf-section-label--expired">
                        {{ $t('leave.recently_expired_leaves') }}
                    </div>
                    <div
                        v-for="month in summary.expired_leaves"
                        :key="month"
                        class="slf-leave-row slf-leave-row--expired"
                    >
                        <CloseCircleFilled class="slf-icon-x" />
                        <span class="slf-month-name slf-month-name--expired">{{ month }}</span>
                    </div>
                </div>

                <!-- ── Footer Stats ── -->
                <div class="slf-footer-stats">
                    <div class="slf-footer-stat" v-if="summary.used_count > 0">
                        <span class="slf-footer-label">{{ $t('leave.used_leaves') }}</span>
                        <span class="slf-footer-value slf-footer-value--used">{{ summary.used_count }}</span>
                    </div>
                    <div class="slf-footer-stat" v-if="summary.last_used_date">
                        <span class="slf-footer-label">{{ $t('leave.last_used_on') }}</span>
                        <span class="slf-footer-value">{{ summary.last_used_date }}</span>
                    </div>
                    <div class="slf-footer-stat" v-if="summary.expired_count > 0">
                        <span class="slf-footer-label">{{ $t('leave.total_expired') }}</span>
                        <span class="slf-footer-value slf-footer-value--expired">{{ summary.expired_count }}</span>
                    </div>
                </div>

            </div>
        </a-skeleton>

        <!-- ══════════════════════════════════════════
             FULL HISTORY (collapsible)
        ══════════════════════════════════════════ -->
        <div class="slf-history-toggle">
            <a-button type="link" class="slf-toggle-btn" @click="showHistory = !showHistory">
                <HistoryOutlined />
                {{ showHistory ? $t('leave.hide_full_history') : $t('leave.view_full_history') }}
                <DownOutlined v-if="!showHistory" />
                <UpOutlined v-else />
            </a-button>
        </div>

        <div v-show="showHistory" class="slf-history-section">
            <a-tabs v-model:activeKey="activeStatus" @change="onStatusChange">
                <a-tab-pane key="ACTIVE">
                    <template #tab><span><span class="slf-dot slf-dot--active"></span> {{ $t('leave.active') }}</span></template>
                </a-tab-pane>
                <a-tab-pane key="USED">
                    <template #tab><span><span class="slf-dot slf-dot--used"></span> {{ $t('leave.used') }}</span></template>
                </a-tab-pane>
                <a-tab-pane key="EXPIRED">
                    <template #tab><span><span class="slf-dot slf-dot--expired"></span> {{ $t('leave.expired') }}</span></template>
                </a-tab-pane>
                <a-tab-pane key="">
                    <template #tab><span>{{ $t('leave.all_records') }}</span></template>
                </a-tab-pane>
            </a-tabs>

            <a-table
                :columns="historyColumns"
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

    </admin-page-table-content>
</template>

<script>
import { onMounted, ref, computed } from "vue";
import {
    CalendarFilled,
    CheckCircleFilled,
    ClockCircleFilled,
    CloseCircleFilled,
    InfoCircleOutlined,
    MinusCircleOutlined,
    HistoryOutlined,
    DownOutlined,
    UpOutlined,
} from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import crud from "../../../../../common/composable/crud";
import common from "../../../../../common/composable/common";

export default {
    components: {
        CalendarFilled,
        CheckCircleFilled,
        ClockCircleFilled,
        CloseCircleFilled,
        InfoCircleOutlined,
        MinusCircleOutlined,
        HistoryOutlined,
        DownOutlined,
        UpOutlined,
    },
    setup() {
        const { t } = useI18n();
        const { formatDate, dayjs } = common();
        const crudVariables = crud();

        const summaryLoading = ref(false);
        const showHistory    = ref(false);
        const activeStatus   = ref("ACTIVE");

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

        // ── History Columns ────────────────────────────────────────────────────

        const historyColumns = computed(() => {
            const base = [{ title: t("leave.month"), dataIndex: "credited_date", width: 180 }];
            if (activeStatus.value === "USED") {
                base.push({ title: t("leave.used_date"),    dataIndex: "used_date",    width: 140 });
                base.push({ title: t("leave.leave_period"), dataIndex: "used_in_leave", width: 220 });
            } else if (activeStatus.value === "") {
                base.push({ title: t("leave.status"),       dataIndex: "status",       width: 120 });
                base.push({ title: t("leave.used_date"),    dataIndex: "used_date",    width: 140 });
                base.push({ title: t("leave.leave_period"), dataIndex: "used_in_leave", width: 220 });
            } else {
                base.push({ title: t("leave.status"), dataIndex: "status", width: 120 });
            }
            return base;
        });

        // ── Fetchers ───────────────────────────────────────────────────────────

        const fetchSummary = async () => {
            summaryLoading.value = true;
            try {
                const res = await axiosAdmin.get("self/employee-monthly-leaves/summary");
                summary.value = res.data;
            } catch (_) {
                // silent
            } finally {
                summaryLoading.value = false;
            }
        };

        const setUrlData = () => {
            const statusFilter = {};
            if (activeStatus.value) {
                statusFilter.status = activeStatus.value;
            }
            crudVariables.tableUrl.value = {
                url: "self/employee-monthly-leaves?fields=id,xid,credited_date,status,used_date,used_in_leave_id,x_used_in_leave_id,used_in_leave{id,xid,start_date,end_date}",
                filters: statusFilter,
                extraFilters: {},
            };
            crudVariables.fetch({ page: 1 });
        };

        const onStatusChange = () => setUrlData();

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

        // ── Mount ──────────────────────────────────────────────────────────────

        onMounted(() => {
            fetchSummary();
            setUrlData();
        });

        const getCycleRowClass = (status) => {
            if (status === "ACTIVE")  return "slf-leave-row--active";
            if (status === "USED")    return "slf-leave-row--used";
            if (status === "EXPIRED") return "slf-leave-row--expired";
            return "slf-leave-row--notcredited";
        };

        const getCycleIcon = (status) => {
            if (status === "ACTIVE")  return "CheckCircleFilled";
            if (status === "USED")    return "ClockCircleFilled";
            if (status === "EXPIRED") return "CloseCircleFilled";
            return "MinusCircleOutlined";
        };

        const getCycleIconClass = (status) => {
            if (status === "ACTIVE")  return "slf-icon-check";
            if (status === "USED")    return "slf-icon-used";
            if (status === "EXPIRED") return "slf-icon-x";
            return "slf-icon-minus";
        };

        const getCycleMonthClass = (status) => {
            if (status === "EXPIRED" || status === "NOT_CREDITED") return "slf-month-name--muted";
            return "";
        };

        const getStatusTagClass = (status) => {
            if (status === "ACTIVE")  return "slf-status-tag--active";
            if (status === "USED")    return "slf-status-tag--used";
            if (status === "EXPIRED") return "slf-status-tag--expired";
            return "slf-status-tag--notcredited";
        };

        const getStatusText = (item) => {
            if (item.status === "ACTIVE") return t("leave.available");
            if (item.status === "USED") {
                return item.used_date ? t("leave.used_on") + ` ${item.used_date}` : t("leave.used");
            }
            if (item.status === "EXPIRED") return t("leave.expired");
            return t("leave.not_credited");
        };

        const getCycleTagClass = (key) => {
            if (key === 'current') return 'slf-badge--current';
            if (key === 'prev') return 'slf-badge--prev';
            if (key === 'prev2') return 'slf-badge--prev2';
            return 'slf-badge--prev-more';
        };

        return {
            summary,
            summaryLoading,
            showHistory,
            activeStatus,
            historyColumns,
            formatDate,
            formatCreditedMonth,
            getStatusColor,
            onStatusChange,
            setUrlData,
            getCycleRowClass,
            getCycleIcon,
            getCycleIconClass,
            getCycleMonthClass,
            getStatusTagClass,
            getStatusText,
            getCycleTagClass,

            table: crudVariables.table,
            handleTableChange: crudVariables.handleTableChange,
        };
    },
};
</script>

<style scoped>
/* ══ Main Card ══════════════════════════════════════════ */
.slf-card {
    background: #fff;
    border: 1.5px solid #ede0ff;
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 20px;
    box-shadow: 0 4px 24px rgba(123, 42, 114, 0.07);
}

/* ── Card Header ── */
.slf-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.slf-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
    color: #3a1a52;
}

.slf-header-icon {
    font-size: 22px;
    color: #7b2a72;
}

.slf-available-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 3px solid;
    padding: 4px 10px;
}

.slf-pill--positive {
    border-color: #52c41a;
    background: #f6ffed;
    box-shadow: 0 0 0 4px rgba(82, 196, 26, 0.12);
}

.slf-pill--zero {
    border-color: #d9d9d9;
    background: #fafafa;
}

.slf-pill-number {
    font-size: 28px;
    font-weight: 900;
    line-height: 1;
    color: #52c41a;
}

.slf-pill--zero .slf-pill-number { color: #bbb; }

.slf-pill-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #52c41a;
    font-weight: 600;
}

.slf-pill--zero .slf-pill-label { color: #bbb; }

.slf-next-date {
    font-size: 13px;
    color: #888;
    margin-top: 4px;
}

.slf-next-date strong {
    color: #7b2a72;
    font-weight: 700;
    font-size: 14px;
}

/* ── Divider ── */
.slf-divider {
    height: 1px;
    background: #f0e6ff;
    margin: 18px 0;
}

/* ── Sections ── */
.slf-section {
    margin-bottom: 16px;
}

.slf-section-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #999;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.slf-section-label--expired { color: #ccc; }

.slf-carry-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #7b2a72;
    color: #fff;
    border-radius: 10px;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    font-size: 11px;
    font-weight: 700;
}

/* ── Leave Rows ── */
.slf-leave-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 6px;
    border: 1.5px solid transparent;
    transition: border-color 0.15s;
}

.slf-leave-row--active {
    background: #f6ffed;
    border-color: #d9f7be;
}

.slf-leave-row--carry {
    background: #f9f0ff;
    border-color: #efdbff;
}

.slf-leave-row--expired {
    background: #fafafa;
    border-color: #f0f0f0;
}

.slf-icon-check {
    font-size: 18px;
    color: #52c41a;
    flex-shrink: 0;
}

.slf-icon-check--carry { color: #9254de; }

.slf-icon-x {
    font-size: 16px;
    color: #d9d9d9;
    flex-shrink: 0;
}

.slf-month-name {
    flex: 1;
    font-weight: 600;
    font-size: 15px;
    color: #2a2a2a;
}

.slf-month-name--expired {
    color: #bbb;
    font-weight: 400;
    font-size: 14px;
    text-decoration: line-through;
}

/* ── Status Badges ── */
.slf-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.slf-badge--current {
    background: #e6f4ff;
    color: #1890ff;
    border: 1px solid #91caff;
}

.slf-badge--prev {
    background: #f9f0ff;
    color: #9254de;
    border: 1px solid #d3adf7;
}

.slf-badge--prev2 {
    background: #fff0f6;
    color: #eb2f96;
    border: 1px solid #ffadd2;
}

.slf-badge--prev-more {
    background: #f5f5f5;
    color: #666;
    border: 1px solid #d9d9d9;
}

.slf-leave-row--used {
    background: #e6f7ff;
    border-color: #91d5ff;
}

.slf-leave-row--notcredited {
    background: #fafafa;
    border-color: #f0f0f0;
    opacity: 0.7;
}

.slf-icon-used {
    font-size: 18px;
    color: #1890ff;
    flex-shrink: 0;
}

.slf-icon-minus {
    font-size: 16px;
    color: #bfbfbf;
    flex-shrink: 0;
}

.slf-month-name--muted {
    color: #999;
    font-weight: 500;
}

.slf-cycle-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.slf-status-tag {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 4px;
    text-transform: uppercase;
}

.slf-status-tag--active {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.slf-status-tag--used {
    background: #e6f7ff;
    color: #1890ff;
    border: 1px solid #91d5ff;
}

.slf-status-tag--expired {
    background: #fff2e8;
    color: #fa541c;
    border: 1px solid #ffbb96;
}

.slf-status-tag--notcredited {
    background: #f5f5f5;
    color: #bfbfbf;
    border: 1px solid #d9d9d9;
}

/* ── No Carry / Empty ── */
.slf-no-carry {
    font-size: 13px;
    color: #bbb;
    padding: 6px 0;
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}

.slf-empty-state {
    padding: 16px 0;
}

.slf-empty-text {
    color: #aaa;
    font-size: 14px;
}

/* ── Info Alert ── */
.slf-info-box {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 14px 16px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.6;
    margin-top: 4px;
    margin-bottom: 4px;
}

.slf-info-box--warn {
    background: #fffbe6;
    border: 1.5px solid #ffe58f;
    color: #7c4a03;
}

.slf-info-box--info {
    background: #e6f7ff;
    border: 1.5px solid #91d5ff;
    color: #003a8c;
}

.slf-info-icon {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 2px;
}

.slf-info-box--warn .slf-info-icon { color: #fa8c16; }
.slf-info-box--info .slf-info-icon { color: #1890ff; }

/* ── Footer Stats ── */
.slf-footer-stats {
    display: flex;
    gap: 28px;
    flex-wrap: wrap;
}

.slf-footer-stat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.slf-footer-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #aaa;
}

.slf-footer-value {
    font-size: 20px;
    font-weight: 800;
    color: #333;
}

.slf-footer-value--used    { color: #1890ff; }
.slf-footer-value--expired { color: #ff4d4f; }

/* ══ History Section ════════════════════════════════════ */
.slf-history-toggle {
    margin-bottom: 8px;
}

.slf-toggle-btn {
    font-size: 14px;
    font-weight: 600;
    color: #7b2a72;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}

.slf-history-section {
    animation: fadeSlideDown 0.25s ease;
}

@keyframes fadeSlideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.slf-dot {
    display: inline-block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin-right: 5px;
    vertical-align: middle;
}

.slf-dot--active  { background: #52c41a; }
.slf-dot--used    { background: #1890ff; }
.slf-dot--expired { background: #ff4d4f; }
</style>
