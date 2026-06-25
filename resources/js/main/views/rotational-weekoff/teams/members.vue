<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header
                :title="teamName ? teamName + ' - Members' : 'Team Members'"
                class="p-0"
                @back="() => $router.push({ name: 'admin.rotational-weekoff.teams' })"
            />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.rotational-weekoff.teams' }">Rotational Teams</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Members</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-table-content>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12">
                <a-card title="Current Members" size="small">
                    <template #extra>
                        <a-button type="link" @click="showAddEmployeeModal = true">+ Add</a-button>
                    </template>
                    <a-table
                        :columns="memberColumns"
                        :data-source="currentMembers"
                        :row-key="(record) => record.xid"
                        :loading="loading"
                        bordered
                        size="small"
                        :pagination="false"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'action'">
                                <a-select
                                    v-model:value="transferTarget[record.xid]"
                                    style="width: 140px;"
                                    :placeholder="'Transfer to...'"
                                    @change="(val) => transferMember(record.xid, val)"
                                >
                                    <a-select-option
                                        v-for="team in otherTeams"
                                        :key="team.xid"
                                        :value="team.xid"
                                    >
                                        {{ team.name }}
                                    </a-select-option>
                                </a-select>
                                <a-button type="link" danger @click="removeMember(record.xid)" size="small">
                                    <DeleteOutlined />
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12">
                <a-card title="Available Employees" size="small">
                    <a-table
                        :columns="availableColumns"
                        :data-source="availableEmployees"
                        :row-key="(record) => record.xid"
                        :loading="availableLoading"
                        bordered
                        size="small"
                        :pagination="false"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'action'">
                                <a-button type="primary" @click="addMember(record.xid)" size="small">
                                    <PlusOutlined /> Add
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </a-card>
            </a-col>
        </a-row>

        <!-- Add Employee Modal -->
        <a-modal
            v-model:open="showAddEmployeeModal"
            :title="'Add Employees to ' + teamName"
            @ok="addSelectedEmployees"
            :okButtonProps="{ disabled: selectedAddEmployees.length === 0 }"
        >
            <a-select
                v-model:value="selectedAddEmployees"
                mode="multiple"
                style="width: 100%"
                placeholder="Select employees"
                :options="availableEmployeeOptions"
                :loading="availableLoading"
            />
        </a-modal>
    </admin-page-table-content>
</template>
<script>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { PlusOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: { PlusOutlined, DeleteOutlined, AdminPageHeader },
    setup() {
        const route = useRoute();
        const teamXid = route.params.xid;
        const { addEditRequestAdmin, loading } = apiAdmin();

        const teamName = ref("");
        const currentMembers = ref([]);
        const availableEmployees = ref([]);
        const availableLoading = ref(false);
        const allTeams = ref([]);
        const transferTarget = ref({});
        const showAddEmployeeModal = ref(false);
        const selectedAddEmployees = ref([]);

        const otherTeams = computed(() =>
            allTeams.value.filter((t) => t.xid !== teamXid)
        );

        const availableEmployeeOptions = computed(() =>
            availableEmployees.value.map((emp) => ({
                label: emp.name + (emp.designation ? " - " + emp.designation : ""),
                value: emp.xid,
            }))
        );

        const memberColumns = [
            { title: "Name", dataIndex: "name" },
            { title: "Designation", dataIndex: "designation" },
            { title: "Actions", dataIndex: "action" },
        ];

        const availableColumns = [
            { title: "Name", dataIndex: "name" },
            { title: "Designation", dataIndex: "designation" },
            { title: "Action", dataIndex: "action" },
        ];

        const fetchTeam = () => {
            window.axiosAdmin.post("rotational-teams", {}).then((res) => {
                const teams = res.data || [];
                allTeams.value = teams;
                const current = teams.find((t) => t.xid === teamXid);
                if (current) teamName.value = current.name;
            });
        };

        const fetchMembers = () => {
            loading.value = true;
            window.axiosAdmin.post("rotational-teams/members", { team_id: teamXid }).then((res) => {
                currentMembers.value = res.data || [];
                loading.value = false;
            }).catch(() => {
                loading.value = false;
            });
        };

        const fetchAvailable = () => {
            availableLoading.value = true;
            window.axiosAdmin.post("rotational-teams/available-employees", {}).then((res) => {
                availableEmployees.value = res.data || [];
                availableLoading.value = false;
            }).catch(() => {
                availableLoading.value = false;
            });
        };

        const addMember = (userXid) => {
            const ids = [...currentMembers.value.map((m) => m.xid), userXid];
            addEditRequestAdmin({
                url: "rotational-teams/assign-members",
                data: { team_id: teamXid, user_ids: ids },
                success: () => {
                    fetchMembers();
                    fetchAvailable();
                },
            });
        };

        const addSelectedEmployees = () => {
            const ids = [
                ...currentMembers.value.map((m) => m.xid),
                ...selectedAddEmployees.value,
            ];
            addEditRequestAdmin({
                url: "rotational-teams/assign-members",
                data: { team_id: teamXid, user_ids: ids },
                success: () => {
                    showAddEmployeeModal.value = false;
                    selectedAddEmployees.value = [];
                    fetchMembers();
                    fetchAvailable();
                },
            });
        };

        const removeMember = (userXid) => {
            const remaining = currentMembers.value
                .filter((m) => m.xid !== userXid)
                .map((m) => m.xid);
            addEditRequestAdmin({
                url: "rotational-teams/assign-members",
                data: { team_id: teamXid, user_ids: remaining },
                success: () => {
                    fetchMembers();
                    fetchAvailable();
                },
            });
        };

        const transferMember = (userXid, toTeamXid) => {
            addEditRequestAdmin({
                url: "rotational-teams/transfer-member",
                data: { employee_id: userXid, to_team_id: toTeamXid },
                success: () => {
                    transferTarget.value[userXid] = undefined;
                    fetchMembers();
                    fetchAvailable();
                },
            });
        };

        onMounted(() => {
            fetchTeam();
            fetchMembers();
            fetchAvailable();
        });

        return {
            teamName, currentMembers, availableEmployees, otherTeams,
            memberColumns, availableColumns, loading, availableLoading,
            showAddEmployeeModal, selectedAddEmployees, availableEmployeeOptions,
            addMember, addSelectedEmployees, removeMember, transferMember,
            transferTarget,
        };
    },
};
</script>
