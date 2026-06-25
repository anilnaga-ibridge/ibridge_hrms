<template>
    <div>
        <apexchart
            v-if="showChart"
            width="100%"
            height="320"
            type="bar"
            :options="chartOptions"
            :series="series"
        />
        <div v-else class="no-data">{{ $t('common.no_data') }}</div>
    </div>
</template>

<script>
import { computed } from "vue";

export default {
    props: {
        assigned: { type: Number, default: 0 },
        completed: { type: Number, default: 0 },
        pending: { type: Number, default: 0 },
        overdue: { type: Number, default: 0 },
    },
    setup(props) {
        const showChart = computed(() => props.assigned > 0 || props.completed > 0 || props.pending > 0 || props.overdue > 0);

        const chartOptions = computed(() => ({
            chart: {
                type: "bar",
                height: 320,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150,
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350,
                    },
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 2,
                    blur: 6,
                    color: "#999",
                    opacity: 0.1,
                },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "45%",
                    borderRadius: 6,
                    distributed: true,
                },
            },
            dataLabels: { enabled: false },
            stroke: { show: true, width: 2, colors: ["transparent"] },
            xaxis: {
                categories: ["Assigned", "Completed", "Pending", "Overdue"],
                labels: { style: { fontSize: "12px", fontWeight: 600, colors: "#999" } },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                tickAmount: 5,
                labels: {
                    formatter: (val) => Math.round(val),
                    style: { fontSize: "11px", fontWeight: 500, colors: "#999" },
                },
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: "light",
                    type: "vertical",
                    shadeIntensity: 0.2,
                    opacityFrom: 0.9,
                    opacityTo: 0.75,
                },
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} tasks`,
                },
            },
            colors: ["#3b82f6", "#10b981", "#f59e0b", "#ef4444"],
            grid: {
                borderColor: "#f3e8ff",
                strokeDashArray: 4,
            },
            legend: { show: false },
        }));

        const series = computed(() => [
            {
                name: "Tasks",
                data: [props.assigned, props.completed, props.pending, props.overdue],
            },
        ]);

        return { chartOptions, series, showChart };
    },
};
</script>

<style scoped>
.no-data {
    text-align: center;
    padding: 60px 0;
    color: #999;
}
</style>
