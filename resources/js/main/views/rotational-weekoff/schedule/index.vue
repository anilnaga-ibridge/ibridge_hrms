<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="'Week-Off Schedule'" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Rotational Weekoff</a-breadcrumb-item>
                <a-breadcrumb-item>Schedule</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
                <a-month-picker v-model:value="selectedMonth" @change="fetchSchedule" style="width: 100%" />
            </a-col>
            <a-col :xs="12" :sm="12" :md="6" :lg="4">
                <a-button type="primary" @click="autoGenerate" :loading="autoGenLoading" block>
                    Auto Generate
                </a-button>
            </a-col>
            <a-col :xs="12" :sm="12" :md="6" :lg="4">
                <a-button @click="clearSchedule" :loading="clearing" block danger>
                    Clear Schedule
                </a-button>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="10">
                <span style="color: #888; font-size: 12px;">
                    <strong>Rotation:</strong>
                    <span v-for="(team, i) in teams" :key="team.xid">
                        {{ team.name }}<template v-if="i < teams.length - 1"> → </template>
                    </span>
                    <span v-if="teams.length > 0"> → {{ teams[0].name }} (loop)</span>
                </span>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-table
            :columns="columns"
            :data-source="scheduleData"
            :row-key="(record) => record.date"
            :pagination="false"
            :loading="loading"
            bordered
            size="middle"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'week'">
                    <strong>Week {{ record.week }}</strong>
                    <br />
                    <small style="color: #999;">{{ record.date }}</small>
                </template>
                <template v-if="column.dataIndex === 'off_team'">
                    <a-select
                        v-model:value="record.selectedTeamXid"
                        style="width: 180px;"
                        @change="(val) => updateOffTeam(record.date, val)"
                        :allowClear="true"
                        :placeholder="'Select off team'"
                    >
                        <a-select-option
                            v-for="team in teams"
                            :key="team.xid"
                            :value="team.xid"
                        >
                            {{ team.name }} ({{ team.members_count }} members)
                        </a-select-option>
                    </a-select>
                </template>
            </template>
        </a-table>
        <div v-if="scheduleData.length === 0 && !loading" style="text-align: center; padding: 40px; color: #999;">
            No Saturdays found in selected month.
        </div>
    </admin-page-table-content>
</template>
<script>
import { ref, computed, onMounted } from "vue";
import dayjs from "dayjs";
import apiAdmin from "../../../../common/composable/apiAdmin";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: { AdminPageHeader },
    setup() {
        const { addEditRequestAdmin, loading } = apiAdmin();
        const selectedMonth = ref(dayjs());
        const scheduleData = ref([]);
        const teams = ref([]);
        const autoGenLoading = ref(false);
        const clearing = ref(false);

        const columns = [
            {
                title: "Saturday",
                dataIndex: "week",
                width: 140,
            },
            {
                title: "Team Having Week-Off",
                dataIndex: "off_team",
            },
        ];

        const fetchSchedule = () => {
            if (!selectedMonth.value) return;
            const start = selectedMonth.value.startOf("month").format("YYYY-MM-DD");
            const end = selectedMonth.value.endOf("month").format("YYYY-MM-DD");

            loading.value = true;
            window.axiosAdmin.post("rotational-teams/schedule", { start_date: start, end_date: end }).then((res) => {
                teams.value = res.teams || [];
                const rawData = res.data || [];
                scheduleData.value = rawData.map((item, idx) => ({
                    key: item.date,
                    week: idx + 1,
                    date: item.date,
                    off_team: item.off_team,
                    selectedTeamXid: item.off_team ? item.off_team.xid : undefined,
                }));
                loading.value = false;
            }).catch(() => {
                loading.value = false;
            });
        };

        const updateOffTeam = (date, teamXid) => {
            addEditRequestAdmin({
                url: "rotational-teams/update-schedule",
                data: { date, off_team_id: teamXid || null },
                success: () => {
                    fetchSchedule();
                },
            });
        };

        const autoGenerate = () => {
            if (!selectedMonth.value) return;
            const start = selectedMonth.value.startOf("month").format("YYYY-MM-DD");
            const end = selectedMonth.value.endOf("month").format("YYYY-MM-DD");

            autoGenLoading.value = true;
            addEditRequestAdmin({
                url: "rotational-teams/auto-generate",
                data: { start_date: start, end_date: end },
                success: () => {
                    autoGenLoading.value = false;
                    fetchSchedule();
                },
                error: () => { autoGenLoading.value = false; },
            });
        };

        const clearSchedule = () => {
            if (!selectedMonth.value) return;
            const start = selectedMonth.value.startOf("month").format("YYYY-MM-DD");
            const end = selectedMonth.value.endOf("month").format("YYYY-MM-DD");

            clearing.value = true;
            addEditRequestAdmin({
                url: "rotational-teams/clear-schedule",
                data: { start_date: start, end_date: end },
                success: () => {
                    clearing.value = false;
                    fetchSchedule();
                },
                error: () => { clearing.value = false; },
            });
        };

        onMounted(() => {
            fetchSchedule();
        });

        return {
            selectedMonth, scheduleData, teams, columns,
            loading, autoGenLoading, clearing,
            fetchSchedule, updateOffTeam, autoGenerate, clearSchedule,
        };
    },
};
</script>
