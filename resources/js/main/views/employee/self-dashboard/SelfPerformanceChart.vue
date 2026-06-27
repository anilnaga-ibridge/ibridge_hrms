<template>
    <a-card class="performance-chart-card">
        <template #title>
            Overall Performance
        </template>
        <template #extra>
            <router-link :to="{ name: 'admin.employee.performance.index' }">
                <a-button type="primary" size="small">View Details</a-button>
            </router-link>
        </template>

        <div class="chart-content">
            <RadialBar :value="overallScore" label="Overall Score" />
            <div class="performance-details" v-if="grade">
                <span class="grade-badge" :style="{ background: gradeColor }">{{ grade }}</span>
                <span class="rank-text" v-if="companyRank">Rank #{{ companyRank }}</span>
            </div>
        </div>
    </a-card>
</template>

<script>
import { ref, onMounted, computed, defineComponent } from "vue";
import common from "@/common/composable/common";
import RadialBar from "../../../components/charts/performance/RadialBar.vue";

export default defineComponent({
    components: {
        RadialBar,
    },
    setup() {
        const { dayjs } = common();
        const overallScore = ref(0);
        const grade = ref("");
        const companyRank = ref(null);

        const gradeColor = computed(() => {
            const colors = { "A+": "#22c55e", A: "#86efac", B: "#fbbf24", C: "#fb923c", D: "#ef4444" };
            return colors[grade.value] || "#999";
        });

        onMounted(() => {
            const month = dayjs().format("M");
            const year = dayjs().format("YYYY");
            axiosAdmin.get(`performance/self/dashboard?month=${month}&year=${year}`).then((res) => {
                if (res.data && res.data.current) {
                    overallScore.value = res.data.current.overall_score;
                    grade.value = res.data.current.grade;
                    companyRank.value = res.data.current.company_rank;
                }
            });
        });

        return {
            overallScore,
            grade,
            companyRank,
            gradeColor,
        };
    },
});
</script>

<style scoped>
.performance-chart-card {
    width: 100%;
    height: 100%;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}
.chart-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 10px 0;
}
.performance-details {
    display: flex;
    justify-content: center;
    gap: 12px;
    align-items: center;
    margin-top: 8px;
}
.grade-badge {
    display: inline-block;
    padding: 2px 12px;
    border-radius: 12px;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
}
.rank-text {
    font-size: 13px;
    color: #666;
    font-weight: 600;
}
</style>
