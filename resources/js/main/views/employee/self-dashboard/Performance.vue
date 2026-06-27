<template>
    <a-card class="performance-card">
        <template #title>
            {{ $t("menu.appreciations") }}
        </template>
        <template #extra>
            <a-date-picker
                v-model:value="extraFilters.year"
                :placeholder="
                    $t('common.select_default_text', [$t('holiday.year')])
                "
                picker="year"
                style="width: 100%"
                :allowClear="false"
                @change="filterYearData(extraFilters.year)"
        /></template>

        <div class="chart-container">
            <apexchart
                type="area"
                height="280"
                :options="chartOptions"
                :series="chartSeries"
            />
        </div>
    </a-card>
</template>

<script>
import { ref, onMounted, defineComponent, watch } from "vue";
import { Card as ACard } from "ant-design-vue";
import common from "@/common/composable/common";

export default defineComponent({
    props: ["data"],
    components: {},
    setup(props, { emit }) {
        const { dayjs } = common();
        const filterMonthYear = ref();
        const extraFilters = ref({
            filterMonthYear: undefined,
        });

        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December",
        ];

        const chartSeries = ref([
            {
                name: "Appreciations",
                data: Array(12).fill(0),
            },
        ]);

        const chartOptions = ref({
            chart: {
                type: "area",
                height: 280,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: "easeinout",
                    speed: 1000,
                    dynamicAnimation: {
                        enabled: true,
                        speed: 600,
                    },
                },
            },
            colors: ["#6366F1"],
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
                            color: "#6366F1",
                            opacity: 0.45,
                        },
                        {
                            offset: 50,
                            color: "#8B5CF6",
                            opacity: 0.2,
                        },
                        {
                            offset: 100,
                            color: "#8B5CF6",
                            opacity: 0.02,
                        },
                    ],
                },
            },
            stroke: {
                curve: "smooth",
                width: 3,
                colors: ["#6366F1"],
            },
            markers: {
                size: 5,
                colors: ["#fff"],
                strokeColors: ["#6366F1"],
                strokeWidth: 3,
                hover: {
                    size: 8,
                    sizeOffset: 2,
                },
            },
            tooltip: {
                enabled: true,
                theme: "light",
                style: {
                    fontSize: "13px",
                    fontFamily: "Inter, sans-serif",
                },
                y: {
                    formatter: (val) => `${val} appreciation${val !== 1 ? "s" : ""}`,
                },
            },
            grid: {
                show: true,
                borderColor: "rgba(0,0,0,0.04)",
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: true } },
            },
            xaxis: {
                categories: months,
                labels: {
                    style: {
                        fontSize: "11px",
                        fontWeight: 600,
                        colors: "#9CA3AF",
                    },
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: {
                        fontSize: "11px",
                        fontWeight: 600,
                        colors: "#9CA3AF",
                    },
                    formatter: (val) => (Number.isInteger(val) ? val : ""),
                },
            },
        });

        const filterYearData = (yearValue) => {
            if (yearValue) {
                filterMonthYear.value = dayjs(yearValue).format("YYYY");
            }
            emit("employeeAppriciationData", {
                filterMonthYear: filterMonthYear.value,
                type: "employee_appriciation",
            });
        };

        watch(
            props,
            (newVal) => {
                if (!newVal) return;

                let dataArray = Array(12).fill(0);

                newVal.data?.appriciationByDate.forEach((item) => {
                    if (item.month_number >= 1 && item.month_number <= 12) {
                        dataArray[item.month_number - 1] = item.count;
                    }
                });

                chartSeries.value = [
                    {
                        name: "Appreciations",
                        data: dataArray,
                    },
                ];
            },
            { deep: true }
        );

        return {
            chartOptions,
            chartSeries,
            filterYearData,
            extraFilters,
        };
    },
});
</script>

<style scoped>
.performance-card {
    width: 100%;
    height: 99%;
}
.chart-container {
    width: 100%;
}
</style>
