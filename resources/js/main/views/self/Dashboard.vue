<template>
    <div class="dash-premium">
        <div class="dash-bg-orb orb-glow-1"></div>
        <div class="dash-bg-orb orb-glow-2"></div>

        <div class="dash-topbar">
            <div class="topbar-left">
                <div class="topbar-search" @click="focusSearch">
                    <SearchOutlined class="search-icon" />
                    <input ref="searchInput" type="text" placeholder="Search employees, reports..." class="search-input" />
                    <span class="search-shortcut">⌘K</span>
                </div>
            </div>
            <div class="topbar-right">
                <button class="topbar-icon-btn">
                    <BellOutlined />
                    <span class="notif-dot"></span>
                </button>
                <button class="topbar-icon-btn">
                    <CalendarOutlined />
                </button>
                <div class="topbar-avatar">
                    <UserOutlined />
                </div>
            </div>
        </div>

        <div class="dash-grid">
            <div class="dash-main">
                <div class="dash-welcome">
                    <div class="welcome-card neumorphic-card">
                        <div class="welcome-greeting">
                            <h1>Good {{ greeting }}, {{ userName }}</h1>
                            <p>Here's what's happening at your company today.</p>
                        </div>
                        <div class="welcome-stats">
                            <div class="welcome-stat">
                                <span class="ws-label">Team</span>
                                <span class="ws-value" v-if="responseData.stateData">{{ responseData.stateData.totalEmployees }}</span>
                            </div>
                            <div class="welcome-stat">
                                <span class="ws-label">Active</span>
                                <span class="ws-value" v-if="responseData.stateData">{{ responseData.stateData.totalActiveEmployee }}</span>
                            </div>
                            <div class="welcome-stat">
                                <span class="ws-label">Present Today</span>
                                <span class="ws-value">{{ responseData.todaysAttendence ? responseData.todaysAttendence.employee_under_you : 0 }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="welcome-attendance neumorphic-card">
                        <div class="attendance-ring-wrapper">
                            <div class="attendance-ring">
                                <svg viewBox="0 0 120 120" class="ring-svg">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="url(#grad)" stroke-width="8" stroke-linecap="round" stroke-dasharray="314" :stroke-dashoffset="ringOffset" transform="rotate(-90 60 60)"/>
                                    <defs>
                                        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#6366F1"/>
                                            <stop offset="100%" stop-color="#8B5CF6"/>
                                        </linearGradient>
                                    </defs>
                                </svg>
                                <div class="ring-center">
                                    <span class="ring-pct">{{ attendancePct }}%</span>
                                    <span class="ring-label">Attendance</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dash-stats-grid">
                    <div class="stat-card neumorphic-card" v-if="willSubscriptionModuleVisible('appreciations')">
                        <div class="stat-icon stat-purple"><TrophyOutlined /></div>
                        <div class="stat-info">
                            <span class="stat-num">{{ responseData.appreciations || 0 }}</span>
                            <span class="stat-label">Appreciations</span>
                        </div>
                    </div>
                    <div class="stat-card neumorphic-card" v-if="willSubscriptionModuleVisible('offboardings')">
                        <div class="stat-icon stat-amber"><WomanOutlined /></div>
                        <div class="stat-info">
                            <span class="stat-num">{{ responseData.warnings || 0 }}</span>
                            <span class="stat-label">Warnings</span>
                        </div>
                    </div>
                    <div class="stat-card neumorphic-card" v-if="willSubscriptionModuleVisible('expenses')">
                        <div class="stat-icon stat-rose"><DollarCircleOutlined /></div>
                        <div class="stat-info">
                            <span class="stat-num">{{ responseData.expenses || 0 }}</span>
                            <span class="stat-label">Expenses</span>
                        </div>
                    </div>
                    <div class="stat-card neumorphic-card" v-if="willSubscriptionModuleVisible('offboardings')">
                        <div class="stat-icon stat-blue"><BankOutlined /></div>
                        <div class="stat-info">
                            <span class="stat-num">{{ responseData.complaints || 0 }}</span>
                            <span class="stat-label">Complaints</span>
                        </div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">Profile & Attendance</h2>
                    <div class="section-grid-3">
                        <div class="neumorphic-card"><DashboardProfileUser :data="responseData" /></div>
                        <div class="neumorphic-card"><LeaveDetailsChartFixed :data="responseData" /></div>
                        <div v-if="willSubscriptionModuleVisible('leaves')" class="neumorphic-card"><LeaveDetailsFixed :data="responseData" /></div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">Attendance Overview</h2>
                    <div class="section-grid-2">
                        <div class="neumorphic-card"><Attendance :data="responseData" /></div>
                        <div class="neumorphic-card"><SelfProgressBar :data="responseData" @attendaceData="getAttendaceData" /></div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">Performance & Analytics</h2>
                    <div class="section-grid-3">
                        <div class="neumorphic-card"><SelfPerformanceChart /></div>
                        <div v-if="willSubscriptionModuleVisible('forms')" class="neumorphic-card"><AssignedSurvey :data="responseData" /></div>
                        <div v-if="willSubscriptionModuleVisible('appreciations')" class="neumorphic-card"><Performance :data="responseData" @employeeAppriciationData="employeeAppriciationData" /></div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">Rewards & Growth</h2>
                    <div class="section-grid-3">
                        <div v-if="willSubscriptionModuleVisible('payrolls')" class="neumorphic-card"><SelfIncrementPromotion :data="responseData" @employeeIncrementData="employeeIncrementData" /></div>
                        <div v-if="willSubscriptionModuleVisible('appreciations')" class="neumorphic-card"><RecentAchievements :data="responseData" /></div>
                        <div class="neumorphic-card"><BirthdayWishCard :data="responseData" /></div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">Events & Calendar</h2>
                    <div class="section-grid-3">
                        <div class="neumorphic-card"><Birthday :data="responseData" @employeeBirthDayData="employeeBirthDayData" /></div>
                        <div class="neumorphic-card"><SelfWorkAnniversay :data="responseData" @employeeAnniversaryData="employeeAnniversaryData" /></div>
                        <div v-if="willSubscriptionModuleVisible('holidays')" class="neumorphic-card"><SelfWeekendHoliday :data="responseData" @fetchYearData="fetchWeekend" /></div>
                    </div>
                </div>

                <div class="dash-section">
                    <h2 class="section-title">More</h2>
                    <div class="section-grid-3">
                        <div class="neumorphic-card"><SelfBirthday :data="responseData" /></div>
                        <div v-if="willSubscriptionModuleVisible('company_policies')" class="neumorphic-card"><SelfCompanyPolicy :data="responseData" /></div>
                        <div v-if="willSubscriptionModuleVisible('holidays')" class="neumorphic-card"><SelfNextHoliday :data="responseData" /></div>
                    </div>
                </div>
            </div>

            <aside class="dash-sidebar">
                <div class="sidebar-sticky">
                    <div class="neumorphic-card sidebar-quick-actions">
                        <h3 class="sidebar-title">Quick Actions</h3>
                        <button class="qa-btn qa-clockin" @click="clockIn"><ClockCircleOutlined /> Clock In</button>
                        <button class="qa-btn qa-clockout" @click="clockOut"><ClockCircleOutlined /> Clock Out</button>
                        <button class="qa-btn qa-leave"><CarOutlined /> Apply Leave</button>
                        <button class="qa-btn qa-payslip"><DollarCircleOutlined /> Download Payslip</button>
                    </div>

                    <div class="neumorphic-card sidebar-holidays">
                        <h3 class="sidebar-title">Upcoming Holidays</h3>
                        <div class="holiday-item" v-for="(h, i) in upcomingHolidays" :key="i">
                            <span class="holiday-date">{{ h.date }}</span>
                            <span class="holiday-name">{{ h.name }}</span>
                        </div>
                        <div v-if="!upcomingHolidays.length" class="holiday-empty">No upcoming holidays</div>
                    </div>

                    <div class="neumorphic-card sidebar-actions-mini">
                        <h3 class="sidebar-title">Quick Stats</h3>
                        <div class="qs-row"><span>Total Leaves</span><span class="qs-val">{{ responseData.totalLeaves || 0 }}</span></div>
                        <div class="qs-row"><span>Approved</span><span class="qs-val success">{{ responseData.approvedLeaves || 0 }}</span></div>
                        <div class="qs-row"><span>Pending</span><span class="qs-val warning">{{ responseData.pendingLeaves || 0 }}</span></div>
                        <div class="qs-row"><span>Rejected</span><span class="qs-val error">{{ responseData.rejectedLeaves || 0 }}</span></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>

<script>
import { onMounted, ref, watch, reactive, computed } from "vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";
import {
    SearchOutlined,
    BellOutlined,
    CalendarOutlined,
    UserOutlined,
    ClockCircleOutlined,
    BankOutlined,
    TrophyOutlined,
    WomanOutlined,
    DollarCircleOutlined,
    CarOutlined,
} from "@ant-design/icons-vue";
import common from "../../../common/composable/common";
import DashboardProfileUser from "./self-dashboard/DashboardProfileUser.vue";
import LeaveDetailsChartFixed from "./self-dashboard/LeaveDetailsChartFixed.vue";
import LeaveDetailsFixed from "./self-dashboard/LeaveDetailsFixed.vue";
import Attendance from "./self-dashboard/Attendance.vue";
import SelfProgressBar from "./self-dashboard/SelfProgressBar.vue";
import Performance from "./self-dashboard/Performance.vue";
import SelfPerformanceChart from "./self-dashboard/SelfPerformanceChart.vue";
import AssignedSurvey from "./self-dashboard/AssignedSurvey.vue";
import SelfIncrementPromotion from "./self-dashboard/SelfIncrementPromotion.vue";
import SelfBirthday from "./self-dashboard/SelfBirthday.vue";
import SelfCompanyPolicy from "./self-dashboard/SelfCompanyPolicy.vue";
import SelfNextHoliday from "./self-dashboard/SelfNextHoliday.vue";
import Birthday from "./self-dashboard/Birthday.vue";
import BirthdayWishCard from "../../../common/components/common/card/BirthdayWishCard.vue";
import SelfWorkAnniversay from "./self-dashboard/SelfWorkAnniversay.vue";
import SelfWeekendHoliday from "./self-dashboard/SelfWeekendHoliday.vue";
import RecentAchievements from "./self-dashboard/RecentAchievements.vue";
import { map } from "lodash-es";

export default {
    components: {
        SearchOutlined, BellOutlined, CalendarOutlined, UserOutlined,
        ClockCircleOutlined, BankOutlined, TrophyOutlined,
        WomanOutlined, DollarCircleOutlined, CarOutlined,
        DashboardProfileUser, LeaveDetailsChartFixed, LeaveDetailsFixed,
        Attendance, SelfProgressBar, Performance, SelfPerformanceChart,
        AssignedSurvey, SelfIncrementPromotion, SelfBirthday,
        SelfCompanyPolicy, SelfNextHoliday, Birthday, BirthdayWishCard,
        SelfWorkAnniversay, SelfWeekendHoliday, RecentAchievements,
    },
    setup() {
        const responseData = ref([]);
        const { permsArray, dayjs, user, willSubscriptionModuleVisible } = common();
        const searchInput = ref(null);
        const filters = reactive({
            year: dayjs().year(),
            userId: user.value.xid,
            filterMonthYear: undefined,
            filterUserId: undefined,
            date: undefined,
            status_date: [dayjs().format("YYYY-MM-DD"), dayjs().format("YYYY-MM-DD")],
            type: "all",
        });

        const userName = computed(() => user.value?.name || "User");
        const greeting = computed(() => {
            const h = dayjs().hour();
            if (h < 12) return "morning";
            if (h < 17) return "afternoon";
            return "evening";
        });
        const attendancePct = computed(() => {
            if (!responseData.value?.todaysAttendence) return 0;
            return Math.min(100, Math.round(responseData.value.todaysAttendence.attendance_percentage || 0));
        });
        const ringOffset = computed(() => {
            const circumference = 2 * Math.PI * 50;
            return circumference - (attendancePct.value / 100) * circumference;
        });

        const upcomingHolidays = computed(() => {
            if (!responseData.value?.upcomingHolidays) return [];
            return responseData.value.upcomingHolidays.slice(0, 5);
        });

        onMounted(() => {
            axiosAdmin.post("self/dashboard", filters).then((res) => {
                responseData.value = { ...responseData.value, ...res.data };
            });
        });

        const focusSearch = () => searchInput.value?.focus();
        const clockIn = () => {};
        const clockOut = () => {};

        const fetchWeekend = (obj) => { filters.year = obj.filterMonthYear; filters.type = obj.type; };
        const getAttendaceData = (d) => { filters.status_date = d.status_date; filters.type = d.type; };
        const employeeIncrementData = (d) => { filters.filterMonthYear = d.filterMonthYear; filters.type = d.type; };
        const employeeBirthDayData = (d) => { filters.filterUserId = d.xid; filters.date = d.date; filters.type = d.type; };
        const employeeAnniversaryData = (d) => { filters.filterUserId = d.xid; filters.date = d.date; filters.type = d.type; };
        const employeeAppriciationData = (d) => { filters.filterMonthYear = d.filterMonthYear; filters.type = d.type; };

        watch(filters, (n) => {
            axiosAdmin.post("self/dashboard", n).then((r) => {
                responseData.value = { ...responseData.value, ...r.data };
            });
        }, { deep: true });

        return {
            responseData, permsArray, filters, searchInput, userName, greeting,
            attendancePct, ringOffset, upcomingHolidays, focusSearch,
            clockIn, clockOut, fetchWeekend, getAttendaceData,
            employeeIncrementData, employeeAppriciationData,
            employeeBirthDayData, employeeAnniversaryData,
            willSubscriptionModuleVisible,
        };
    },
};
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.dash-premium {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #EEF2F7;
    min-height: 100vh;
    padding: 20px 28px 40px;
    position: relative;
    overflow-x: hidden;
}

.dash-bg-orb {
    position: fixed;
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}
.orb-glow-1 {
    width: 500px; height: 500px;
    top: -200px; right: -150px;
    background: radial-gradient(circle at 30% 30%, rgba(99,102,241,0.08), rgba(139,92,246,0.04), transparent 70%);
}
.orb-glow-2 {
    width: 400px; height: 400px;
    bottom: -150px; left: -100px;
    background: radial-gradient(circle at 30% 30%, rgba(139,92,246,0.06), rgba(99,102,241,0.03), transparent 70%);
}

/* ── TOP BAR ── */
.dash-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    position: relative;
    z-index: 2;
}
.topbar-left { flex: 1; max-width: 420px; }
.topbar-search {
    display: flex;
    align-items: center;
    background: #EEF2F7;
    border-radius: 16px;
    padding: 10px 16px;
    box-shadow: inset 4px 4px 10px rgba(163,177,198,0.4), inset -4px -4px 10px rgba(255,255,255,0.9);
    gap: 10px;
    cursor: text;
    transition: box-shadow 0.25s ease;
}
.topbar-search:focus-within {
    box-shadow: inset 5px 5px 12px rgba(163,177,198,0.5), inset -5px -5px 12px rgba(255,255,255,0.95);
}
.search-icon { color: #9CA3AF; font-size: 16px; }
.search-input {
    border: none;
    background: transparent;
    outline: none;
    flex: 1;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #1F2937;
}
.search-input::placeholder { color: #9CA3AF; }
.search-shortcut {
    font-size: 11px;
    font-weight: 600;
    color: #9CA3AF;
    background: rgba(255,255,255,0.5);
    padding: 2px 8px;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.topbar-right { display: flex; align-items: center; gap: 12px; }
.topbar-icon-btn {
    width: 44px; height: 44px;
    border-radius: 14px;
    border: none;
    background: #EEF2F7;
    box-shadow: 5px 5px 12px rgba(163,177,198,0.4), -5px -5px 12px rgba(255,255,255,0.9);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    color: #6B7280;
    font-size: 18px;
    position: relative;
    transition: all 0.25s ease;
}
.topbar-icon-btn:hover {
    box-shadow: 3px 3px 8px rgba(163,177,198,0.4), -3px -3px 8px rgba(255,255,255,0.9);
    transform: translateY(-1px);
    color: #6366F1;
}
.notif-dot {
    position: absolute; top: 10px; right: 10px;
    width: 8px; height: 8px;
    background: #EF4444;
    border-radius: 50%;
    border: 2px solid #EEF2F7;
}
.topbar-avatar {
    width: 44px; height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366F1, #8B5CF6);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 18px;
    box-shadow: 0 6px 14px rgba(99,102,241,0.3);
}

/* ── NEUMORPHIC CARD ── */
.neumorphic-card {
    background: #EEF2F7;
    border-radius: 28px;
    box-shadow: 12px 12px 24px rgba(163,177,198,0.45), -12px -12px 24px rgba(255,255,255,0.9);
    border: none;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    height: 100%;
}
.neumorphic-card:hover {
    box-shadow: 8px 8px 18px rgba(163,177,198,0.45), -8px -8px 18px rgba(255,255,255,0.9);
    transform: translateY(-3px);
}
.neumorphic-card .ant-card {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    border-radius: 28px !important;
}
.neumorphic-card .ant-card-head {
    background: transparent !important;
    border-bottom: 1px solid rgba(0,0,0,0.03) !important;
    padding: 18px 22px 12px !important;
    min-height: auto !important;
}
.neumorphic-card .ant-card-head-title {
    font-family: 'Inter', sans-serif;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: -0.2px;
    color: #1F2937;
}
.neumorphic-card .ant-card-body {
    background: transparent !important;
    padding: 16px 22px 22px !important;
}
.neumorphic-card .ant-card-extra { padding: 14px 0 !important; }

/* ── GRID LAYOUT ── */
.dash-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
    position: relative;
    z-index: 1;
}
.dash-main { min-width: 0; }

/* ── WELCOME HERO ── */
.dash-welcome {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 20px;
    margin-bottom: 22px;
}
.welcome-card {
    padding: 28px 32px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.welcome-greeting h1 {
    font-size: 22px;
    font-weight: 700;
    color: #1F2937;
    margin: 0 0 4px;
}
.welcome-greeting p {
    font-size: 14px;
    font-weight: 500;
    color: #6B7280;
    margin: 0 0 22px;
}
.welcome-stats { display: flex; gap: 32px; }
.welcome-stat { display: flex; flex-direction: column; gap: 2px; }
.ws-label { font-size: 12px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; }
.ws-value { font-size: 26px; font-weight: 800; color: #1F2937; line-height: 1; }

.welcome-attendance {
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.attendance-ring-wrapper { position: relative; }
.attendance-ring { position: relative; width: 140px; height: 140px; }
.ring-svg { width: 100%; height: 100%; }
.ring-center {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
}
.ring-pct { font-size: 28px; font-weight: 800; color: #1F2937; line-height: 1; }
.ring-label { font-size: 11px; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

/* ── STATS GRID ── */
.dash-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    padding: 20px 22px;
    display: flex;
    align-items: center;
    gap: 16px;
}
.stat-icon {
    width: 48px; height: 48px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.stat-purple { background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.15)); color: #6366F1; }
.stat-amber { background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(251,191,36,0.15)); color: #F59E0B; }
.stat-rose { background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(251,113,133,0.15)); color: #EF4444; }
.stat-blue { background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.15)); color: #3B82F6; }
.stat-info { display: flex; flex-direction: column; gap: 1px; }
.stat-num { font-size: 22px; font-weight: 800; color: #1F2937; line-height: 1.2; }
.stat-label { font-size: 12px; font-weight: 600; color: #6B7280; text-transform: uppercase; letter-spacing: 0.3px; }

/* ── SECTIONS ── */
.dash-section { margin-bottom: 28px; }
.section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1F2937;
    margin: 0 0 14px 4px;
    letter-spacing: -0.3px;
}
.section-grid-3 {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
}
.section-grid-3 > * {
    flex: 1;
    min-width: 260px;
}
.section-grid-2 {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
}
.section-grid-2 > * {
    flex: 1;
    min-width: 300px;
}

/* ── SIDEBAR ── */
.dash-sidebar {}
.sidebar-sticky {
    display: flex;
    flex-direction: column;
    gap: 18px;
    position: sticky;
    top: 20px;
}
.sidebar-title {
    font-size: 14px;
    font-weight: 700;
    color: #1F2937;
    margin: 0 0 14px;
    letter-spacing: -0.2px;
}
.sidebar-quick-actions, .sidebar-holidays, .sidebar-actions-mini {
    padding: 22px;
}

.qa-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 8px;
    border-radius: 14px;
    border: none;
    background: #EEF2F7;
    box-shadow: 5px 5px 12px rgba(163,177,198,0.4), -5px -5px 12px rgba(255,255,255,0.9);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #1F2937;
    cursor: pointer;
    transition: all 0.25s ease;
}
.qa-btn:last-child { margin-bottom: 0; }
.qa-btn:hover {
    box-shadow: 3px 3px 8px rgba(163,177,198,0.4), -3px -3px 8px rgba(255,255,255,0.9);
    transform: translateY(-2px);
    color: #6366F1;
}
.qa-btn:active {
    box-shadow: inset 4px 4px 10px rgba(163,177,198,0.4), inset -4px -4px 10px rgba(255,255,255,0.9);
    transform: translateY(0);
}

.holiday-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.03);
}
.holiday-item:last-child { border-bottom: none; }
.holiday-date { font-size: 12px; font-weight: 600; color: #9CA3AF; }
.holiday-name { font-size: 13px; font-weight: 600; color: #1F2937; }
.holiday-empty { font-size: 13px; color: #9CA3AF; text-align: center; padding: 12px 0; }

.qs-row {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    font-size: 13px;
    font-weight: 500;
    color: #6B7280;
    border-bottom: 1px solid rgba(0,0,0,0.03);
}
.qs-row:last-child { border-bottom: none; }
.qs-val { font-weight: 700; color: #1F2937; }
.qs-val.success { color: #10B981; }
.qs-val.warning { color: #F59E0B; }
.qs-val.error { color: #EF4444; }

/* ── RESPONSIVE ── */
@media (max-width: 1200px) {
    .dash-grid { grid-template-columns: 1fr; }
    .dash-sidebar { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }
    .sidebar-sticky { position: static; flex-direction: row; gap: 18px; }
    .sidebar-quick-actions, .sidebar-holidays, .sidebar-actions-mini { flex: 1; }
}
@media (max-width: 1024px) {
    .dash-welcome { grid-template-columns: 1fr; }
    .dash-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .section-grid-3 { grid-template-columns: 1fr 1fr; }
    .dash-premium { padding: 16px 20px 32px; }
}
@media (max-width: 768px) {
    .dash-stats-grid { grid-template-columns: 1fr 1fr; }
    .section-grid-3, .section-grid-2 { grid-template-columns: 1fr; }
    .dash-sidebar { grid-template-columns: 1fr; }
    .sidebar-sticky { flex-direction: column; }
    .dash-topbar { flex-direction: column; gap: 12px; }
    .topbar-left { max-width: 100%; width: 100%; }
    .dash-premium { padding: 14px 16px 24px; }
    .welcome-stats { gap: 20px; }
}
</style>