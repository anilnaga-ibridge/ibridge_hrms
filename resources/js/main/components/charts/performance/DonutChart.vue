<template>
    <div>
        <apexchart
            v-if="showChart"
            width="100%"
            height="320"
            type="donut"
            :options="chartOptions"
            :series="series"
        />
        <div v-else class="no-data">{{ $t('common.no_data') }}</div>
    </div>
</template>

<script>
import { computed } from "vue";
import { useI18n } from "vue-i18n";

export default {
    props: {
        data: {
            type: Array,
            default: () => [],
        },
        hideLegend: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const { t } = useI18n();
        const showChart = computed(() => props.data.length > 0 && props.data.some((v) => v.value > 0));

        const chartOptions = computed(() => ({
            chart: {
                type: "donut",
                height: 320,
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
            labels: props.data.map((d) => d.label),
            colors: props.data.map((d) => d.color || "#4facfe"),
            plotOptions: {
                pie: {
                    donut: {
                        size: "70%",
                        labels: {
                            show: true,
                            name: { show: true, fontSize: "14px", fontWeight: 600, color: "#333" },
                            value: {
                                show: true,
                                fontSize: "18px",
                                fontWeight: 800,
                                color: "#3a1a52",
                                formatter: (val) => val,
                            },
                            total: {
                                show: true,
                                label: t("common.total"),
                                fontSize: "13px",
                                fontWeight: 600,
                                color: "#999",
                                formatter: () => props.data.reduce((s, d) => s + d.value, 0),
                            },
                        },
                    },
                },
            },
            stroke: { show: true, width: 2, colors: ["#fff"] },
            dataLabels: {
                enabled: true,
                formatter: (val) => `${val.toFixed(0)}%`,
                style: { fontSize: "11px", fontWeight: 700, colors: ["#fff"] },
                dropShadow: { enabled: false },
            },
            legend: {
                show: !props.hideLegend,
                position: "bottom",
                fontSize: "12px",
                fontWeight: 500,
                markers: { radius: 12 },
            },
            tooltip: {
                y: {
                    formatter: (val) => `${val} ${t("menu.employees")}`,
                },
            },
            responsive: [
                {
                    breakpoint: 480,
                    options: {
                        legend: { position: "bottom" },
                    },
                },
            ],
        }));

        const series = computed(() => props.data.map((d) => d.value));

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
