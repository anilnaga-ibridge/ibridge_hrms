<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        :title="pageTitle"
        width="600px"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('project.name')"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.name"
                            :placeholder="$t('common.placeholder_default_text', [$t('project.name')])"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        label="Customer"
                        name="customer"
                        :help="rules.customer ? rules.customer.message : null"
                        :validateStatus="rules.customer ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.customer"
                            placeholder="Please Enter Customer Name"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16" align="middle" style="margin-bottom: 16px;">
                <a-col :xs="24" :sm="12" :md="12" :lg="12">
                    <a-form-item name="calculate_progress" style="margin-bottom: 0;">
                        <a-checkbox v-model:checked="formData.calculate_progress">
                            Calculate progress through tasks
                        </a-checkbox>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="12" :lg="12">
                    <a-form-item
                        :label="'Progress (' + formData.progress + '%)'"
                        name="progress"
                        style="margin-bottom: 0;"
                    >
                        <a-slider
                            v-model:value="formData.progress"
                            :min="0"
                            :max="100"
                            :disabled="formData.calculate_progress"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        label="Billing Type"
                        name="billing_type"
                        class="required"
                    >
                        <a-select v-model:value="formData.billing_type" style="width: 100%">
                            <a-select-option value="fixed_rate">Fixed Rate</a-select-option>
                            <a-select-option value="project_hours">Project Hours</a-select-option>
                            <a-select-option value="task_hours">Task Hours</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        label="Total Rate"
                        name="total_rate"
                        :help="rules.total_rate ? rules.total_rate.message : null"
                        :validateStatus="rules.total_rate ? 'error' : null"
                    >
                        <a-input-number
                            v-model:value="formData.total_rate"
                            :min="0"
                            style="width: 100%"
                            :disabled="formData.billing_type !== 'fixed_rate'"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        label="Estimated Hours"
                        name="estimated_hours"
                        :help="rules.estimated_hours ? rules.estimated_hours.message : null"
                        :validateStatus="rules.estimated_hours ? 'error' : null"
                    >
                        <a-input-number
                            v-model:value="formData.estimated_hours"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        :label="$t('project.start_date')"
                        name="start_date"
                        :help="rules.start_date ? rules.start_date.message : null"
                        :validateStatus="rules.start_date ? 'error' : null"
                        class="required"
                    >
                        <a-date-picker
                            v-model:value="formData.start_date"
                            valueFormat="YYYY-MM-DD"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        :label="$t('project.deadline')"
                        name="deadline"
                        :help="rules.deadline ? rules.deadline.message : null"
                        :validateStatus="rules.deadline ? 'error' : null"
                    >
                        <a-date-picker
                            v-model:value="formData.deadline"
                            valueFormat="YYYY-MM-DD"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8" :lg="8">
                    <a-form-item
                        :label="$t('project.status')"
                        name="status"
                    >
                        <a-select v-model:value="formData.status" style="width: 100%">
                            <a-select-option value="not_started">Not Started</a-select-option>
                            <a-select-option value="in_progress">In Progress</a-select-option>
                            <a-select-option value="on_hold">On Hold</a-select-option>
                            <a-select-option value="cancelled">Cancelled</a-select-option>
                            <a-select-option value="finished">Finished</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        label="Tags"
                        name="tags"
                    >
                        <a-select
                            v-model:value="formData.tags"
                            mode="tags"
                            placeholder="Add tag"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                    <a-form-item
                        :label="$t('project.members')"
                        name="members"
                    >
                        <a-select
                            v-model:value="formData.members"
                            mode="multiple"
                            placeholder="Select project members"
                            style="width: 100%"
                            :filterOption="filterOption"
                        >
                            <a-select-option
                                v-for="user in users"
                                :key="user.xid"
                                :value="user.xid"
                            >
                                {{ user.name }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item
                        :label="$t('project.description')"
                        name="description"
                    >
                        <a-textarea
                            v-model:value="formData.description"
                            :rows="4"
                            :placeholder="$t('common.placeholder_default_text', [$t('project.description')])"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item name="send_email">
                        <a-checkbox v-model:checked="formData.send_email">
                            Send project created email
                        </a-checkbox>
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="loading"
                @click="onSubmit"
            >
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ addEditType == 'add' ? $t('common.create') : $t('common.update') }}
            </a-button>
            <a-button key="back" @click="onClose">
                {{ $t("common.cancel") }}
            </a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../common/composable/apiAdmin";

export default defineComponent({
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
        SaveOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const users = ref([]);

        onMounted(() => {
            axiosAdmin.get("users?limit=10000").then((response) => {
                users.value = response.data;
            });
        });

        const onSubmit = () => {
            addEditRequestAdmin({
                url: props.url,
                data: props.formData,
                successMessage: props.successMessage,
                success: (res) => {
                    emit("addEditSuccess", res.xid);
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        const filterOption = (input, option) => {
            return option.children[0].text.toLowerCase().indexOf(input.toLowerCase()) >= 0;
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,
            users,
            filterOption,
        };
    },
});
</script>
