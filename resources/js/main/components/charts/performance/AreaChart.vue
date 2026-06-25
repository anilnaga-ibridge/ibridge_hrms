<template>
    <div>
        <apexchart
            v-if="showChart"
            width="100%"
            height="320"
            type="area"
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
        data: {
            type: Array,
            default: () => [],
        },
        labels: {
            type: Array,
            default: () => [
                "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
            ],
        },
    },
    setup(props) {
        const showChart = computed(() => props.data.length > 0 && props.data.some((v) => v > 0));

        const chartOptions = computed(() => ({
            chart: {
                type: "area",
                height: 320,
                toolbar: { show: false },
                zoom: { enabled: false },
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
                    top: 8,
                    left: 0,
                    blur: 8,
                    color: "#852ca0",
                    opacity: 0.15,
                },
            },
            dataLabels: {
                enabled: true,
                offsetY: -6,
                style: {
                    fontSize: "11px",
                    fontWeight: 700,
                    colors: ["#3a1a52"],
                },
                background: {
                    enabled: true,
                    foreColor: "#3a1a52",
                    padding: 4,
                    borderRadius: 4,
                    borderWidth: 1,
                    borderColor: "#edd8fc",
                    opacity: 0.9,
                    dropShadow: {
                        enabled: false
                    }
                }
            },
            stroke: {
                curve: "smooth",
                width: 4,
                colors: ["#852ca0"],
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                    colorStops: [
                        {
                            offset: 0,
                            color: "#852ca0",
                            opacity: 0.4,
                        },
                        {
                            offset: 100,
                            color: "#4facfe",
                            opacity: 0.05,
                        },
                    ],
                },
            },
            xaxis: {
                categories: props.labels,
                labels: {
                    style: { fontSize: "11px", fontWeight: 500, colors: "#999" },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                max: 100,
                tickAmount: 5,
                labels: {
                    formatter: (val) => `${val.toFixed(0)}%`,
                    style: { fontSize: "11px", fontWeight: 500, colors: "#999" },
                },
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val.toFixed(1)}%`,
                },
                theme: "light",
            },
            grid: {
                borderColor: "#f3e8ff",
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
            },
            colors: ["#852ca0"],
            markers: {
                size: 6,
                colors: ["#852ca0"],
                strokeColors: "#fff",
                strokeWidth: 3,
                hover: { size: 8 },
            },
        }));

        const series = computed(() => [
            {
                name: "Performance",
                data: props.data,
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
