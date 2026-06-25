<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t('menu.shift_roster')" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t("menu.shift_roster") }}
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-row>
        <a-col :xs="24" :sm="24" :md="24" :lg="24" :xl="24">
            <admin-page-table-content>
                <a-card class="page-content-container mt-20 mb-20">
                    <a-row :gutter="16" class="mb-20" align="middle">
                        <a-col :xs="24" :sm="12" :md="8" :lg="6">
                            <a-select
                                v-model:value="selectedUserId"
                                :placeholder="$t('shift_roster.select_user')"
                                style="width: 100%"
                                allowClear
                                show-search
                                optionFilterProp="title"
                                @change="onUserChange"
                            >
                                <a-select-option
                                    v-for="user in users"
                                    :key="user.xid"
                                    :value="user.xid"
                                    :title="user.name"
                                >
                                    {{ user.name }}
                                </a-select-option>
                            </a-select>
                        </a-col>
                        <a-col :xs="24" :sm="12" :md="8" :lg="6">
                            <a-space>
                                <a-button @click="previousWeek">
                                    <LeftOutlined />
                                </a-button>
                                <span style="font-weight: 500; white-space: nowrap;">
                                    {{ weekRangeLabel }}
                                </span>
                                <a-button @click="nextWeek">
                                    <RightOutlined />
                                </a-button>
                            </a-space>
                        </a-col>
                        <a-col :xs="24" :sm="24" :md="8" :lg="6">
                            <a-button type="primary" @click="goToCurrentWeek">
                                {{ $t("shift_roster.today") }}
                            </a-button>
                        </a-col>
                    </a-row>

                    <template v-if="!selectedUserId">
                        <a-empty :description="$t('shift_roster.select_user_prompt')" />
                    </template>

                    <template v-else-if="currentUserRoster">
                        <a-row :gutter="12">
                            <a-col
                                v-for="(day, index) in currentUserRoster.rosters"
                                :key="index"
                                :xs="12"
                                :sm="8"
                                :md="6"
                                :lg="3"
                                :xl="3"
                            >
                                <a-card
                                    size="small"
                                    class="roster-day-card"
                                    :class="{ 'is-today': isToday(day.date) }"
                                >
                                    <div class="day-header" style="text-align: center;">
                                        <div class="day-name" style="font-weight: 600; font-size: 13px;">
                                            {{ formatDayName(day.date) }}
                                        </div>
                                        <div class="day-number" style="font-size: 24px; font-weight: 700; line-height: 1.2;">
                                            {{ formatDayNumber(day.date) }}
                                        </div>
                                        <div class="day-month" style="font-size: 11px; color: #888;">
                                            {{ formatMonth(day.date) }}
                                        </div>
                                    </div>
                                    <div style="margin-top: 8px; text-align: center; min-height: 40px;">
                                        <template v-if="day.shift">
                                            <a-tag
                                                color="blue"
                                                style="cursor: pointer; margin: 2px; white-space: normal; height: auto; line-height: 1.4;"
                                                @click="openShiftSelector(day.date)"
                                            >
                                                {{ day.shift.name }}
                                            </a-tag>
                                            <br />
                                            <a-button
                                                type="link"
                                                size="small"
                                                danger
                                                @click="removeRoster(day.date)"
                                                style="font-size: 11px; padding: 0;"
                                            >
                                                {{ $t("common.remove") }}
                                            </a-button>
                                        </template>
                                        <template v-else>
                                            <a-button
                                                type="dashed"
                                                size="small"
                                                @click="openShiftSelector(day.date)"
                                                style="width: 100%;"
                                            >
                                                <PlusOutlined />
                                                {{ $t("shift_roster.assign") }}
                                            </a-button>
                                        </template>
                                    </div>
                                </a-card>
                            </a-col>
                        </a-row>
                    </template>
                </a-card>
            </admin-page-table-content>
        </a-col>
    </a-row>

    <a-modal
        :open="shiftSelectorVisible"
        :title="$t('shift_roster.select_shift')"
        @cancel="shiftSelectorVisible = false"
        @ok="confirmShift"
        :okText="$t('common.confirm')"
        :cancelText="$t('common.cancel')"
    >
        <a-select
            v-model:value="selectedShiftId"
            :placeholder="$t('shift_roster.select_shift')"
            style="width: 100%"
            show-search
            optionFilterProp="title"
        >
            <a-select-option
                v-for="shift in shifts"
                :key="shift.xid"
                :value="shift.xid"
                :title="shift.name"
            >
                {{ shift.name }}
                ({{ shift.clock_in_time }} - {{ shift.clock_out_time }})
            </a-select-option>
        </a-select>
    </a-modal>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import dayjs from "dayjs";
import weekday from "dayjs/plugin/weekday";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import common from "../../../../common/composable/common";
import { LeftOutlined, RightOutlined, PlusOutlined } from "@ant-design/icons-vue";

dayjs.extend(weekday);

export default defineComponent({
    components: {
        AdminPageHeader,
        LeftOutlined,
        RightOutlined,
        PlusOutlined,
    },
    setup() {
        const { t } = useI18n();
        const { appSetting } = common();

        const users = ref([]);
        const shifts = ref([]);
        const rosterData = ref(null);
        const dates = ref([]);
        const selectedUserId = ref(undefined);
        const currentWeekStart = ref(dayjs().startOf("week").add(1, "day"));

        const shiftSelectorVisible = ref(false);
        const selectedDateForShift = ref(null);
        const selectedShiftId = ref(undefined);

        const weekRangeLabel = computed(() => {
            if (dates.value.length < 2) return "";
            const start = dayjs(dates.value[0]);
            const end = dayjs(dates.value[dates.value.length - 1]);
            return `${start.format("D MMM")} — ${end.format("D MMM")}`;
        });

        const currentUserRoster = computed(() => {
            if (!rosterData.value || !selectedUserId.value) return null;
            return rosterData.value.find(r => r.user.xid === selectedUserId.value) || null;
        });

        const fetchUsers = async () => {
            try {
                const response = await axios.get("/api/v1/users?fields=id,xid,name");
                users.value = response.data.data || [];
            } catch (e) {
                users.value = [];
            }
        };

        const fetchShifts = async () => {
            try {
                const response = await axios.get("/api/v1/shifts?fields=id,xid,name,clock_in_time,clock_out_time");
                shifts.value = response.data.data || [];
            } catch (e) {
                shifts.value = [];
            }
        };

        const fetchRoster = async () => {
            if (!selectedUserId.value) {
                rosterData.value = null;
                dates.value = [];
                return;
            }

            const startDate = currentWeekStart.value.format("YYYY-MM-DD");
            const endDate = currentWeekStart.value.endOf("week").add(1, "day").format("YYYY-MM-DD");

            try {
                const response = await axios.post("/api/v1/shift-rosters/weekly", {
                    user_id: selectedUserId.value,
                    start_date: startDate,
                    end_date: endDate,
                });
                rosterData.value = response.data.data || [];
                dates.value = response.data.dates || [];
            } catch (e) {
                rosterData.value = null;
                dates.value = [];
            }
        };

        const onUserChange = () => {
            fetchRoster();
        };

        const formatDayName = (date) => dayjs(date).format("dddd");
        const formatDayNumber = (date) => dayjs(date).format("D");
        const formatMonth = (date) => dayjs(date).format("MMM").toUpperCase();
        const isToday = (date) => dayjs(date).isSame(dayjs(), "day");

        const previousWeek = () => {
            currentWeekStart.value = currentWeekStart.value.subtract(7, "day");
            fetchRoster();
        };

        const nextWeek = () => {
            currentWeekStart.value = currentWeekStart.value.add(7, "day");
            fetchRoster();
        };

        const goToCurrentWeek = () => {
            currentWeekStart.value = dayjs().startOf("week").add(1, "day");
            fetchRoster();
        };

        const openShiftSelector = (date) => {
            selectedDateForShift.value = date;
            selectedShiftId.value = undefined;
            if (currentUserRoster.value) {
                const day = currentUserRoster.value.rosters.find(r => r.date === date);
                if (day && day.shift) {
                    selectedShiftId.value = day.shift.xid;
                }
            }
            shiftSelectorVisible.value = true;
        };

        const confirmShift = async () => {
            if (!selectedDateForShift.value || !selectedShiftId.value || !selectedUserId.value) {
                shiftSelectorVisible.value = false;
                return;
            }

            try {
                await axios.post("/api/v1/shift-rosters/assign", {
                    user_id: selectedUserId.value,
                    shift_id: selectedShiftId.value,
                    date: selectedDateForShift.value,
                });
                shiftSelectorVisible.value = false;
                fetchRoster();
            } catch (e) {
                shiftSelectorVisible.value = false;
            }
        };

        const removeRoster = async (date) => {
            if (!selectedUserId.value) return;

            try {
                await axios.post("/api/v1/shift-rosters/remove", {
                    user_id: selectedUserId.value,
                    date: date,
                });
                fetchRoster();
            } catch (e) {
                // silent
            }
        };

        onMounted(() => {
            fetchUsers();
            fetchShifts();
        });

        return {
            users,
            shifts,
            rosterData,
            dates,
            selectedUserId,
            currentWeekStart,
            weekRangeLabel,
            currentUserRoster,
            shiftSelectorVisible,
            selectedDateForShift,
            selectedShiftId,
            onUserChange,
            fetchRoster,
            formatDayName,
            formatDayNumber,
            formatMonth,
            isToday,
            previousWeek,
            nextWeek,
            goToCurrentWeek,
            openShiftSelector,
            confirmShift,
            removeRoster,
        };
    },
});
</script>

<style scoped>
.roster-day-card {
    border-radius: 8px;
    transition: box-shadow 0.2s;
    margin-bottom: 8px;
}

.roster-day-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.09);
}

.roster-day-card.is-today {
    border-color: #1890ff;
    background-color: #e6f7ff;
}

.mb-20 {
    margin-bottom: 20px;
}

.mt-20 {
    margin-top: 20px;
}
</style>
