<template>
    <a-drawer
        :open="visible"
        :closable="false"
        :centered="true"
        :title="pageTitle"
        @ok="onSubmit"
        :width="drawerWidth"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="18" :sm="18" :md="18" :lg="18">
                    <a-form-item
                        :label="$t('leave_type.name')"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.name"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('leave_type.name'),
                                ])
                            "
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="6" :sm="6" :md="6" :lg="6">
                    <a-form-item
                        :label="$t('leave_type.is_paid')"
                        name="is_paid"
                        :help="rules.is_paid ? rules.is_paid.message : null"
                        :validateStatus="rules.is_paid ? 'error' : null"
                    >
                        <a-radio-group
                            v-model:value="formData.is_paid"
                            button-style="solid"
                            size="small"
                        >
                            <a-radio-button :value="1">
                                {{ $t("common.yes") }}
                            </a-radio-button>
                            <a-radio-button :value="0">
                                {{ $t("common.no") }}
                            </a-radio-button>
                        </a-radio-group>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col
                    v-if="formData.count_type === 'same_for_all'"
                    :xs="24" :sm="24" :md="10" :lg="10"
                >
                    <a-form-item
                        :label="$t('leave_type.total_leaves')"
                        name="total_leaves"
                        :help="
                            rules.total_leaves
                                ? rules.total_leaves.message
                                : null
                        "
                        :validateStatus="rules.total_leaves ? 'error' : null"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="formData.total_leaves"
                            :placeholder="
                                $t('common.placeholder_default_text', [
                                    $t('leave_type.total_leaves'),
                                ])
                            "
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="14" :lg="14">
                    <a-form-item
                        :label="$t('leave_type.count_type')"
                        name="count_type"
                        :help="
                            rules.count_type ? rules.count_type.message : null
                        "
                        :validateStatus="rules.count_type ? 'error' : null"
                    >
                        <a-input-group compact style="display: flex; gap: 8px;">
                            <a-select
                                v-model:value="formData.count_type"
                                :placeholder="
                                    $t('common.placeholder_default_text', [
                                        $t('leave_type.count_type'),
                                    ])
                                "
                                style="flex: 1"
                            >
                                <a-select-option value="same_for_all">
                                    {{ $t("leave_type.same_for_all") }}
                                </a-select-option>
                                <a-select-option value="employee_specific">
                                    {{ $t("leave_type.employee_specific") }}
                                </a-select-option>
                            </a-select>
                            <a-button
                                v-if="formData.count_type === 'employee_specific'"
                                type="primary"
                                @click="modalOpened"
                                style="white-space: nowrap"
                            >
                                <template #icon><EditOutlined /></template>
                                {{ $t("leave_type.edit_count") }}
                            </a-button>
                        </a-input-group>
                    </a-form-item>
                </a-col>
            </a-row>

        </a-form>
        <template #footer>
            <a-space>
                <a-button
                    key="submit"
                    type="primary"
                    :loading="loading"
                    @click="onSubmit"
                >
                    <template #icon>
                        <SaveOutlined />
                    </template>
                    {{
                        addEditType == "add"
                            ? $t("common.create")
                            : $t("common.update")
                    }}
                </a-button>
                <a-button key="back" @click="onClose">
                    {{ $t("common.cancel") }}
                </a-button>
            </a-space>
        </template>
    </a-drawer>
    <EmployeeSpecificLeaveCount
        :visible="modalOpen"
        :title="$t('leave_type.employee_specific_leave_count')"
        @close="modalClosed"
        :data="modalData"
        :addEditType="addEditType"
        @save="onEmployeeSpecificLeaveSave"
    />
</template>
<script>
import { defineComponent, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
    EditOutlined,
} from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import common from "../../../../common/composable/common";
import EmployeeSpecificLeaveCount from "./EmployeeSpecificLeaveCount.vue";

export default defineComponent({
    emits: ["addEditSuccess", "closed"],
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "successMessage",
    ],
    components: {
        PlusOutlined,
        LoadingOutlined,
        SaveOutlined,
        EditOutlined,
        EmployeeSpecificLeaveCount,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const { t } = useI18n();

        const { appSetting, disabledDate, permsArray } = common();
        const modalOpen = ref(false);
        const modalData = ref({});
        const tempEmployeeSpecificLeaves = ref({ all_form_fields: [], removed_fields: [] });
        const createdLeaveTypeId = ref(null);

        const onEmployeeSpecificLeaveSave = (data) => {
            tempEmployeeSpecificLeaves.value = data;
        };

        const modalOpened = () => {
            modalOpen.value = true;
            if (props.addEditType === "edit") {
                modalData.value = props.data;
            } else {
                modalData.value = {
                    xid: null,
                    employee_specific_leave_count: tempEmployeeSpecificLeaves.value.all_form_fields.map((field) => ({
                        x_user_id: field.user_id,
                        total_leaves: field.total_leaves,
                        x_leave_type_id: null,
                        xid: field.xid,
                    })),
                };
            }
        };
        const modalClosed = () => {
            modalOpen.value = false;
            modalData.value = {};
        };

        const onSubmit = () => {
            if (createdLeaveTypeId.value) {
                loading.value = true;
                const finalFields = tempEmployeeSpecificLeaves.value.all_form_fields.map(field => ({
                    ...field,
                    leave_type_id: createdLeaveTypeId.value
                }));
                axiosAdmin.post("employee-specific-leave", {
                    all_form_fields: finalFields,
                    removed_fields: tempEmployeeSpecificLeaves.value.removed_fields
                }).then(() => {
                    emit("addEditSuccess", createdLeaveTypeId.value);
                }).catch((err) => {
                    const errorMsg = err?.data?.message || err?.message || t("leave_type.failed_to_save_counts");
                    message.error(errorMsg);
                }).finally(() => {
                    loading.value = false;
                });
                return;
            }

            addEditRequestAdmin({
                url: props.url,
                data: props.formData,
                successMessage: props.successMessage,
                success: (res) => {
                    if (props.addEditType === "add" && tempEmployeeSpecificLeaves.value.all_form_fields.length > 0) {
                        createdLeaveTypeId.value = res.xid;
                        const finalFields = tempEmployeeSpecificLeaves.value.all_form_fields.map(field => ({
                            ...field,
                            leave_type_id: res.xid
                        }));
                        return axiosAdmin.post("employee-specific-leave", {
                            all_form_fields: finalFields,
                            removed_fields: tempEmployeeSpecificLeaves.value.removed_fields
                        }).then(() => {
                            emit("addEditSuccess", res.xid);
                        }).catch((err) => {
                            const errorMsg = err?.data?.message || err?.message || t("leave_type.failed_to_save_counts");
                            message.error(errorMsg);
                            throw err;
                        });
                    } else {
                        emit("addEditSuccess", res.xid);
                    }
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            tempEmployeeSpecificLeaves.value = { all_form_fields: [], removed_fields: [] };
            createdLeaveTypeId.value = null;
            emit("closed");
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,
            appSetting,
            disabledDate,
            permsArray,
            modalOpen,
            modalData,
            modalOpened,
            modalClosed,
            onEmployeeSpecificLeaveSave,

            drawerWidth: window.innerWidth <= 991 ? "90%" : "45%",
        };
    },
});
</script>
