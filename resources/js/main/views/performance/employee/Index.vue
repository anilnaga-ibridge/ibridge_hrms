<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t('menu.my_performance')" class="p-0">
                <template #extra>
                    <div class="header-actions">
                        <a-date-picker
                            v-model:value="filters.month"
                            picker="month"
                            :allowClear="false"
                            @change="fetchData"
                        />
                        <a-button type="primary" class="download-btn" @click="downloadReport">
                            <template #icon><DownloadOutlined /></template>
                            Download Report
                        </a-button>
                    </div>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.employee.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.my_performance') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="dashboard-page-content-container">
        <!-- Performance Header Card -->
        <div class="performance-banner mb-20">
            <div class="banner-content">
                <div class="banner-info">
                    <h2>Overall Performance Analytics</h2>
                    <p>Track your attendance, productivity, communication, leadership, and overall progress score calculated monthly.</p>
                </div>
                <div class="grade-legend">
                    <div class="legend-item grade-aplus">
                        <span class="legend-badge">A+</span>
                        <div class="legend-desc">
                            <span class="legend-title">Outstanding</span>
                            <span class="legend-range">(≥90)</span>
                        </div>
                    </div>
                    <div class="legend-item grade-a">
                        <span class="legend-badge">A</span>
                        <div class="legend-desc">
                            <span class="legend-title">Excellent</span>
                            <span class="legend-range">(80-89)</span>
                        </div>
                    </div>
                    <div class="legend-item grade-b">
                        <span class="legend-badge">B</span>
                        <div class="legend-desc">
                            <span class="legend-title">Good</span>
                            <span class="legend-range">(70-79)</span>
                        </div>
                    </div>
                    <div class="legend-item grade-c">
                        <span class="legend-badge">C</span>
                        <div class="legend-desc">
                            <span class="legend-title">Average</span>
                            <span class="legend-range">(60-69)</span>
                        </div>
                    </div>
                    <div class="legend-item grade-d">
                        <span class="legend-badge">D</span>
                        <div class="legend-desc">
                            <span class="legend-title">Needs Work</span>
                            <span class="legend-range">(&lt;60)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Summary Cards Row (4 Columns) -->
        <a-row :gutter="[16, 16]" class="mb-20">
            <!-- Radial Bar Column -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <div class="kpi-card gauge-card text-center">
                    <RadialBar :value="overallScore" :grade="grade" label="Performance Grade" :key="'radial-' + overallScore + '-' + grade" />
                    <div class="gauge-footer">
                        <div class="gauge-score-value">{{ (overallScore ?? 0).toFixed(2) }} / 100</div>
                        <div class="gauge-status-pill" :class="gradeClass">{{ gradeLabel }}</div>
                    </div>
                </div>
            </a-col>

            <!-- Company Rank Column -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <div class="kpi-card rank-card rank-purple">
                    <div class="rank-card-header">
                        <div class="hex-icon-wrapper purple-glow">
                            <div class="hex-bg">
                                <TrophyOutlined class="rank-icon" />
                            </div>
                        </div>
                        <div class="rank-details">
                            <span class="rank-title">Company Rank</span>
                            <span class="rank-value">#{{ companyRank || 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="rank-sparkline">
                        <apexchart type="area" height="65" :options="sparklinePurpleOptions" :series="sparklineSeries" />
                    </div>
                </div>
            </a-col>

            <!-- Department Rank Column -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <div class="kpi-card rank-card rank-green">
                    <div class="rank-card-header">
                        <div class="hex-icon-wrapper green-glow">
                            <div class="hex-bg">
                                <TeamOutlined class="rank-icon" />
                            </div>
                        </div>
                        <div class="rank-details">
                            <span class="rank-title">Department Rank</span>
                            <span class="rank-value">#{{ deptRank || 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="rank-sparkline">
                        <apexchart type="area" height="65" :options="sparklineGreenOptions" :series="sparklineSeries" />
                    </div>
                </div>
            </a-col>

            <!-- Percentile Column -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <div class="kpi-card rank-card rank-blue">
                    <div class="rank-card-header">
                        <div class="hex-icon-wrapper blue-glow">
                            <div class="hex-bg">
                                <AimOutlined class="rank-icon" />
                            </div>
                        </div>
                        <div class="rank-details">
                            <span class="rank-title">Percentile</span>
                            <span class="rank-value">Top {{ percentile || '0' }}%</span>
                        </div>
                    </div>
                    <div class="rank-sparkline">
                        <apexchart type="area" height="65" :options="sparklineBlueOptions" :series="sparklineSeries" />
                    </div>
                </div>
            </a-col>
        </a-row>

        <!-- Charts Row (Radar, Trend, Task Donut) -->
        <a-row :gutter="[16, 16]" class="mb-20">
            <!-- Radar Chart -->
            <a-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                <a-card :bordered="false" class="chart-card">
                    <div class="card-header-flex">
                        <h3 class="card-title">KPI Breakdown</h3>
                        <div class="custom-legend">
                            <span class="legend-dot-label"><span class="dot blue"></span> Your Score</span>
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <RadarChart :categories="kpiLabels" :data="kpiValues" :key="'radar-' + kpiValues.join(',') + '-' + kpiLabels.join(',')" />
                    </div>
                </a-card>
            </a-col>

            <!-- Monthly Trend (Area Chart) -->
            <a-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                <a-card :bordered="false" class="chart-card">
                    <div class="card-header-flex">
                        <h3 class="card-title">Monthly Trend</h3>
                        <a-select v-model:value="trendPeriod" size="small" style="width: 100px">
                            <a-select-option value="6">6 Months</a-select-option>
                            <a-select-option value="12">12 Months</a-select-option>
                        </a-select>
                    </div>
                    <div class="chart-wrapper font-bold-values">
                        <AreaChart :data="trendDataFiltered" :labels="trendLabelsFiltered" :key="'area-' + trendDataFiltered.join(',') + '-' + trendLabelsFiltered.join(',')" />
                    </div>
                </a-card>
            </a-col>

            <!-- Task Analytics (Donut) -->
            <a-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                <a-card :bordered="false" class="chart-card">
                    <div class="card-header-flex">
                        <h3 class="card-title">Task Analytics</h3>
                        <a-select v-model:value="taskPeriod" size="small" style="width: 100px">
                            <a-select-option value="current">This Month</a-select-option>
                        </a-select>
                    </div>
                    <div class="chart-wrapper task-analytics-container">
                        <div class="task-donut-layout">
                            <div class="donut-chart-box">
                                <DonutChart :data="taskDonutData" :hideLegend="true" :key="'donut-' + taskDonutData.map(d => d.value).join(',')" />
                            </div>
                            <div class="task-legend-list">
                                <div class="task-legend-item" v-for="item in taskDonutData" :key="item.label">
                                    <span class="legend-color-dot" :style="{ background: item.color }"></span>
                                    <span class="legend-label">{{ item.label }}</span>
                                    <span class="legend-value">{{ item.value }} ({{ item.percent }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a-card>
            </a-col>
        </a-row>

        <!-- Attendance Summary, Strengths, Areas for Improvement Row -->
        <a-row :gutter="[16, 16]" class="mb-20">
            <!-- Attendance Summary -->
            <a-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                <a-card :bordered="false" class="attendance-summary-card h-100">
                    <div class="card-header-flex mb-16">
                        <h3 class="card-title">Attendance Summary</h3>
                    </div>
                    <div class="attendance-charts-container" v-if="attendanceSummary">
                        <!-- Left column: Radial Gauge for Rate -->
                        <div class="attendance-chart-box attendance-rate-box">
                            <apexchart 
                                type="radialBar" 
                                height="200" 
                                :options="attendanceRateChartOptions" 
                                :series="attendanceRateSeries" 
                                :key="'att-rate-' + attendanceSummary.percentage"
                            />
                            <div class="attendance-quick-info">
                                <span class="quick-info-num">{{ attendanceSummary.percentage }}%</span>
                                <span class="quick-info-label">Attendance Rate</span>
                            </div>
                        </div>

                        <!-- Right column: Donut for days breakdown -->
                        <div class="attendance-chart-box attendance-dist-box">
                            <apexchart 
                                type="donut" 
                                height="180" 
                                :options="attendanceDistributionOptions" 
                                :series="attendanceDistributionSeries" 
                                :key="'att-dist-' + attendanceSummary.present + '-' + attendanceSummary.absent + '-' + attendanceSummary.lop"
                            />
                            <!-- Simple custom inline stats list beneath donut -->
                            <div class="attendance-stats-row">
                                <div class="attendance-stat-item">
                                    <span class="stat-bullet bg-green"></span>
                                    <span class="stat-label">Present:</span>
                                    <span class="stat-value">{{ attendanceSummary.present }} Days</span>
                                </div>
                                <div class="attendance-stat-item">
                                    <span class="stat-bullet bg-red"></span>
                                    <span class="stat-label">Absent:</span>
                                    <span class="stat-value">{{ attendanceSummary.absent }} Days</span>
                                </div>
                                <div class="attendance-stat-item">
                                    <span class="stat-bullet bg-orange"></span>
                                    <span class="stat-label">LOP:</span>
                                    <span class="stat-value">{{ attendanceSummary.lop }} Days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="no-data-sm">{{ $t('common.no_data') }}</div>
                </a-card>
            </a-col>

            <!-- Strengths -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <a-card :bordered="false" class="strengths-card h-100">
                    <h3 class="card-title mb-16">Strengths</h3>
                    <div v-if="strengths.length > 0" class="mini-chart-wrapper">
                        <apexchart
                            type="bar"
                            height="140"
                            :options="strengthsChartOptions"
                            :series="strengthsSeries"
                        />
                    </div>
                    <div v-else class="glowing-target-wrapper green-glow-effect">
                        <div class="glow-circle green-glow">
                            <LikeOutlined class="glow-icon text-green" />
                        </div>
                        <span class="glow-text">No critical strengths recorded yet.</span>
                    </div>
                </a-card>
            </a-col>

            <!-- Areas for Improvement -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <a-card :bordered="false" class="improvements-card h-100">
                    <h3 class="card-title mb-16">Areas for Improvement</h3>
                    <div v-if="improvements.length > 0" class="mini-chart-wrapper">
                        <apexchart
                            type="bar"
                            height="140"
                            :options="improvementsChartOptions"
                            :series="improvementsSeries"
                        />
                    </div>
                    <div v-else class="glowing-target-wrapper blue-glow-effect">
                        <div class="glow-circle blue-glow">
                            <AimOutlined class="glow-icon text-blue" />
                        </div>
                        <span class="glow-text">All performance areas are in good standing!</span>
                    </div>
                </a-card>
            </a-col>
        </a-row>

        <!-- Appreciations -->
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
                <a-card :bordered="false" class="appreciations-card overflow-hidden relative">
                    <div class="appreciations-layout">
                        <div class="appr-info-box">
                            <div class="party-popper-wrapper">
                                <span class="emoji-popper">🎉</span>
                            </div>
                            <div class="appr-text">
                                <!-- Copy changes based on whether the employee actually has appreciations -->
                                <template v-if="appreciationData && appreciationData.some(v => v > 0)">
                                    <h3>Well done — you've been appreciated!</h3>
                                    <p>Your contributions have been recognised this year. Keep up the excellent work!</p>
                                </template>
                                <template v-else>
                                    <h3>Keep up the great work!</h3>
                                    <p>You don't have any appreciations yet. Keep contributing to be recognized!</p>
                                </template>
                            </div>
                        </div>
                        <div class="appr-chart-box">
                            <apexchart
                                type="bar"
                                height="80"
                                :options="appreciationChartOptions"
                                :series="appreciationSeries"
                            />
                        </div>
                        <div class="appr-graphic-box">
                            <div class="trophy-platform-container">
                                <div class="trophy-glowing-base"></div>
                                <span class="emoji-trophy">🏆</span>
                            </div>
                        </div>
                    </div>
                    <!-- Subtle wave graphic on background -->
                    <div class="appr-bg-wave">
                        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0,32L120,42.7C240,53,480,75,720,74.7C960,75,1200,53,1320,42.7L1440,32L1440,120L1320,120C1200,120,960,120,720,120C480,120,240,120,120,120L0,120Z" fill="url(#wave-gradient)" />
                            <defs>
                                <linearGradient id="wave-gradient" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="rgba(99, 102, 241, 0.03)" />
                                    <stop offset="100%" stop-color="rgba(99, 102, 241, 0.08)" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </a-card>
            </a-col>
        </a-row>
    </div>
</template>

<script>
import { ref, onMounted, computed, reactive } from "vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import { 
    TrophyOutlined, 
    TeamOutlined, 
    CheckCircleOutlined, 
    InfoCircleOutlined, 
    SmileOutlined,
    AimOutlined,
    PieChartOutlined,
    LikeOutlined,
    CalendarOutlined,
    DownloadOutlined
} from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import RadialBar from "../../../components/charts/performance/RadialBar.vue";
import RadarChart from "../../../components/charts/performance/RadarChart.vue";
import DonutChart from "../../../components/charts/performance/DonutChart.vue";
import AreaChart from "../../../components/charts/performance/AreaChart.vue";
import { useI18n } from "vue-i18n";

export default {
    components: { 
        AdminPageHeader, 
        RadialBar, 
        RadarChart, 
        DonutChart, 
        AreaChart, 
        TrophyOutlined, 
        TeamOutlined, 
        CheckCircleOutlined, 
        InfoCircleOutlined, 
        SmileOutlined,
        AimOutlined,
        PieChartOutlined,
        LikeOutlined,
        CalendarOutlined,
        DownloadOutlined
    },
    setup() {
        const { dayjs } = common();
        const { t } = useI18n();

        const filters = reactive({
            month: dayjs(),
        });

        const overallScore = ref(0);
        const grade = ref("");
        const companyRank = ref(null);
        const deptRank = ref(null);
        const percentile = ref(null);
        const trendData = ref([]);
        const kpiData = ref([]);
        const taskAnalytics = ref({ assigned: 0, completed: 0, pending: 0, overdue: 0 });
        const attendanceSummary = ref(null);
        const strengths = ref([]);
        const improvements = ref([]);
        const appreciationData = ref([]);

        // Interactive Period Refs
        const trendPeriod = ref("6");
        const taskPeriod = ref("current");

        const kpiLabels = computed(() => kpiData.value.map((k) => k.label));
        const kpiValues = computed(() => kpiData.value.map((k) => k.value));

        // Gauge status bindings
        const gradeLabel = computed(() => {
            const labels = {
                "A+": "Outstanding",
                "A": "Excellent",
                "B": "Good",
                "C": "Average",
                "D": "Needs Work"
            };
            return labels[grade.value] || "Needs Work";
        });

        const gradeClass = computed(() => {
            const classes = {
                "A+": "status-outstanding",
                "A": "status-excellent",
                "B": "status-good",
                "C": "status-average",
                "D": "status-needs-work"
            };
            return classes[grade.value] || "status-needs-work";
        });

        // Sparklines Data from real monthly trend
        const sparklineData = computed(() => {
            const raw = trendData.value.filter(v => v !== null);
            if (raw.length === 0) return [40, 50, 45, 60, 55, 70];
            return raw;
        });

        const sparklineSeries = computed(() => [
            {
                name: "Score",
                data: sparklineData.value
            }
        ]);

        const makeSparklineOptions = (color) => ({
            chart: {
                type: "area",
                sparkline: { enabled: true },
                animations: { enabled: true, easing: "easeinout", speed: 800 },
            },
            stroke: { curve: "smooth", width: 2 },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.01,
                    stops: [0, 100],
                }
            },
            colors: [color],
            tooltip: { enabled: false },
        });

        const sparklinePurpleOptions = computed(() => makeSparklineOptions("#8b5cf6"));
        const sparklineGreenOptions = computed(() => makeSparklineOptions("#10b981"));
        const sparklineBlueOptions = computed(() => makeSparklineOptions("#3b82f6"));
        const sparklineRedOptions = computed(() => makeSparklineOptions("#ef4444"));
        const sparklineOrangeOptions = computed(() => makeSparklineOptions("#f59e0b"));

        // Attendance Summary Redesign using ApexCharts
        const attendanceRateSeries = computed(() => [
            attendanceSummary.value ? parseFloat(attendanceSummary.value.percentage) : 0
        ]);

        const attendanceRateColor = computed(() => {
            const rate = attendanceSummary.value ? parseFloat(attendanceSummary.value.percentage) : 0;
            if (rate < 50) return "#ef4444"; // Red
            if (rate < 85) return "#f59e0b"; // Orange
            return "#10b981"; // Green
        });

        const attendanceRateChartOptions = computed(() => ({
            chart: {
                type: "radialBar",
                sparkline: { enabled: true },
                animations: { enabled: true, easing: "easeinout", speed: 800 }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -90,
                    endAngle: 90,
                    track: {
                        background: "#f1f5f9",
                        strokeWidth: "90%",
                        margin: 5,
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: "11px",
                            fontWeight: 600,
                            color: "#64748b",
                            offsetY: -55
                        },
                        value: {
                            show: true,
                            fontSize: "18px",
                            fontWeight: 700,
                            color: "#1e293b",
                            offsetY: -15,
                            formatter: (val) => `${val}%`
                        }
                    }
                }
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: "light",
                    type: "horizontal",
                    shadeIntensity: 0.5,
                    gradientToColors: [attendanceRateColor.value],
                    inverseColors: true,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },
            colors: [attendanceRateColor.value],
            labels: ["Attendance Rate"]
        }));

        const attendanceDistributionSeries = computed(() => [
            attendanceSummary.value ? attendanceSummary.value.present : 0,
            attendanceSummary.value ? attendanceSummary.value.absent : 0,
            attendanceSummary.value ? attendanceSummary.value.lop : 0
        ]);

        const attendanceDistributionOptions = computed(() => ({
            chart: {
                type: "donut",
                animations: { enabled: true, easing: "easeinout", speed: 800 }
            },
            labels: ["Present", "Absent", "LOP"],
            colors: ["#10b981", "#ef4444", "#f59e0b"],
            stroke: { show: true, width: 2, colors: ["#ffffff"] },
            plotOptions: {
                pie: {
                    donut: {
                        size: "65%",
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: "10px",
                                fontWeight: 600,
                                color: "#64748b",
                                offsetY: -4
                            },
                            value: {
                                show: true,
                                fontSize: "12px",
                                fontWeight: 700,
                                color: "#1e293b",
                                offsetY: 4,
                                formatter: (val) => `${val} Days`
                            },
                            total: {
                                show: true,
                                label: "Total Days",
                                fontSize: "10px",
                                fontWeight: 600,
                                color: "#64748b",
                                formatter: (w) => {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + " Days";
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                show: false
            },
            tooltip: {
                theme: "light",
                y: {
                    formatter: (val) => `${val} Days`
                }
            }
        }));

        // Trend calculations based on period
        const trendDataFiltered = computed(() => {
            const limit = parseInt(trendPeriod.value);
            const currentMonth = filters.month.month(); // 0-based index
            const start = Math.max(0, currentMonth - limit + 1);
            return trendData.value.slice(start, currentMonth + 1);
        });

        const trendLabelsFiltered = computed(() => {
            const limit = parseInt(trendPeriod.value);
            const currentMonth = filters.month.month();
            const start = Math.max(0, currentMonth - limit + 1);
            const allLabels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            return allLabels.slice(start, currentMonth + 1);
        });

        // Task Analytics donut custom payload
        const taskDonutData = computed(() => {
            const completed = taskAnalytics.value.completed || 0;
            const pending   = taskAnalytics.value.pending   || 0;
            const overdue   = taskAnalytics.value.overdue   || 0;
            // Use the API-supplied assigned count as the true total.
            // "overdue" is a subset of "pending", so computing
            // completed + pending + overdue would double-count overdue tasks.
            const total = taskAnalytics.value.assigned || (completed + pending);

            const makePercent = (val) => total > 0 ? Math.round((val / total) * 100) : 0;

            return [
                { label: "Completed", value: completed, percent: makePercent(completed), color: "#10b981" },
                { label: "Pending",   value: pending,   percent: makePercent(pending),   color: "#3b82f6" },
                { label: "Overdue",   value: overdue,   percent: makePercent(overdue),   color: "#ef4444" }
            ];
        });

        // Strengths & Improvements ApexChart configs
        const strengthsChartOptions = computed(() => ({
            chart: {
                type: "bar",
                height: 140,
                toolbar: { show: false },
                animations: { enabled: true, easing: "easeinout", speed: 800 }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: "65%",
                    borderRadius: 4,
                    distributed: true,
                    dataLabels: { position: "end" }
                }
            },
            colors: ["#10b981", "#34d399", "#6ee7b7"],
            dataLabels: {
                enabled: true,
                textAnchor: "start",
                style: { colors: ["#fff"], fontWeight: 700, fontSize: "10px" },
                formatter: (val) => `${val.toFixed(1)}%`,
                offsetX: 0
            },
            xaxis: {
                categories: strengths.value.map(s => s.name),
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { fontSize: "10px", fontWeight: 700, colors: "#475569" }
                }
            },
            grid: { show: false },
            tooltip: { enabled: true, theme: "light" },
            legend: { show: false }
        }));

        const strengthsSeries = computed(() => [
            {
                name: "Score",
                data: strengths.value.map(s => s.score)
            }
        ]);

        const improvementsChartOptions = computed(() => ({
            chart: {
                type: "bar",
                height: 140,
                toolbar: { show: false },
                animations: { enabled: true, easing: "easeinout", speed: 800 }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: "65%",
                    borderRadius: 4,
                    distributed: true,
                    dataLabels: { position: "end" }
                }
            },
            colors: ["#f97316", "#fb923c", "#fca5a5"],
            dataLabels: {
                enabled: true,
                textAnchor: "start",
                style: { colors: ["#fff"], fontWeight: 700, fontSize: "10px" },
                formatter: (val) => `${val.toFixed(1)}%`,
                offsetX: 0
            },
            xaxis: {
                categories: improvements.value.map(s => s.name),
                labels: { show: false },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { fontSize: "10px", fontWeight: 700, colors: "#475569" }
                }
            },
            grid: { show: false },
            tooltip: { enabled: true, theme: "light" },
            legend: { show: false }
        }));

        const improvementsSeries = computed(() => [
            {
                name: "Score",
                data: improvements.value.map(s => s.score)
            }
        ]);

        // Monthly Appreciations Chart configurations
        const appreciationChartOptions = computed(() => ({
            chart: {
                type: "bar",
                height: 80,
                sparkline: { enabled: true },
                animations: { enabled: true, easing: "easeinout", speed: 800 }
            },
            plotOptions: {
                bar: {
                    columnWidth: "60%",
                    borderRadius: 3,
                    distributed: true
                }
            },
            colors: [
                "#c084fc", "#a78bfa", "#818cf8", "#60a5fa", 
                "#34d399", "#4ade80", "#a3e635", "#fde047", 
                "#fbbf24", "#fb923c", "#f87171", "#fda4af"
            ],
            xaxis: {
                categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
            },
            tooltip: {
                theme: "light",
                x: { show: true },
                y: {
                    formatter: (val) => `${val} Appreciations`
                }
            }
        }));

        const appreciationSeries = computed(() => [
            {
                name: "Appreciations",
                data: appreciationData.value
            }
        ]);

        const downloadReport = () => {
            window.print();
        };

        const fetchData = () => {
            const month = filters.month.format("M");
            const year = filters.month.format("YYYY");

            axiosAdmin.get(`performance/self/dashboard?month=${month}&year=${year}`).then((response) => {
                const data = response.data;
                const current = data.current;

                if (current) {
                    overallScore.value = current.overall_score;
                    grade.value = current.grade;
                    companyRank.value = current.company_rank;
                    deptRank.value = current.department_rank;
                    percentile.value = data.percentile || 0;

                    taskAnalytics.value = data.tasks || { assigned: 0, completed: 0, pending: 0, overdue: 0 };
                    attendanceSummary.value = data.attendance || null;
                    appreciationData.value = data.appreciations || [];
                } else {
                    overallScore.value = 0;
                    grade.value = "";
                    companyRank.value = null;
                    deptRank.value = null;
                    percentile.value = 0;

                    taskAnalytics.value = { assigned: 0, completed: 0, pending: 0, overdue: 0 };
                    attendanceSummary.value = null;
                    appreciationData.value = [];
                }

                const monthlyScores = Array(12).fill(null);
                if (data.trend) {
                    data.trend.forEach((item) => {
                        const mIndex = parseInt(item.month) - 1;
                        if (mIndex >= 0 && mIndex < 12) {
                            monthlyScores[mIndex] = item.overall_score;
                        }
                    });
                }
                trendData.value = monthlyScores;

                kpiData.value = data.kpis || [];

                if (data.strengths) {
                    strengths.value = data.strengths.strengths || [];
                    improvements.value = data.strengths.improvements || [];
                } else {
                    strengths.value = [];
                    improvements.value = [];
                }
            });
        };

        onMounted(fetchData);

        return {
            filters,
            overallScore, grade, companyRank, deptRank, percentile,
            trendData, kpiData, kpiLabels, kpiValues,
            taskAnalytics, attendanceSummary, strengths, improvements, appreciationData,
            trendPeriod, taskPeriod,
            gradeLabel, gradeClass,
            sparklineSeries,
            sparklinePurpleOptions, sparklineGreenOptions, sparklineBlueOptions,
            sparklineRedOptions, sparklineOrangeOptions,
            attendanceRateSeries, attendanceRateColor, attendanceRateChartOptions,
            attendanceDistributionSeries, attendanceDistributionOptions,
            trendDataFiltered, trendLabelsFiltered,
            taskDonutData,
            strengthsChartOptions, strengthsSeries,
            improvementsChartOptions, improvementsSeries,
            appreciationChartOptions, appreciationSeries,
            downloadReport, fetchData,
        };
    },
};
</script>

<style scoped>
/* Page Layout */
.dashboard-page-content-container {
    background: #f8fafc;
    padding: 24px;
    border-radius: 16px;
    min-height: calc(100vh - 120px);
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.download-btn {
    border-radius: 8px;
    font-weight: 600;
    box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2);
}

/* Header Banner Card */
.performance-banner {
    background: linear-gradient(135deg, #f5f3ff 0%, #e0e7ff 100%);
    border: 1px solid rgba(133, 44, 160, 0.1);
    border-radius: 16px;
    padding: 24px;
}

.banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.banner-info h2 {
    margin: 0 0 6px 0;
    font-weight: 800;
    color: #1e1b4b;
    font-size: 22px;
}

.banner-info p {
    margin: 0;
    color: #4f46e5;
    font-size: 14px;
    font-weight: 500;
}

.grade-legend {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
}

.legend-badge {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 12px;
}

.legend-desc {
    display: flex;
    flex-direction: column;
}

.legend-title {
    font-size: 11px;
    font-weight: 700;
    color: #334155;
    line-height: 1.2;
}

.legend-range {
    font-size: 9px;
    color: #64748b;
    font-weight: 600;
}

.grade-aplus .legend-badge { background: #d1fae5; color: #065f46; }
.grade-a .legend-badge { background: #dbeafe; color: #1e40af; }
.grade-b .legend-badge { background: #fef3c7; color: #92400e; }
.grade-c .legend-badge { background: #ffedd5; color: #9a3412; }
.grade-d .legend-badge { background: #fee2e2; color: #991b1b; }

/* KPI Cards Layout */
.kpi-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    overflow: hidden;
    height: 100%;
}

.gauge-card {
    padding: 16px 16px 20px 16px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.gauge-footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-top: -30px;
}

.gauge-score-value {
    font-size: 14px;
    font-weight: 700;
    color: #64748b;
}

.gauge-status-pill {
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-outstanding { background: #d1fae5; color: #065f46; }
.status-excellent { background: #dbeafe; color: #1e40af; }
.status-good { background: #fef3c7; color: #92400e; }
.status-average { background: #ffedd5; color: #9a3412; }
.status-needs-work { background: #fee2e2; color: #991b1b; }

/* Rank Cards */
.rank-card {
    padding: 24px 0 0 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
}

.rank-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0 24px;
}

/* CSS Hexagon Shapes */
.hex-icon-wrapper {
    position: relative;
    width: 48px;
    height: 52px;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.05));
}

.hex-bg {
    width: 48px;
    height: 52px;
    clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.purple-glow .hex-bg { background: linear-gradient(135deg, #c084fc 0%, #8b5cf6 100%); }
.green-glow .hex-bg { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); }
.blue-glow .hex-bg { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); }

.rank-icon {
    font-size: 20px;
    color: #ffffff;
}

.rank-details {
    display: flex;
    flex-direction: column;
}

.rank-title {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.rank-value {
    font-size: 24px;
    font-weight: 800;
    line-height: 1.1;
    margin-top: 2px;
}

.rank-purple .rank-value { color: #5b21b6; }
.rank-green .rank-value { color: #065f46; }
.rank-blue .rank-value { color: #1e40af; }

.rank-sparkline {
    margin-top: 16px;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
    overflow: hidden;
}

/* Charts Elements */
.chart-card {
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-title {
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
}

.custom-legend {
    display: flex;
    align-items: center;
}

.legend-dot-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}

.legend-dot-label .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.legend-dot-label .dot.blue { background: #852ca0; }

.chart-wrapper {
    min-height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.task-analytics-container {
    display: block;
}

/* Task Donut Layout */
.task-donut-layout {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    width: 100%;
}

.donut-chart-box {
    flex: 1.2;
}

.task-legend-list {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.task-legend-item {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.legend-color-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.legend-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}

.legend-value {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-left: auto;
}

/* Attendance & Glow layouts */
.attendance-summary-card {
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.attendance-charts-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
    justify-content: center;
    min-height: 200px;
}

@media(min-width: 768px) {
    .attendance-charts-container {
        flex-direction: row;
    }
}

.attendance-chart-box {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.8);
    border-radius: 12px;
    padding: 16px;
    position: relative;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.01);
}

.attendance-rate-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.attendance-quick-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -10px;
    text-align: center;
}

.quick-info-num {
    font-size: 22px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.2;
}

.quick-info-label {
    font-size: 10px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.attendance-dist-box {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.attendance-stats-row {
    display: flex;
    justify-content: space-around;
    width: 100%;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed rgba(226, 232, 240, 0.8);
}

.attendance-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
}

.stat-bullet {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-bottom: 4px;
}

.bg-green { background: #10b981; }
.bg-red { background: #ef4444; }
.bg-orange { background: #f59e0b; }

.stat-label {
    color: #64748b;
    font-size: 10px;
}

.stat-value {
    color: #1e293b;
    font-weight: 700;
    font-size: 11px;
}

/* Strengths & Improvements Glow Cards */
.strengths-card, .improvements-card {
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.glowing-target-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 0;
}

.glow-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    position: relative;
}

.glow-circle::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    animation: pulseGlow 2s infinite;
}

.green-glow {
    background: #ecfdf5;
    border: 2px solid #a7f3d0;
}
.green-glow::after {
    border: 2px solid #34d399;
}

.blue-glow {
    background: #eff6ff;
    border: 2px solid #bfdbfe;
}
.blue-glow::after {
    border: 2px solid #60a5fa;
}

@keyframes pulseGlow {
    0% { transform: scale(1); opacity: 0.8; }
    100% { transform: scale(1.3); opacity: 0; }
}

.glow-icon {
    font-size: 24px;
}

.glow-text {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-align: center;
}

.mini-chart-wrapper {
    min-height: 140px;
}

/* Appreciations Card */
.appreciations-card {
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
    min-height: 160px;
}

.appreciations-layout {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 5;
    flex-wrap: wrap;
    gap: 20px;
}

.appr-info-box {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1.2;
}

.party-popper-wrapper {
    width: 54px;
    height: 54px;
    border-radius: 14px;
    background: #fdf2f8;
    border: 1px solid #fbcfe8;
    display: flex;
    align-items: center;
    justify-content: center;
}

.emoji-popper {
    font-size: 28px;
}

.appr-text h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: #1e293b;
}

.appr-text p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.appr-chart-box {
    flex: 1.5;
    max-width: 450px;
    min-width: 250px;
}

.appr-graphic-box {
    margin-right: 24px;
}

.trophy-platform-container {
    position: relative;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.trophy-glowing-base {
    position: absolute;
    bottom: 5px;
    width: 50px;
    height: 10px;
    background: radial-gradient(circle, rgba(251, 191, 36, 0.6) 0%, rgba(251, 191, 36, 0) 70%);
    filter: blur(2px);
    animation: goldGlow 3s infinite alternate;
}

@keyframes goldGlow {
    0% { transform: scale(0.9); opacity: 0.5; }
    100% { transform: scale(1.1); opacity: 1; }
}

.emoji-trophy {
    font-size: 40px;
    filter: drop-shadow(0 4px 8px rgba(251, 191, 36, 0.3));
    z-index: 2;
}

.appr-bg-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    line-height: 0;
    z-index: 1;
}

.no-data-sm {
    text-align: center;
    padding: 24px 0;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

.att-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bg-light-green { background: #ecfdf5; }
.bg-light-red { background: #fef2f2; }
.bg-light-orange { background: #fffbeb; }
.bg-light-blue { background: #eff6ff; }

.att-icon {
    font-size: 18px;
}

.text-green { color: #10b981; }
.text-red { color: #ef4444; }
.text-orange { color: #f59e0b; }
.text-blue { color: #3b82f6; }

.att-content {
    display: flex;
    flex-direction: column;
    z-index: 2;
}

.att-number {
    font-size: 18px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1.1;
}

.att-label {
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    margin-top: 2px;
}

/* Custom point-label text boldness */
.font-bold-values :deep(.apexcharts-data-labels text) {
    font-weight: 800 !important;
}
</style>
