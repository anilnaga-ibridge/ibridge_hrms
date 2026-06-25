<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="'Rotational Teams'" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">{{ $t('menu.dashboard') }}</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Rotational Weekoff</a-breadcrumb-item>
                <a-breadcrumb-item>Teams</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <template v-if="permsArray.includes('rotational_teams_create') || permsArray.includes('admin')">
                        <a-button type="primary" @click="addItem">
                            <PlusOutlined />
                            Add Team
                        </a-button>
                    </template>
                    <a-button
                        v-if="table.selectedRowKeys.length > 0 && (permsArray.includes('rotational_teams_delete') || permsArray.includes('admin'))"
                        type="primary" @click="showSelectedDeleteConfirm" danger
                    >
                        <template #icon><DeleteOutlined /></template>
                        {{ $t("common.delete") }}
                    </a-button>
                    <a-tag v-if="unassignedCount > 0" color="orange">
                        {{ unassignedCount }} employee(s) not assigned to any team
                    </a-tag>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="12" :xl="8">
                        <a-input-group compact>
                            <a-select style="width: 25%" v-model:value="table.searchColumn" :placeholder="$t('common.select_default_text', [''])">
                                <a-select-option v-for="filterableColumn in filterableColumns" :key="filterableColumn.key">
                                    {{ filterableColumn.value }}
                                </a-select-option>
                            </a-select>
                            <a-input-search
                                style="width: 75%"
                                v-model:value="table.searchString"
                                show-search
                                @change="onTableSearch"
                                @search="onTableSearch"
                                :loading="table.filterLoading"
                                :placeholder="$t('common.placeholder_search_text', ['Name'])"
                            />
                        </a-input-group>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <AddEdit
            :addEditType="addEditType"
            :visible="addEditVisible"
            :url="addEditUrl"
            @addEditSuccess="addEditSuccess"
            @closed="onCloseAddEdit"
            :formData="formData"
            :data="viewData"
            :pageTitle="pageTitle"
            :successMessage="successMessage"
            :prepopulateUnassigned="prepopulateUnassigned"
            :unassignedEmployees="unassignedEmployees"
        />

        <!-- Unassigned Employees Modal -->
        <a-modal
            v-model:open="unassignedModalVisible"
            title="Unassigned Employees"
            :footer="null"
            width="600px"
        >
            <div style="margin-bottom: 16px;">
                <strong>{{ unassignedCount }}</strong> new employee(s) have been added. Choose an option:
            </div>
            <div v-if="unassignedEmployees.length > 0" style="margin-bottom: 16px;">
                <a-table
                    :data-source="unassignedEmployees"
                    :columns="unassignedColumns"
                    :row-key="(r) => r.xid"
                    :pagination="false"
                    size="small"
                    bordered
                />
            </div>
            <a-divider />
            <div style="margin-bottom: 16px;">
                <a-radio-group v-model:value="unassignedOption" style="display: flex; flex-direction: column; gap: 10px;">
                    <a-radio value="existing" :disabled="teamsList.length === 0">
                        Add employees to existing teams
                    </a-radio>
                    <a-radio value="new">
                        Create a new team
                    </a-radio>
                </a-radio-group>
            </div>
            <a-space style="width: 100%; justify-content: flex-end;">
                <a-button @click="handleAssignLater">Assign later</a-button>
                <a-button type="primary" :disabled="!unassignedOption" @click="submitUnassignedAction">
                    Submit
                </a-button>
            </a-space>
        </a-modal>

        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: table.selectedRowKeys,
                            onChange: onRowSelectChange,
                            getCheckboxProps: (record) => ({ disabled: false, name: record.xid }),
                        }"
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="table.data"
                        :pagination="table.pagination"
                        :loading="table.loading"
                        @change="handleTableChange"
                        bordered
                        size="middle"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'is_active'">
                                <a-switch
                                    v-model:checked="record.is_active"
                                    :checkedValue="1"
                                    :unCheckedValue="0"
                                    @change="toggleActive(record)"
                                    size="small"
                                />
                            </template>
                            <template v-if="column.dataIndex === 'members_count'">
                                <a-button type="link" @click="openMembers(record)">
                                    {{ record.members_count || 0 }} members
                                </a-button>
                            </template>
                            <template v-if="column.dataIndex === 'action'">
                                <a-button
                                    v-if="permsArray.includes('rotational_teams_edit') || permsArray.includes('admin')"
                                    type="primary" @click="editItem(record)" style="margin-left: 4px"
                                >
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button
                                    type="primary" @click="openMembers(record)" style="margin-left: 4px"
                                >
                                    <template #icon><TeamOutlined /></template>
                                </a-button>
                                <a-button
                                    v-if="permsArray.includes('rotational_teams_delete') || permsArray.includes('admin')"
                                    type="primary" @click="showDeleteConfirm(record.xid)" style="margin-left: 4px"
                                >
                                    <template #icon><DeleteOutlined /></template>
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
</template>
<script>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { PlusOutlined, EditOutlined, DeleteOutlined, TeamOutlined } from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { notification } from "ant-design-vue";
import fields from "./fields";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import apiAdmin from "../../../../common/composable/apiAdmin";
import AddEdit from "./AddEdit.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: { PlusOutlined, EditOutlined, DeleteOutlined, TeamOutlined, AddEdit, AdminPageHeader },
    setup() {
        const { t } = useI18n();
        const { addEditUrl, initData, columns, filterableColumns } = fields();
        const crudVariables = crud();
        const { permsArray, appSetting } = common();
        const { addEditRequestAdmin } = apiAdmin();
        const router = useRouter();

        const unassignedModalVisible = ref(false);
        const unassignedCount = ref(0);
        const unassignedEmployees = ref([]);
        const teamsList = ref([]);
        const unassignedOption = ref("existing");
        const prepopulateUnassigned = ref(false);

        const unassignedColumns = [
            { title: "Name", dataIndex: "name" },
            { title: "Designation", dataIndex: "designation" },
        ];

        const checkUnassignedEmployees = () => {
            window.axiosAdmin.post("rotational-teams/unassigned-employees", {}).then((res) => {
                const data = res.data || {};
                unassignedCount.value = data.unassigned_count || 0;
                unassignedEmployees.value = data.unassigned_employees || [];

                if (unassignedCount.value > 0) {
                    // Fetch teams list for the modal options
                    window.axiosAdmin.post("rotational-teams", {}).then((teamRes) => {
                        teamsList.value = teamRes.data || [];
                        if (teamsList.value.length === 0) {
                            unassignedOption.value = "new";
                        } else {
                            unassignedOption.value = "existing";
                        }
                    });
                    unassignedModalVisible.value = true;
                }
            });
        };

        const submitUnassignedAction = () => {
            unassignedModalVisible.value = false;
            if (unassignedOption.value === "existing") {
                window.axiosAdmin.post("rotational-teams/distribute-unassigned", {}).then(() => {
                    notification.success({
                        message: "Success",
                        description: "Unassigned employees distributed to existing teams successfully.",
                        placement: "topRight",
                    });
                    crudVariables.fetch({ page: 1 });
                    checkUnassignedEmployees();
                });
            } else if (unassignedOption.value === "new") {
                prepopulateUnassigned.value = true;
                crudVariables.addItem();
            }
        };

        const handleAssignLater = () => {
            unassignedModalVisible.value = false;
        };

        const openMembers = (record) => {
            router.push({ name: "admin.rotational-weekoff.teams.members", params: { xid: record.xid } });
        };

        const toggleActive = (record) => {
            const originalValue = 1 - record.is_active;
            addEditRequestAdmin({
                url: `rotational-teams/${record.xid}`,
                data: {
                    name: record.name,
                    rotation_order: record.rotation_order,
                    is_active: record.is_active,
                    _method: "PUT",
                },
                success: () => {
                    crudVariables.fetch({ page: 1 });
                },
                error: () => {
                    record.is_active = originalValue;
                },
            });
        };

        onMounted(() => {
            if (permsArray.value.includes("rotational_teams_edit") || permsArray.value.includes("rotational_teams_delete") || permsArray.value.includes("admin")) {
                columns.value = [
                    {
                        title: "Active",
                        dataIndex: "is_active",
                        width: 80,
                    },
                    ...columns.value,
                    {
                        title: "Members",
                        dataIndex: "members_count",
                    },
                    {
                        title: t("common.action"),
                        dataIndex: "action",
                    },
                ];
            }

            crudVariables.tableUrl.value = { url: "rotational-teams?fields=id,xid,name,rotation_order,is_active,members_count" };
            crudVariables.table.filterableColumns = filterableColumns;
            crudVariables.fetch({ page: 1 });
            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "rotational_team";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };

            checkUnassignedEmployees();
        });

        const onCloseAddEdit = () => {
            prepopulateUnassigned.value = false;
            crudVariables.onCloseAddEdit();
        };

        const addEditSuccess = (teamXid) => {
            prepopulateUnassigned.value = false;
            crudVariables.addEditSuccess(teamXid);
            checkUnassignedEmployees();
        };

        return {
            columns, filterableColumns, permsArray, appSetting, teamsList,
            unassignedModalVisible, unassignedCount, unassignedEmployees, unassignedColumns,
            unassignedOption, prepopulateUnassigned, submitUnassignedAction, handleAssignLater,
            openMembers, toggleActive,
            onCloseAddEdit,
            addEditSuccess,
            ...crudVariables,
        };
    },
};
</script>
