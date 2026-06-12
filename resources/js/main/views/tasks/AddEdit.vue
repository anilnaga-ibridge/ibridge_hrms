<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        :title="pageTitle"
        width="800px"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16" align="middle" style="margin-bottom: 16px;">
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item name="is_public" style="margin-bottom: 0;">
                        <a-checkbox v-model:checked="formData.is_public">
                            {{ $t("task.is_public") || "Public" }}
                        </a-checkbox>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12" :md="8">
                    <a-form-item name="is_billable" style="margin-bottom: 0;">
                        <a-checkbox v-model:checked="formData.is_billable">
                            {{ $t("task.is_billable") || "Billable" }}
                        </a-checkbox>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item :label="$t('task.attach_files') || 'Attach Files'" name="task_file" style="margin-bottom: 0;">
                        <UploadFile
                            :acceptFormat="'image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar'"
                            :formData="formData"
                            folder="taskFiles"
                            uploadField="task_file"
                            @onFileUploaded="
                                (file) => {
                                    formData.task_file = file.file;
                                    formData.task_file_url = file.file_url;
                                }
                            "
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="16">
                    <a-form-item
                        :label="$t('task.subject') || $t('task.name') || 'Subject'"
                        name="name"
                        :help="rules.name ? rules.name.message : null"
                        :validateStatus="rules.name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.name"
                            :placeholder="$t('common.placeholder_default_text', [$t('task.subject') || 'Subject'])"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item
                        :label="$t('task.hourly_rate') || 'Hourly Rate'"
                        name="hourly_rate"
                        :help="rules.hourly_rate ? rules.hourly_rate.message : null"
                        :validateStatus="rules.hourly_rate ? 'error' : null"
                    >
                        <a-input-number
                            v-model:value="formData.hourly_rate"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item
                        :label="$t('task.start_date')"
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
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item
                        :label="$t('task.due_date')"
                        name="due_date"
                        :help="rules.due_date ? rules.due_date.message : null"
                        :validateStatus="rules.due_date ? 'error' : null"
                    >
                        <a-date-picker
                            v-model:value="formData.due_date"
                            valueFormat="YYYY-MM-DD"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item
                        :label="$t('task.priority')"
                        name="priority"
                        :help="rules.priority ? rules.priority.message : null"
                        :validateStatus="rules.priority ? 'error' : null"
                    >
                        <a-select v-model:value="formData.priority" style="width: 100%">
                            <a-select-option value="low">Low</a-select-option>
                            <a-select-option value="medium">Medium</a-select-option>
                            <a-select-option value="high">High</a-select-option>
                            <a-select-option value="urgent">Urgent</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="8">
                    <a-form-item
                        :label="$t('task.repeat_every') || 'Repeat every'"
                        name="repeat_every"
                    >
                        <a-select v-model:value="formData.repeat_every" style="width: 100%">
                            <a-select-option value="no_repeat">No Repeat</a-select-option>
                            <a-select-option value="daily">Daily</a-select-option>
                            <a-select-option value="weekly">Weekly</a-select-option>
                            <a-select-option value="bi_weekly">2 Weeks</a-select-option>
                            <a-select-option value="monthly">Monthly</a-select-option>
                            <a-select-option value="quarterly">3 Months</a-select-option>
                            <a-select-option value="semi_annually">6 Months</a-select-option>
                            <a-select-option value="annually">1 Year</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="24" :md="16">
                    <a-form-item
                        :label="$t('task.project') || 'Related To'"
                        name="project_id"
                        :help="rules.project_id ? rules.project_id.message : null"
                        :validateStatus="rules.project_id ? 'error' : null"
                    >
                        <a-select
                            v-model:value="formData.project_id"
                            placeholder="Select project (optional)"
                            style="width: 100%"
                            :filterOption="filterOption"
                            show-search
                            allowClear
                        >
                            <a-select-option
                                v-for="project in projects"
                                :key="project.xid"
                                :value="project.xid"
                            >
                                {{ project.name }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="12">
                    <a-form-item
                        :label="$t('task.assignees')"
                        name="assignees"
                        :help="rules.assignees ? rules.assignees.message : null"
                        :validateStatus="rules.assignees ? 'error' : null"
                    >
                        <a-select
                            v-model:value="formData.assignees"
                            mode="multiple"
                            placeholder="Select assignees"
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
                <a-col :xs="24" :sm="24" :md="12">
                    <a-form-item
                        :label="$t('task.followers') || 'Followers'"
                        name="followers"
                        :help="rules.followers ? rules.followers.message : null"
                        :validateStatus="rules.followers ? 'error' : null"
                    >
                        <a-select
                            v-model:value="formData.followers"
                            mode="multiple"
                            placeholder="Select followers"
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
                        :label="$t('task.tags') || 'Tags'"
                        name="tags"
                    >
                        <a-select
                            v-model:value="formData.tags"
                            mode="tags"
                            placeholder="Add tags"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-row :gutter="16">
                <a-col :span="24">
                    <a-form-item
                        :label="$t('task.description') || 'Task Description'"
                        name="description"
                        :help="rules.description ? rules.description.message : null"
                        :validateStatus="rules.description ? 'error' : null"
                    >
                        <a-textarea
                            v-model:value="formData.description"
                            :rows="4"
                            :placeholder="$t('common.placeholder_default_text', [$t('task.description') || 'Task Description'])"
                        />
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
import UploadFile from "../../../common/core/ui/file/UploadFile.vue";

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
        UploadFile,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const users = ref([]);
        const projects = ref([]);

        onMounted(() => {
            // Try admin endpoint first; fall back to self endpoint for non-admin users
            const usersUrl = props.url && props.url.startsWith('self/') ? 'self/users?limit=10000' : 'users?limit=10000';
            const projectsUrl = props.url && props.url.startsWith('self/') ? 'self/projects?limit=1000' : 'projects?limit=1000';

            axiosAdmin.get(usersUrl).then((response) => {
                users.value = response.data;
            }).catch(() => {
                axiosAdmin.get('self/users?limit=10000').then((r) => { users.value = r.data; });
            });
            axiosAdmin.get(projectsUrl).then((response) => {
                projects.value = response.data;
            }).catch(() => {
                axiosAdmin.get('self/projects?limit=1000').then((r) => { projects.value = r.data; });
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
            projects,
            filterOption,
        };
    },
});
</script>
