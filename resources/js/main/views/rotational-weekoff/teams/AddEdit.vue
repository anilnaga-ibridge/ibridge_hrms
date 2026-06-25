<template>
    <a-drawer
        :title="pageTitle"
        :width="drawerWidth"
        :open="visible"
        :body-style="{ paddingBottom: '80px' }"
        :footer-style="{ textAlign: 'right' }"
        :maskClosable="false"
        @close="onClose"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('common.name')"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="newFormData.name"
                            :placeholder="$t('common.placeholder_default_text', [$t('common.name')])"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        label="Rotation Order"
                        name="rotation_order"
                    >
                        <a-input-number
                            v-model:value="newFormData.rotation_order"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item label="Assign Employees" name="employee_ids">
                        <a-select
                            v-model:value="newFormData.employee_ids"
                            mode="multiple"
                            style="width: 100%"
                            :placeholder="'Select employees'"
                            :options="employeeOptions"
                            :loading="employeesLoading"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-space>
                <a-button key="submit" type="primary" :loading="loading" @click="onSubmit">
                    <template #icon><SaveOutlined /></template>
                    {{ addEditType == "add" ? $t("common.create") : $t("common.update") }}
                </a-button>
                <a-button key="back" @click="onClose">{{ $t("common.cancel") }}</a-button>
            </a-space>
        </template>
    </a-drawer>
</template>
<script>
import { defineComponent, ref, watch, computed } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";

export default defineComponent({
    props: ["formData", "data", "visible", "url", "addEditType", "pageTitle", "successMessage", "prepopulateUnassigned", "unassignedEmployees"],
    components: { SaveOutlined },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const newFormData = ref({ name: "", rotation_order: 0, employee_ids: [] });
        const allEmployees = ref([]);
        const employeesLoading = ref(false);

        const employeeOptions = computed(() => {
            return allEmployees.value
                .filter((emp) => {
                    // In edit mode, show if unassigned OR if assigned to this team
                    if (props.addEditType === "edit" && props.data) {
                        return !emp.team_xid || emp.team_xid === props.data.xid;
                    }
                    // In add mode, show only unassigned employees
                    return !emp.team_xid;
                })
                .map((emp) => ({
                    label: emp.name + (emp.designation ? " - " + emp.designation : ""),
                    value: emp.xid,
                }));
        });

        const fetchEmployees = () => {
            employeesLoading.value = true;
            window.axiosAdmin.post("rotational-teams/all-employees", {}).then((response) => {
                allEmployees.value = response.data || [];
                employeesLoading.value = false;
            }).catch(() => {
                employeesLoading.value = false;
            });
        };

        const fetchCurrentMembers = () => {
            if (props.addEditType !== "edit" || !props.data) return;
            window.axiosAdmin.post("rotational-teams/members", { team_id: props.data.xid }).then((response) => {
                const members = response.data || [];
                newFormData.value.employee_ids = members.map((m) => m.xid);
            });
        };

        const onSubmit = () => {
            rules.value = {};
            const teamData = {
                name: newFormData.value.name,
                rotation_order: newFormData.value.rotation_order,
            };

            addEditRequestAdmin({
                url: props.url,
                data: teamData,
                successMessage: props.successMessage,
                success: (res) => {
                    const teamXid = res.xid;
                    if (newFormData.value.employee_ids.length > 0) {
                        window.axiosAdmin.post("rotational-teams/assign-members", {
                            team_id: teamXid,
                            user_ids: newFormData.value.employee_ids,
                        }).finally(() => {
                            emit("addEditSuccess", teamXid);
                        });
                    } else {
                        emit("addEditSuccess", teamXid);
                    }
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        watch(
            () => props.visible,
            (newVal) => {
                if (newVal) {
                    fetchEmployees();
                    if (props.addEditType == "edit" && props.data) {
                        newFormData.value = {
                            name: props.data.name,
                            rotation_order: props.data.rotation_order,
                            employee_ids: [],
                        };
                        fetchCurrentMembers();
                    } else {
                        const preselected = (props.prepopulateUnassigned && props.unassignedEmployees)
                            ? props.unassignedEmployees.map((emp) => emp.xid)
                            : [];
                        newFormData.value = {
                            name: "",
                            rotation_order: 0,
                            employee_ids: preselected,
                        };
                    }
                }
            }
        );

        return {
            loading, rules, onClose, onSubmit, newFormData,
            employeeOptions, employeesLoading,
            drawerWidth: window.innerWidth <= 991 ? "90%" : "45%",
        };
    },
});
</script>
