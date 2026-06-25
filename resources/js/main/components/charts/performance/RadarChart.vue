<template>
    <div>
        <apexchart
            v-if="showChart"
            width="100%"
            height="350"
            type="radar"
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
        categories: {
            type: Array,
            default: () => [],
        },
        data: {
            type: Array,
            default: () => [],
        },
    },
    setup(props) {
        const showChart = computed(() => props.data.length > 0 && props.data.some((v) => v > 0));

        const chartOptions = computed(() => ({
            chart: {
                type: "radar",
                height: 350,
                toolbar: { show: false },
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 1000,
                },
                dropShadow: {
                    enabled: true,
                    blur: 6,
                    left: 0,
                    top: 2,
                    color: "#852ca0",
                    opacity: 0.15,
                },
            },
            xaxis: {
                categories: props.categories,
                labels: {
                    style: {
                        fontSize: "11px",
                        fontWeight: 700,
                        colors: ["#3a1a52", "#3a1a52", "#3a1a52", "#3a1a52", "#3a1a52", "#3a1a52", "#3a1a52"],
                    },
                },
            },
            yaxis: {
                min: 0,
                max: 100,
                tickAmount: 5,
                show: false,
            },
            stroke: {
                width: 3,
                colors: ["#852ca0"],
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: "light",
                    shadeIntensity: 0.5,
                    inverseColors: false,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                },
                colors: ["#4facfe"],
            },
            markers: {
                size: 5,
                hover: { size: 7 },
                colors: ["#852ca0"],
                strokeWidth: 2,
                strokeColors: "#fff",
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val.toFixed(1)}%`,
                },
            },
            legend: { show: false },
            colors: ["#852ca0"],
            plotOptions: {
                radar: {
                    polygons: {
                        strokeColors: "#e2e8f0",
                        fill: {
                            colors: ["#f8fafc", "#fff"],
                        },
                    },
                },
            },
        }));

        const series = computed(() => [
            {
                name: "Score",
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
