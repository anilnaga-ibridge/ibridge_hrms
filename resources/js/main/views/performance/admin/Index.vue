<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t('menu.employee_performance')" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t('menu.dashboard') }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ $t('menu.employee_performance') }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="dashboard-page-content-container">
        <div class="mb-20">
            <a-row :gutter="[16, 16]" class="mb-20">
                <a-col :xs="24" :sm="12" :md="8" :lg="6" :xl="4">
                    <a-space>
                        <a-date-picker
                            v-model:value="filters.month"
                            picker="month"
                            :allowClear="false"
                            style="width: 100%"
                            @change="fetchData"
                        />
                        <a-button type="primary" @click="recalculate" :loading="calculating">
                            <ReloadOutlined /> {{ $t('performance.calculate') }}
                        </a-button>
                    </a-space>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 16]" class="mb-20">
                <a-col :xs="24" :sm="12" :md="12" :lg="6" :xl="6">
                    <a-card class="kpi-card" :style="{ borderTop: '3px solid #4facfe' }">
                        <statistic :title="$t('performance.overall_performance')" :value="overallAvg" suffix="%" :precision="1" :value-style="{ color: '#4facfe' }" />
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="12" :lg="6" :xl="6">
                    <a-card class="kpi-card" :style="{ borderTop: '3px solid #22c55e' }">
                        <statistic :title="$t('performance.top_performer')" :value="topPerformerName" :value-style="{ color: '#22c55e', fontSize: '16px' }" />
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="12" :lg="6" :xl="6">
                    <a-card class="kpi-card" :style="{ borderTop: '3px solid #fbbf24' }">
                        <statistic :title="$t('performance.average_score')" :value="overallAvg" suffix="%" :precision="1" :value-style="{ color: '#fbbf24' }" />
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="12" :md="12" :lg="6" :xl="6">
                    <a-card class="kpi-card" :style="{ borderTop: '3px solid #a78bfa' }">
                        <statistic :title="$t('performance.employees_scored')" :value="totalEmployees" :value-style="{ color: '#a78bfa' }" />
                    </a-card>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 16]" class="mb-20">
                <a-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8">
                    <a-card :title="$t('performance.score_distribution')">
                        <DonutChart :data="scoreDistribution" />
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="24" :md="24" :lg="16" :xl="16">
                    <a-card :title="$t('performance.department_performance')">
                        <apexchart
                            v-if="deptChartData.length > 0"
                            width="100%"
                            height="320"
                            type="bar"
                            :options="deptChartOptions"
                            :series="deptSeries"
                        />
                        <div v-else class="no-data">{{ $t('common.no_data') }}</div>
                    </a-card>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 16]" class="mb-20">
                <a-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                    <a-card :title="$t('performance.top_performers')">
                        <a-table :data-source="topPerformers" :columns="topPerformerColumns" :pagination="false" size="small" bordered :loading="loading">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'rank'">
                                    <span :class="getRankClass(record.company_rank)">{{ record.company_rank }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'employee'">
                                    <span>{{ record.user?.name || 'N/A' }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'score'">
                                    <span :style="{ color: getScoreColor(record.overall_score), fontWeight: 600 }">{{ (record.overall_score ?? 0).toFixed(1) }}%</span>
                                </template>
                                <template v-if="column.dataIndex === 'grade'">
                                    <a-tag :color="getGradeColor(record.grade)">{{ record.grade }}</a-tag>
                                </template>
                            </template>
                        </a-table>
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                    <a-card :title="$t('performance.employees_at_risk')">
                        <a-table :data-source="atRisk" :columns="riskColumns" :pagination="false" size="small" bordered :loading="loading">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'employee'">
                                    <span>{{ record.user?.name || 'N/A' }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'score'">
                                    <span style="color: #ef4444; font-weight: 600">{{ (record.overall_score ?? 0).toFixed(1) }}%</span>
                                </template>
                                <template v-if="column.dataIndex === 'grade'">
                                    <a-tag color="red">{{ record.grade }}</a-tag>
                                </template>
                            </template>
                        </a-table>
                    </a-card>
                </a-col>
            </a-row>

            <a-row :gutter="[16, 16]" class="mb-20">
                <a-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                    <a-card :title="$t('performance.promotion_recommendations')">
                        <a-table :data-source="promotions" :columns="promotionColumns" :pagination="false" size="small" bordered :loading="loading">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'employee'">
                                    <span>{{ record.user?.name || 'N/A' }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'score'">
                                    <span style="color: #22c55e; font-weight: 600">{{ (record.overall_score ?? 0).toFixed(1) }}%</span>
                                </template>
                            </template>
                        </a-table>
                    </a-card>
                </a-col>
                <a-col :xs="24" :sm="24" :md="24" :lg="12" :xl="12">
                    <a-card :title="$t('performance.needs_training')">
                        <a-table :data-source="needsTraining" :columns="trainingColumns" :pagination="false" size="small" bordered :loading="loading">
                            <template #bodyCell="{ column, record }">
                                <template v-if="column.dataIndex === 'employee'">
                                    <span>{{ record.user?.name || 'N/A' }}</span>
                                </template>
                                <template v-if="column.dataIndex === 'score'">
                                    <span style="color: #fb923c; font-weight: 600">{{ (record.overall_score ?? 0).toFixed(1) }}%</span>
                                </template>
                                <template v-if="column.dataIndex === 'area'">
                                    <a-tag color="orange">{{ getWeakestArea(record) }}</a-tag>
                                </template>
                            </template>
                        </a-table>
                    </a-card>
                </a-col>
            </a-row>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, reactive, computed } from "vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import { ReloadOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import DonutChart from "../../../components/charts/performance/DonutChart.vue";
import { useI18n } from "vue-i18n";

export default {
    components: { AdminPageHeader, ReloadOutlined, DonutChart },
    setup() {
        const { dayjs } = common();
        const { t } = useI18n();
        const loading = ref(false);
        const calculating = ref(false);
        const filters = reactive({
            month: dayjs(),
        });

        const overallAvg = ref(0);
        const topPerformerName = ref("");
        const totalEmployees = ref(0);
        const scoreDistribution = ref([]);
        const deptData = ref([]);
        const topPerformers = ref([]);
        const atRisk = ref([]);
        const promotions = ref([]);
        const needsTraining = ref([]);

        const getMonth = () => filters.month.format("M");
        const getYear = () => filters.month.format("YYYY");

        const deptChartData = computed(() => deptData.value.map((d) => d.avg_score || 0));
        const deptLabels = computed(() => deptData.value.map((d) => d.name));

        const deptChartOptions = computed(() => ({
            chart: { type: "bar", height: 320, toolbar: { show: false } },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                },
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => `${val.toFixed(1)}%`,
            },
            xaxis: {
                categories: deptLabels.value,
                labels: { formatter: (val) => `${val.toFixed(0)}%` },
            },
            colors: ["#4facfe"],
            tooltip: {
                y: { formatter: (val) => `${val.toFixed(1)}%` },
            },
            legend: { show: false },
        }));

        const deptSeries = computed(() => [
            { name: "Avg Score", data: deptChartData.value },
        ]);

        const topPerformerColumns = [
            { title: "#", dataIndex: "rank", width: 40 },
            { title: t("performance.employee"), dataIndex: "employee" },
            { title: t("performance.score"), dataIndex: "score", align: "center" },
            { title: t("performance.grade"), dataIndex: "grade", align: "center" },
        ];

        const riskColumns = [
            { title: t("performance.employee"), dataIndex: "employee" },
            { title: t("performance.score"), dataIndex: "score", align: "center" },
            { title: t("performance.grade"), dataIndex: "grade", align: "center" },
        ];

        const promotionColumns = [
            { title: t("performance.employee"), dataIndex: "employee" },
            { title: t("performance.score"), dataIndex: "score", align: "center" },
            { title: t("performance.current_designation"), dataIndex: "designation" },
        ];

        const trainingColumns = [
            { title: t("performance.employee"), dataIndex: "employee" },
            { title: t("performance.score"), dataIndex: "score", align: "center" },
            { title: t("performance.area"), dataIndex: "area" },
        ];

        const fetchData = () => {
            loading.value = true;
            const month = getMonth();
            const year = getYear();

            Promise.all([
                axiosAdmin.get(`performance/ranking?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/score-distribution?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/department-performance?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/top-performers?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/at-risk?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/promotion-recommendations?month=${month}&year=${year}`),
                axiosAdmin.get(`performance/needs-training?month=${month}&year=${year}`),
            ]).then(([rankingRes, distRes, deptRes, topRes, riskRes, promoRes, trainingRes]) => {
                const ranking = rankingRes.data;
                totalEmployees.value = ranking.length;
                overallAvg.value = ranking.length > 0
                    ? ranking.reduce((s, r) => s + r.overall_score, 0) / ranking.length
                    : 0;
                topPerformerName.value = ranking.length > 0 ? (ranking[0].user?.name || "") : "";

                scoreDistribution.value = distRes.data;
                deptData.value = deptRes.data;
                topPerformers.value = topRes.data;
                atRisk.value = riskRes.data;
                promotions.value = promoRes.data;
                needsTraining.value = trainingRes.data;
                loading.value = false;
            }).catch(() => { loading.value = false; });
        };

        const recalculate = () => {
            calculating.value = true;
            axiosAdmin.post("performance/calculate", {
                month: getMonth(),
                year: getYear(),
            }).then(() => {
                calculating.value = false;
                fetchData();
            }).catch(() => { calculating.value = false; });
        };

        const getRankClass = (rank) => {
            if (rank === 1) return "rank-gold";
            if (rank === 2) return "rank-silver";
            if (rank === 3) return "rank-bronze";
            return "";
        };

        const getScoreColor = (score) => {
            if (score >= 90) return "#22c55e";
            if (score >= 80) return "#86efac";
            if (score >= 70) return "#fbbf24";
            if (score >= 60) return "#fb923c";
            return "#ef4444";
        };

        const getGradeColor = (grade) => {
            const colors = { "A+": "green", A: "lime", B: "gold", C: "orange", D: "red" };
            return colors[grade] || "default";
        };

        const getWeakestArea = (record) => {
            const areas = [
                { label: "Attendance", val: record.attendance_score },
                { label: "Productivity", val: record.productivity_score },
                { label: "Communication", val: record.communication_score },
                { label: "Leadership", val: record.leadership_score },
                { label: "Discipline", val: record.discipline_score },
                { label: "Teamwork", val: record.teamwork_score },
                { label: "Task Completion", val: record.task_completion_score },
            ];
            const weakest = areas.reduce((min, a) => (a.val < min.val ? a : min), areas[0]);
            return weakest.label;
        };

        onMounted(fetchData);

        return {
            filters, loading, calculating,
            overallAvg, topPerformerName, totalEmployees,
            scoreDistribution, deptData, deptChartData, deptChartOptions, deptSeries,
            topPerformers, topPerformerColumns,
            atRisk, riskColumns,
            promotions, promotionColumns,
            needsTraining, trainingColumns,
            fetchData, recalculate,
            getRankClass, getScoreColor, getGradeColor, getWeakestArea,
        };
    },
};
</script>

<style scoped>
.kpi-card {
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}
.no-data {
    text-align: center;
    padding: 60px 0;
    color: #999;
}
.rank-gold { color: #d4a017; font-weight: 700; }
.rank-silver { color: #a0a0a0; font-weight: 700; }
.rank-bronze { color: #cd7f32; font-weight: 700; }
</style>
