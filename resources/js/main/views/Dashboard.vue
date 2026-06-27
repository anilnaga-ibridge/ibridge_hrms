<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.dashboard`)" style="padding: 0px" />
        </template>
    </AdminPageHeader>

    <div class="dashboard-bento">
        <UpdateAppAlert />

        <div class="bento-hero">
            <div class="bento-hero-clock">
                <a-card class="clock-in" :bodyStyle="{ padding: '20px' }">
                    <template #title>
                        <ClockCircleOutlined />
                        {{ $t("hrm_dashboard.today_attendance") }}
                    </template>
                    <MarkTodayAttendance />
                </a-card>
            </div>
            <div class="bento-hero-stats">
                <div class="stat-grid">
                    <StateWidget>
                        <template #image>
                            <TeamOutlined style="color: #fff; font-size: 24px" />
                        </template>
                        <template #description>
                            <h2 v-if="responseData.stateData">
                                {{ responseData.stateData.totalEmployees }}
                            </h2>
                            <p class="antd-theme">
                                {{ $t("dashboard.total_employees") }}
                            </p>
                        </template>
                    </StateWidget>
                    <StateWidget>
                        <template #image>
                            <SmileOutlined style="color: #fff; font-size: 24px" />
                        </template>
                        <template #description>
                            <h2 v-if="responseData.stateData">
                                {{ responseData.stateData.totalActiveEmployee }}
                            </h2>
                            <p class="antd-theme">
                                {{ $t("dashboard.total_active_employees") }}
                            </p>
                        </template>
                    </StateWidget>
                    <StateWidget>
                        <template #image>
                            <TagOutlined style="color: #fff; font-size: 24px" />
                        </template>
                        <template #description>
                            <h2 v-if="responseData.stateData">
                                {{ responseData.stateData.totalInactiveEmployees }}
                            </h2>
                            <p class="antd-theme">
                                {{ $t("dashboard.total_inactive_employees") }}
                            </p>
                        </template>
                    </StateWidget>
                    <StateWidget>
                        <template #image>
                            <BankOutlined style="color: #fff; font-size: 24px" />
                        </template>
                        <template #description>
                            <h2 v-if="responseData.stateData">
                                {{ responseData.todaysAttendence.employee_under_you }}
                            </h2>
                            <p class="antd-theme">
                                {{ $t("dashboard.employee_under_you") }}
                            </p>
                        </template>
                    </StateWidget>
                </div>
            </div>
        </div>

        <div class="bento-row-3">
            <div class="bento-item" v-if="willSubscriptionModuleVisible('leaves')">
                <PendingLeaves />
            </div>
            <div class="bento-item bento-chart">
                <a-card>
                    <template #title>
                        <ClockCircleOutlined />
                        {{ $t("hrm_dashboard.today_attendance") }}
                    </template>
                    <template #extra>
                        <a-space>
                            <a-dropdown>
                                <template #overlay>
                                    <a-menu>
                                        <a-menu-item key="yesterday" @click="selectedFilter = 'yesterday'; dateSelectorClicked('yesterday')">
                                            {{ $t("hrm_dashboard.yesterday") }}
                                        </a-menu-item>
                                        <a-menu-item key="today" @click="selectedFilter = 'today'; dateSelectorClicked('today')">
                                            {{ $t("hrm_dashboard.today") }}
                                        </a-menu-item>
                                        <a-menu-item key="this_week" @click="selectedFilter = 'this_week'; dateSelectorClicked('week')">
                                            {{ $t("hrm_dashboard.this_week") }}
                                        </a-menu-item>
                                        <a-menu-item key="this_month" @click="selectedFilter = 'this_month'; dateSelectorClicked('month')">
                                            {{ $t("hrm_dashboard.this_month") }}
                                        </a-menu-item>
                                        <a-menu-item key="this_year" @click="selectedFilter = 'this_year'; dateSelectorClicked('year')">
                                            {{ $t("hrm_dashboard.this_year") }}
                                        </a-menu-item>
                                    </a-menu>
                                </template>
                                <a-button>
                                    <template #icon><CalendarOutlined /></template>
                                    {{ $t(`hrm_dashboard.${selectedFilter}`) }}
                                </a-button>
                            </a-dropdown>
                        </a-space>
                    </template>
                    <DoughChart :data="responseData" />
                </a-card>
            </div>
            <div class="bento-item">
                <ClockInOut :data="responseData" @fetchClockInData="fetchClockInData" />
            </div>
        </div>

        <div class="bento-full">
            <BirthdayWishCard :data="responseData" />
        </div>

        <div class="bento-row-3">
            <div class="bento-item">
                <Birthday :data="responseData" @employeeBirthDayData="employeeBirthDayData" />
            </div>
            <div class="bento-item">
                <WorkAnniversay :data="responseData" @employeeAnniversaryData="employeeAnniversaryData" />
            </div>
            <div class="bento-item">
                <EmployeeByDepartment :data="responseData" />
            </div>
        </div>

        <div class="bento-row-3">
            <div class="bento-item" v-if="willSubscriptionModuleVisible('holidays')">
                <WeekendHoliday :data="responseData" @fetchYearData="fetchWeekend" />
            </div>
            <div class="bento-item">
                <EmployeeWorkStatus :data="responseData" />
            </div>
            <div class="bento-item" v-if="willSubscriptionModuleVisible('appreciations')">
                <Performance :data="responseData" @employeeAppriciationData="employeeAppriciationData" />
            </div>
        </div>

        <div class="bento-row-2">
            <div class="bento-item bento-wide" v-if="willSubscriptionModuleVisible('appreciations')">
                <RecentAchievements :data="responseData" />
            </div>
            <div class="bento-item" v-if="willSubscriptionModuleVisible('payrolls')">
                <IncrementPromotion :data="responseData" @employeeIncrementData="employeeIncrementData" />
            </div>
        </div>

        <div class="bento-full">
            <EmployeeStatus :data="responseData" @employeeStatusData="employeeStatusData" />
        </div>
    </div>
</template>

<script>
import { onMounted, reactive, ref, watch } from "vue";
import UpdateAppAlert from "./UpdateAppAlert.vue";
import AdminPageHeader from "../../common/layouts/AdminPageHeader.vue";
import {
    ClockCircleOutlined,
    TeamOutlined,
    TagOutlined,
    SmileOutlined,
    BankOutlined,
    CalendarOutlined,
} from "@ant-design/icons-vue";
import { useRouter } from "vue-router";
import common from "../../common/composable/common";
import { map } from "lodash-es";
import MarkTodayAttendance from "./admin-dashboard/MarkTodayAttendance.vue";
import PendingLeaves from "./admin-dashboard/PendingLeaves.vue";
import TodayAttendance from "./admin-dashboard/TodayAttendance.vue";
import StateWidget from "../../common/components/common/card/StateWidget.vue";
import DoughChart from "../../common/components/common/card/DoughChart.vue";
import EmployeeStatus from "./admin-dashboard/EmployeeStatus.vue";
import ClockInOut from "./admin-dashboard/ClockInOut.vue";
import EmployeeByDepartment from "./admin-dashboard/EmployeeByDepartment.vue";
import Birthday from "./admin-dashboard/Birthday.vue";
import BirthdayWishCard from "../../common/components/common/card/BirthdayWishCard.vue";
import DateRangePicker from "../../common/components/common/calendar/DateRangePicker.vue";
import WorkAnniversay from "./admin-dashboard/WorkAnniversay.vue";
import WeekendHoliday from "./admin-dashboard/WeekendHoliday.vue";
import EmployeeWorkStatus from "./admin-dashboard/EmployeeWorkStatus.vue";
import Performance from "./admin-dashboard/performance.vue";
import IncrementPromotion from "./admin-dashboard/IncrementPromotion.vue";
import RecentAchievements from "./self/self-dashboard/RecentAchievements.vue";

export default {
    components: {
        UpdateAppAlert,
        AdminPageHeader,
        ClockCircleOutlined,
        TeamOutlined,
        BankOutlined,
        TagOutlined,
        SmileOutlined,
        MarkTodayAttendance,
        PendingLeaves,
        TodayAttendance,
        StateWidget,
        DoughChart,
        EmployeeStatus,
        ClockInOut,
        EmployeeByDepartment,
        Birthday,
        BirthdayWishCard,
        DateRangePicker,
        CalendarOutlined,
        WorkAnniversay,
        WeekendHoliday,
        EmployeeWorkStatus,
        IncrementPromotion,
        Performance,
        RecentAchievements,
    },
    setup(props) {
        const {
            permsArray,
            dayjs,
            selectedWarehouse,
            user,
            willSubscriptionModuleVisible,
        } = common();
        const router = useRouter();
        const responseData = ref([]);
        const filters = reactive({
            dates: [dayjs().format("YYYY-MM-DD"), dayjs().format("YYYY-MM-DD")],
            year: dayjs().year(),
            userId: undefined,
            filterMonthYear: undefined,
            date: undefined,
            status_date: [
                dayjs().startOf("year").format("YYYY-MM-DD"),
                dayjs().endOf("year").format("YYYY-MM-DD"),
            ],
            type: "all",
            clock_in_dates: [
                dayjs().format("YYYY-MM-DD"),
                dayjs().format("YYYY-MM-DD"),
            ],
        });
        const activeDateSelector = ref("");
        const serachDateRangePicker = ref(null);
        const selectedFilter = ref("today");

        onMounted(() => {
            const dashboardPromise = axiosAdmin.post("dashboard", filters);
            Promise.all([dashboardPromise]).then(([dashboardResponse]) => {
                responseData.value = {
                    ...dashboardResponse.data,
                    ...responseData.value,
                };
            });
        });

        const dateSelectorClicked = (selectedType) => {
            let selectedKey;
            let selectedFilterValue;

            if (typeof selectedType === "object" && selectedType !== null) {
                selectedKey = selectedType.key;
                selectedFilterValue = selectedType.selectedFilter;
            } else if (typeof selectedType === "string") {
                selectedKey = selectedType;
            }

            if (selectedFilterValue !== undefined) {
                selectedFilter.value = selectedFilterValue;
            }

            activeDateSelector.value = selectedKey;

            const dateRanges = {
                today: [dayjs(), dayjs()],
                yesterday: [
                    dayjs().subtract(1, "day").startOf("day"),
                    dayjs().subtract(1, "day").endOf("day"),
                ],
                week: [dayjs().subtract(7, "day"), dayjs()],
                month: [dayjs().startOf("month"), dayjs()],
                year: [dayjs().startOf("year"), dayjs()],
            };

            if (dateRanges[selectedKey]) {
                serachDateRangePicker.value.setDatePicker(
                    dateRanges[selectedKey]
                );
                filters.dates = map(dateRanges[selectedKey], (date) =>
                    date.format("YYYY-MM-DD")
                );
            }
            filters.type = "attendance";
        };

        const fetchWeekend = (weekendObj) => {
            filters.year = weekendObj.filterMonthYear;
            filters.type = weekendObj.type;
        };

        const fetchClockInData = (clockInObj) => {
            filters.clock_in_dates = clockInObj.clockInDateRange;
            filters.type = clockInObj.type;
        };

        const employeeStatusData = (statusData) => {
            filters.userId = statusData.xid;
            filters.status_date = statusData.status_date;
            filters.type = statusData.type;
        };

        const employeeBirthDayData = (birthdayData) => {
            filters.userId = birthdayData.xid;
            filters.date = birthdayData.date;
            filters.type = birthdayData.type;
        };
        const employeeAnniversaryData = (anniversaryData) => {
            filters.userId = anniversaryData.xid;
            filters.date = anniversaryData.date;
            filters.type = anniversaryData.type;
        };

        const employeeIncrementData = (increamentData) => {
            filters.userId = increamentData.user_id;
            filters.type = increamentData.type;
            filters.filterMonthYear = increamentData.filterMonthYear;
        };

        const employeeAppriciationData = (appreciationData) => {
            filters.userId = appreciationData.user_id;
            filters.type = appreciationData.type;
            filters.filterMonthYear = appreciationData.filterMonthYear;
        };

        watch(
            filters,
            (newVal, oldVal) => {
                axiosAdmin.post("dashboard", newVal).then((response) => {
                    responseData.value = {
                        ...responseData.value,
                        ...response.data,
                    };
                });
            },
            { deep: true }
        );

        return {
            filters,
            permsArray,
            responseData,
            dateSelectorClicked,
            serachDateRangePicker,
            selectedFilter,
            fetchWeekend,
            employeeStatusData,
            fetchClockInData,
            employeeIncrementData,
            employeeAppriciationData,
            employeeBirthDayData,
            employeeAnniversaryData,
            willSubscriptionModuleVisible,
        };
    },
};
</script>

<style>
.dashboard-bento {
    max-width: 1440px;
    margin: 0 auto;
    padding: 16px 20px 32px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.bento-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.bento-hero-clock {
    grid-column: 1;
}

.bento-hero-stats {
    grid-column: 2;
    display: flex;
    align-items: stretch;
}

.stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    width: 100%;
}

.bento-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 18px;
}

.bento-row-2 {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 18px;
}

.bento-full {
    grid-column: 1 / -1;
}

/* Cards inside bento items */
.bento-item > .ant-card,
.bento-hero-clock > .ant-card,
.bento-full > .ant-card {
    height: 100%;
    border-radius: 16px;
    background: #f0f2f5;
    box-shadow: 6px 6px 14px #d1d9e6, -6px -6px 14px #ffffff;
    border: none;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.bento-item > .ant-card:hover,
.bento-hero-clock > .ant-card:hover {
    box-shadow: 3px 3px 8px #d1d9e6, -3px -3px 8px #ffffff;
}
.bento-item .ant-card-body,
.bento-hero-clock .ant-card-body,
.bento-full .ant-card-body {
    background: transparent;
}
.bento-item .ant-card-head,
.bento-hero-clock .ant-card-head,
.bento-full .ant-card-head {
    background: transparent;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    font-weight: 600;
    padding: 14px 20px;
    min-height: 48px;
}
.bento-item .ant-card-head-title,
.bento-hero-clock .ant-card-head-title {
    font-size: 14px;
    letter-spacing: 0.3px;
    color: #4a4a6a;
}

.clock-in .ant-result {
    margin-bottom: -24px;
    text-align: center;
    margin-top: -22px;
}
.antd-theme {
    color: var(--ant-text-color);
}

/* Inner sub-card overrides */
.bento-item .card-container .ant-card,
.bento-item .top-performer .ant-card,
.bento-item .employee-info-card.ant-card {
    border-radius: 12px;
    background: #f0f2f5;
    box-shadow: 4px 4px 8px #d1d9e6, -4px -4px 8px #ffffff;
    border: none;
    transition: all 0.2s ease;
}
.bento-item .pending-leave-hrm .ant-card,
.bento-item .employee-status-card .ant-card {
    background: transparent;
    box-shadow: none;
}
.bento-item .performance-card.ant-card,
.bento-item .chart-card.ant-card {
    height: 99%;
}
.bento-item .employee-details-card.ant-card {
    border-radius: 10px;
    background: #f0f2f5;
    box-shadow: inset 2px 2px 5px #d1d9e6, inset -2px -2px 5px #ffffff;
    border: none;
    margin-top: 8px;
}

@media (max-width: 992px) {
    .bento-hero {
        grid-template-columns: 1fr;
    }
    .bento-hero-clock {
        grid-column: 1;
    }
    .bento-hero-stats {
        grid-column: 1;
    }
    .bento-row-3 {
        grid-template-columns: 1fr;
    }
    .bento-row-2 {
        grid-template-columns: 1fr;
    }
    .stat-grid {
        grid-template-columns: 1fr 1fr;
    }
    .dashboard-bento {
        padding: 12px 14px 24px;
    }
}
</style>