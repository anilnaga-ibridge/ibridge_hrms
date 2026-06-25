<template>
    <div>
        <apexchart
            v-if="showChart"
            width="100%"
            height="320"
            type="radialBar"
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
        value: {
            type: Number,
            default: 0,
        },
        label: {
            type: String,
            default: "Performance",
        },
        grade: {
            type: String,
            default: "",
        },
    },
    setup(props) {
        const showChart = computed(() => props.value > 0);

        const colorPalette = computed(() => {
            const val = props.value;
            if (val >= 90) {
                return {
                    primary: "#22c55e",
                    gradientTo: "#059669" // Green to Emerald
                };
            } else if (val >= 80) {
                return {
                    primary: "#a78bfa",
                    gradientTo: "#7c3aed" // Light Purple to Deep Purple
                };
            } else if (val >= 70) {
                return {
                    primary: "#fbbf24",
                    gradientTo: "#d97706" // Yellow to Orange
                };
            } else {
                return {
                    primary: "#f43f5e",
                    gradientTo: "#e11d48" // Rose to Crimson
                };
            }
        });

        const chartOptions = computed(() => ({
            chart: {
                type: "radialBar",
                height: 320,
                sparkline: { enabled: true },
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 1200, // Longer, smoother animation
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
                    top: 10,
                    left: 0,
                    blur: 15,
                    color: colorPalette.value.primary,
                    opacity: 0.25, // Glowing drop shadow
                },
            },
            plotOptions: {
                radialBar: {
                    startAngle: -135, // 270-degree speed gauge
                    endAngle: 135,
                    hollow: {
                        margin: 15,
                        size: "70%",
                        background: "transparent",
                    },
                    track: {
                        background: "#f3f4f6",
                        strokeWidth: "97%",
                        margin: 5,
                        dropShadow: {
                            enabled: true,
                            top: 2,
                            left: 0,
                            color: "#999",
                            opacity: 0.05,
                            blur: 2
                        }
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: "12px",
                            fontWeight: 700,
                            color: "#64748b",
                            offsetY: 20, // Offset bottom gap
                        },
                        value: {
                            show: true,
                            fontSize: "44px",
                            fontWeight: 800,
                            color: "#1e1b4b",
                            offsetY: -15,
                            formatter: () => props.grade || `${props.value.toFixed(1)}%`,
                        },
                    },
                },
            },
            fill: {
                type: "gradient",
                gradient: {
                    shade: "dark",
                    type: "horizontal",
                    shadeIntensity: 0.5,
                    gradientToColors: [colorPalette.value.gradientTo],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100],
                },
            },
            stroke: { lineCap: "round" },
            labels: [props.label],
            colors: [colorPalette.value.primary],
        }));

        const series = computed(() => [props.value]);

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
