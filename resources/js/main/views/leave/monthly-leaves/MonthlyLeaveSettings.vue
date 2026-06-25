<template>
    <div>
        <a-spin :spinning="loading">
            <a-row :gutter="[24, 24]">
                <!-- Global Settings -->
                <a-col :xs="24" :sm="24" :md="24" :lg="12">
                    <a-card size="small">
                        <template #title>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <SettingOutlined />
                                <span>Global Settings</span>
                            </div>
                        </template>
                        <template #extra>
                            <a-tag color="blue">Applies to all employees</a-tag>
                        </template>

                        <a-alert
                            type="info"
                            show-icon
                            style="margin-bottom: 16px"
                            message="These settings apply to all employees unless overridden by employee-specific settings."
                        />

                        <a-form layout="vertical">
                            <a-form-item label="Expiry Cycle (Months)" class="required" style="margin-bottom: 16px">
                                <a-input-number
                                    v-model:value="globalExpiryCycle"
                                    :min="1"
                                    style="width: 100%"
                                    placeholder="e.g. 3"
                                />
                                <div style="font-size: 12px; color: #888; margin-top: 4px;">
                                    A credited leave will expire after this many months if unused.
                                    <br><strong>Example:</strong> If set to 3, a leave credited in January expires at end of March.
                                </div>
                            </a-form-item>

                            <a-form-item label="Max Leaves Per Month" style="margin-bottom: 16px">
                                <a-input-number
                                    v-model:value="globalMaxLeavesPerMonth"
                                    :min="0"
                                    style="width: 100%"
                                    placeholder="0 = unlimited"
                                />
                                <div style="font-size: 12px; color: #888; margin-top: 4px;">
                                    Maximum number of monthly leaves an employee can <strong>take</strong> per month.
                                    <br>Set <strong>0</strong> for unlimited.
                                </div>
                            </a-form-item>

                            <a-divider style="margin: 16px 0" />

                            <a-row :gutter="16">
                                <a-col :span="12">
                                    <a-form-item label="Leave Type Name">
                                        <a-input :value="leaveTypeName" disabled />
                                    </a-form-item>
                                </a-col>
                                <a-col :span="12">
                                    <a-form-item label="Paid">
                                        <a-switch :checked="isPaid" disabled />
                                        <span style="margin-left: 8px; color: #888; font-size: 12px;">
                                            {{ isPaid ? 'Yes — leaves are paid' : 'No — leaves are unpaid' }}
                                        </span>
                                    </a-form-item>
                                </a-col>
                            </a-row>
                        </a-form>
                    </a-card>
                </a-col>

                <!-- Per-Employee Settings -->
                <a-col :xs="24" :sm="24" :md="24" :lg="12">
                    <a-card size="small">
                        <template #title>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <TeamOutlined />
                                <span>Employee-Specific Settings</span>
                            </div>
                        </template>
                        <template #extra>
                            <a-button type="primary" size="small" @click="addEmployeeSetting">
                                <template #icon><PlusOutlined /></template>
                                Add Employee
                            </a-button>
                        </template>

                        <a-alert
                            type="warning"
                            show-icon
                            style="margin-bottom: 16px"
                            v-if="employeeSettings.length === 0"
                            message="No employee-specific settings yet."
                            description="All employees currently use the global settings above. Click 'Add Employee' to override settings for specific employees."
                        />

                        <div v-else>
                            <div style="display: flex; gap: 8px; margin-bottom: 8px; padding: 4px 8px; font-weight: 600; font-size: 12px; color: #888; background: #fafafa; border-radius: 4px;">
                                <div style="flex: 1">Employee</div>
                                <div style="width: 120px">Expiry Cycle (Months)</div>
                                <div style="width: 120px">Max Leaves/Month</div>
                                <div style="width: 32px"></div>
                            </div>
                            <div
                                v-for="(setting, index) in employeeSettings"
                                :key="index"
                                style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; padding: 12px; border: 1px solid #e8e8e8; border-radius: 6px; background: #fafafa;"
                            >
                                <a-select
                                    v-model:value="setting.user_id"
                                    show-search
                                    style="flex: 1"
                                    placeholder="Select employee"
                                    :filter-option="(input, option) => option.children?.[0]?.children?.toLowerCase().includes(input.toLowerCase())"
                                >
                                    <a-select-option
                                        v-for="user in allStaffMembers"
                                        :key="user.xid"
                                        :value="user.xid"
                                        :title="user.name"
                                    >
                                        {{ user.name }}
                                    </a-select-option>
                                </a-select>
                                <a-input-number
                                    v-model:value="setting.monthly_leave_expiry_cycle"
                                    :min="1"
                                    style="width: 120px"
                                    placeholder="Cycle"
                                />
                                <a-input-number
                                    v-model:value="setting.max_leaves_per_month"
                                    :min="0"
                                    style="width: 120px"
                                    placeholder="0 = unlim."
                                />
                                <a-tooltip title="Remove this employee">
                                    <a-button
                                        type="text"
                                        danger
                                        @click="removeEmployeeSetting(index)"
                                    >
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                            </div>
                        </div>
                    </a-card>
                </a-col>
            </a-row>

            <a-divider />

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a-button @click="showCreditModal = true" type="primary" ghost>
                    <template #icon><PlusOutlined /></template>
                    Manually Credit Leave
                </a-button>
                <a-button type="primary" @click="saveSettings" :loading="saving" size="large">
                    <template #icon><SaveOutlined /></template>
                    {{ $t('common.save') }} Settings
                </a-button>
            </div>
        </a-spin>

        <!-- Credit Leave Modal -->
        <a-modal
            v-model:open="showCreditModal"
            title="Credit Monthly Leave"
            @ok="creditLeave"
            :confirm-loading="creditLoading"
            ok-text="Credit Leave"
            cancel-text="Cancel"
        >
            <a-alert
                type="info"
                show-icon
                style="margin-bottom: 16px"
                message="Manually credit a monthly leave to an employee. This is useful for new joiners or edge cases."
            />
            <a-form layout="vertical">
                <a-form-item label="Select Employee" class="required">
                    <a-select
                        v-model:value="creditForm.user_id"
                        show-search
                        placeholder="Search employee name..."
                        style="width: 100%"
                        :filter-option="(input, option) => option.children?.[0]?.children?.toLowerCase().includes(input.toLowerCase())"
                    >
                        <a-select-option
                            v-for="user in allStaffMembers"
                            :key="user.xid"
                            :value="user.xid"
                            :title="user.name"
                        >
                            {{ user.name }}
                        </a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Month">
                    <a-month-picker
                        v-model:value="creditForm.month"
                        style="width: 100%"
                        placeholder="Select month (defaults to current month)"
                        :disabled-date="(current) => current && current > moment().endOf('month')"
                    />
                    <div style="font-size: 12px; color: #888; margin-top: 4px;">
                        If not selected, the leave will be credited for the current month.
                    </div>
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import {
    PlusOutlined,
    DeleteOutlined,
    SaveOutlined,
    SettingOutlined,
    TeamOutlined,
} from "@ant-design/icons-vue";
import { notification } from "ant-design-vue";
import { useI18n } from "vue-i18n";
import moment from "moment";
import common from "../../../../common/composable/common";

export default defineComponent({
    components: {
        PlusOutlined,
        DeleteOutlined,
        SaveOutlined,
        SettingOutlined,
        TeamOutlined,
    },
    setup() {
        const { t } = useI18n();
        const { permsArray } = common();

        const loading = ref(false);
        const saving = ref(false);
        const globalExpiryCycle = ref(3);
        const globalMaxLeavesPerMonth = ref(0);
        const leaveTypeName = ref("Monthly Leave");
        const isPaid = ref(true);
        const employeeSettings = ref([]);
        const allStaffMembers = ref([]);
        const showCreditModal = ref(false);
        const creditLoading = ref(false);
        const creditForm = ref({
            user_id: undefined,
            month: null,
        });

        const fetchSettings = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get("employee-monthly-leaves/settings");
                globalExpiryCycle.value = res.data.leave_type.monthly_leave_expiry_cycle;
                globalMaxLeavesPerMonth.value = res.data.leave_type.max_leaves_per_month || 0;
                leaveTypeName.value = res.data.leave_type.name;
                isPaid.value = !!res.data.leave_type.is_paid;
                employeeSettings.value = res.data.employee_settings || [];
            } catch (error) {
                notification.error({
                    message: "Failed to load settings",
                });
            } finally {
                loading.value = false;
            }
        };

        const fetchStaffMembers = async () => {
            try {
                const res = await axiosAdmin.get(
                    "users?limit=10000&fields=id,xid,name"
                );
                allStaffMembers.value = res.data;
            } catch (_) {
                // silent
            }
        };

        const addEmployeeSetting = () => {
            employeeSettings.value.push({
                user_id: undefined,
                monthly_leave_expiry_cycle: globalExpiryCycle.value,
                max_leaves_per_month: globalMaxLeavesPerMonth.value,
            });
        };

        const removeEmployeeSetting = (index) => {
            employeeSettings.value.splice(index, 1);
        };

        const saveSettings = async () => {
            if (!globalExpiryCycle.value || globalExpiryCycle.value < 1) {
                notification.error({
                    message: "Expiry cycle must be at least 1 month",
                });
                return;
            }

            saving.value = true;
            try {
                await axiosAdmin.put("employee-monthly-leaves/settings", {
                    monthly_leave_expiry_cycle: globalExpiryCycle.value,
                    max_leaves_per_month: globalMaxLeavesPerMonth.value,
                    employee_settings: employeeSettings.value.filter(
                        (s) => s.user_id && (s.monthly_leave_expiry_cycle || s.max_leaves_per_month)
                    ),
                });
                notification.success({
                    message: t("common.success"),
                });
                fetchSettings();
            } catch (error) {
                notification.error({
                    message: error.response?.data?.message || "Failed to save settings",
                });
            } finally {
                saving.value = false;
            }
        };

        const creditLeave = async () => {
            if (!creditForm.value.user_id) {
                notification.error({ message: "Please select an employee" });
                return;
            }

            creditLoading.value = true;
            try {
                const payload = {
                    user_id: creditForm.value.user_id,
                };
                if (creditForm.value.month) {
                    payload.credited_date = creditForm.value.month.format("YYYY-MM-DD");
                }
                await axiosAdmin.post("employee-monthly-leaves/credit", payload);
                notification.success({ message: "Monthly leave credited successfully" });
                showCreditModal.value = false;
                creditForm.value = { user_id: undefined, month: null };
            } catch (error) {
                notification.error({
                    message: error.response?.data?.message || "Failed to credit leave",
                });
            } finally {
                creditLoading.value = false;
            }
        };

        onMounted(() => {
            fetchSettings();
            fetchStaffMembers();
        });

        return {
            loading,
            saving,
            globalExpiryCycle,
            globalMaxLeavesPerMonth,
            leaveTypeName,
            isPaid,
            employeeSettings,
            allStaffMembers,
            addEmployeeSetting,
            removeEmployeeSetting,
            saveSettings,
            showCreditModal,
            creditLoading,
            creditForm,
            creditLeave,
            moment,
        };
    },
});
</script>
