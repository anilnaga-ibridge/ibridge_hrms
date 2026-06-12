<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header :title="$t(`menu.projects`)" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t(`menu.projects`) }}
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="stats-cards-container" style="margin: 0 16px 24px 16px;">
        <a-row :gutter="16">
            <a-col :xs="24" :sm="12" :md="6">
                <a-card :bordered="false" class="stats-card total-projects">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.total }}</div>
                        <div class="card-label">Total Projects</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="6">
                <a-card :bordered="false" class="stats-card in-progress-projects">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.inProgress }}</div>
                        <div class="card-label">In Progress</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="6">
                <a-card :bordered="false" class="stats-card on-hold-projects">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.onHold }}</div>
                        <div class="card-label">On Hold</div>
                    </div>
                </a-card>
            </a-col>
            <a-col :xs="24" :sm="12" :md="6">
                <a-card :bordered="false" class="stats-card finished-projects">
                    <div class="card-inner">
                        <div class="card-value">{{ stats.finished }}</div>
                        <div class="card-label">Finished</div>
                    </div>
                </a-card>
            </a-col>
        </a-row>
    </div>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="10" :xl="10">
                <a-space>
                    <a-button type="primary" @click="addItem">
                        <PlusOutlined />
                        {{ $t("project.add") }}
                    </a-button>
                    <a-button
                        v-if="table.selectedRowKeys.length > 0"
                        type="primary"
                        @click="showSelectedDeleteConfirm"
                        danger
                    >
                        <template #icon><DeleteOutlined /></template>
                        {{ $t("common.delete") }}
                    </a-button>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="12" :xl="8">
                        <a-input-group compact>
                            <a-select
                                style="width: 25%"
                                v-model:value="table.searchColumn"
                                :placeholder="$t('common.select_default_text', [''])"
                            >
                                <a-select-option
                                    v-for="filterableColumn in filterableColumns"
                                    :key="filterableColumn.key"
                                >
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
                                :placeholder="$t('common.placeholder_search_text', [$t('project.name')])"
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
        />

        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: table.selectedRowKeys,
                            onChange: onRowSelectChange,
                            getCheckboxProps: (record) => ({
                                disabled: false,
                                name: record.xid,
                            }),
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
                            <template v-if="column.dataIndex === 'status'">
                                <a-tag :color="getStatusColor(record.status)">
                                    {{ getStatusLabel(record.status) }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'start_date'">
                                {{ record.start_date ? record.start_date.substring(0,10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'deadline'">
                                {{ record.deadline ? record.deadline.substring(0,10) : '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'members'">
                                <a-avatar-group :max-count="3">
                                    <a-tooltip
                                        v-for="member in record.member_details"
                                        :key="member.xid"
                                        :title="member.name"
                                    >
                                        <a-avatar :src="member.profile_image_url" />
                                    </a-tooltip>
                                </a-avatar-group>
                            </template>
                            <template v-if="column.dataIndex === 'progress'">
                                <a-progress :percent="record.progress" size="small" />
                            </template>
                            <template v-if="column.dataIndex === 'billing_type'">
                                {{ getBillingTypeLabel(record.billing_type) }}
                            </template>
                            <template v-if="column.dataIndex === 'action'">
                                <a-button
                                    type="primary"
                                    @click="editItem(record)"
                                    style="margin-left: 4px"
                                >
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button
                                    type="primary"
                                    @click="showDeleteConfirm(record.xid)"
                                    style="margin-left: 4px"
                                    danger
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
import { onMounted, reactive, watch } from "vue";
import { PlusOutlined, EditOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import fields from "./fields";
import crud from "../../../common/composable/crud";
import common from "../../../common/composable/common";
import AddEdit from "./AddEdit.vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        AddEdit,
        AdminPageHeader,
    },
    setup() {
        const { t } = useI18n();
        const { addEditUrl, initData, columns, filterableColumns } = fields();
        const crudVariables = crud();
        const { permsArray } = common();

        const stats = reactive({
            total: 0,
            inProgress: 0,
            onHold: 0,
            finished: 0,
        });

        const updateStats = () => {
            if (crudVariables.table.data) {
                const data = crudVariables.table.data;
                stats.total = data.length;
                stats.inProgress = data.filter(p => p.status === 'in_progress').length;
                stats.onHold = data.filter(p => p.status === 'on_hold').length;
                stats.finished = data.filter(p => p.status === 'finished').length;
            }
        };

        watch(() => crudVariables.table.data, () => {
            updateStats();
        }, { deep: true });

        onMounted(() => {
            columns.value = [
                ...columns.value,
                {
                    title: t("common.action"),
                    dataIndex: "action",
                },
            ];

            crudVariables.tableUrl.value = {
                url: "projects?fields=id,xid,name,status,start_date,deadline,description,members,member_details,customer,calculate_progress,progress,billing_type,total_rate,estimated_hours,tags,send_email&limit=1000",
            };
            crudVariables.table.filterableColumns = filterableColumns;

            crudVariables.fetch({
                page: 1,
            });

            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "project";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
        });

        const getStatusColor = (status) => {
            switch (status) {
                case "in_progress":
                    return "purple";
                case "on_hold":
                    return "orange";
                case "finished":
                    return "green";
                case "cancelled":
                    return "red";
                default:
                    return "blue";
            }
        };

        const getStatusLabel = (status) => {
            switch (status) {
                case "in_progress":
                    return "In Progress";
                case "on_hold":
                    return "On Hold";
                case "finished":
                    return "Finished";
                case "cancelled":
                    return "Cancelled";
                default:
                    return "Not Started";
            }
        };

        const getBillingTypeLabel = (type) => {
            switch (type) {
                case "fixed_rate":
                    return "Fixed Rate";
                case "project_hours":
                    return "Project Hours";
                case "task_hours":
                    return "Task Hours";
                default:
                    return type;
            }
        };

        return {
            columns,
            ...crudVariables,
            filterableColumns,
            permsArray,
            stats,
            getStatusColor,
            getStatusLabel,
            getBillingTypeLabel,
        };
    },
};
</script>

<style scoped>
.stats-cards-container {
    background: transparent;
}
.stats-card {
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    background: #ffffff;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}
.card-inner {
    padding: 10px;
}
.card-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    line-height: 1.2;
}
.card-label {
    font-size: 14px;
    color: #8c8c8c;
    margin-top: 4px;
}

/* Custom left accent borders or colors if desired */
.total-projects {
    border-left: 5px solid #1890ff;
}
.in-progress-projects {
    border-left: 5px solid #722ed1;
}
.on-hold-projects {
    border-left: 5px solid #fa8c16;
}
.finished-projects {
    border-left: 5px solid #52c41a;
}
</style>
