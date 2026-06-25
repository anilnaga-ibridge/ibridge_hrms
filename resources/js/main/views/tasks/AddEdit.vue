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
            <div v-if="isSpeechSupported" class="voice-assistant-section">
                <div class="voice-card">
                    <div class="voice-card-header">
                        <span class="voice-title">
                            <AudioOutlined class="icon-voice" /> Voice Task Quick-Fill
                        </span>
                        <span class="voice-badge">Smart Fill</span>
                    </div>
                    <div class="voice-card-body">
                        <p class="voice-instructions">
                            Click below and describe your task. We'll automatically extract the Subject, Project, Priority, Assignee, and Due Date.
                            <br />
                            <small style="color: #6b7280; font-style: italic;">
                                Example: "Create task Design landing page for project Website priority high due next Monday assign to Admin"
                            </small>
                        </p>
                        <div class="voice-actions">
                            <a-button 
                                type="primary" 
                                shape="round" 
                                :class="['btn-voice', { 'recording': isRecording }]"
                                @click="toggleRecording"
                            >
                                <template #icon>
                                    <AudioFilled v-if="isRecording" class="pulse-icon" />
                                    <AudioOutlined v-else />
                                </template>
                                {{ isRecording ? 'Listening... Click to Finish' : 'Record Task Details' }}
                            </a-button>
                        </div>
                        
                        <div v-if="transcription" class="voice-transcript">
                            <span class="transcript-label"><CheckCircleOutlined style="color: #52c41a; margin-right: 4px;" /> Transcribed Text:</span>
                            <blockquote class="transcript-quote">"{{ transcription }}"</blockquote>
                        </div>
                    </div>
                </div>
            </div>

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
import { SaveOutlined, AudioOutlined, AudioFilled, CheckCircleOutlined } from "@ant-design/icons-vue";
import { message } from "ant-design-vue";
import apiAdmin from "../../../common/composable/apiAdmin";
import common from "../../../common/composable/common";
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
    emits: ["addEditSuccess", "closed", "voiceFill"],
    components: {
        SaveOutlined,
        AudioOutlined,
        AudioFilled,
        CheckCircleOutlined,
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
                const data = response.data;
                if (data && data.length > 0) {
                    users.value = data;
                } else {
                    return Promise.reject({ response: { status: 400 } });
                }
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

        const { dayjs } = common();
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const isSpeechSupported = ref(!!SpeechRecognition);
        const isRecording = ref(false);
        const transcription = ref("");
        let recognition = null;

        const initSpeech = () => {
            if (SpeechRecognition) {
                recognition = new SpeechRecognition();
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'en-US';

                recognition.onstart = () => {
                    isRecording.value = true;
                };

                recognition.onresult = (event) => {
                    const resultText = event.results[0][0].transcript;
                    transcription.value = resultText;
                    fillFormFromVoice(resultText);
                };

                recognition.onerror = (event) => {
                    console.error("Speech recognition error", event.error);
                    isRecording.value = false;
                    message.error("Speech recognition error: " + event.error);
                };

                recognition.onend = () => {
                    isRecording.value = false;
                };
            }
        };

        const toggleRecording = () => {
            if (!recognition) initSpeech();
            if (isRecording.value) {
                recognition.stop();
            } else {
                transcription.value = "";
                try {
                    recognition.start();
                } catch (e) {
                }
            }
        };

        const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        const fillFormFromVoice = (text) => {
            const lowercaseText = text.toLowerCase();
            const updates = {};
            let matchedFields = [];

            // ── 1. Priority ─────────────────────────────────────────────────────
            const priorityMap = [
                { key: 'urgent', val: 'urgent' },
                { key: 'high',   val: 'high' },
                { key: 'medium', val: 'medium' },
                { key: 'normal', val: 'medium' },
                { key: 'low',    val: 'low' },
            ];
            for (const { key, val } of priorityMap) {
                if (new RegExp(`\\b${escapeRegex(key)}\\b`, 'i').test(lowercaseText)) {
                    updates.priority = val;
                    matchedFields.push(`Priority → ${val}`);
                    break;
                }
            }

            // ── 2. Project (best-matching by word overlap) ───────────────────────
            if (projects.value && projects.value.length > 0) {
                let bestProject = null;
                let bestScore = 0;
                for (const proj of projects.value) {
                    const projWords = proj.name.toLowerCase().split(/\s+/).filter(w => w.length > 2);
                    const matchCount = projWords.filter(w => new RegExp(`\\b${escapeRegex(w)}\\b`, 'i').test(lowercaseText)).length;
                    const score = projWords.length > 0 ? matchCount / projWords.length : 0;
                    if (matchCount > 0 && score > bestScore) {
                        bestScore = score;
                        bestProject = proj;
                    }
                }
                if (bestProject) {
                    updates.project_id = bestProject.xid;
                    matchedFields.push(`Project → ${bestProject.name}`);
                }
            }

            // ── 3. Assignees (flexible patterns) ─────────────────────────────────
            if (users.value && users.value.length > 0) {
                const matched = [];
                const matchedNames = [];
                for (const u of users.value) {
                    const parts = u.name.toLowerCase().split(/\s+/);
                    const firstName = escapeRegex(parts[0]);
                    const lastName = escapeRegex(parts[parts.length - 1]);
                    const patterns = [
                        new RegExp(`assign(?:ed)?\\s+(?:to\\s+)?${firstName}`, 'i'),
                        new RegExp(`\\bfor\\s+${firstName}\\b`, 'i'),
                        new RegExp(`\\bto\\s+${firstName}\\b`, 'i'),
                        new RegExp(`\\b${firstName}\\s+${lastName}\\b`, 'i'),
                    ];
                    if (patterns.some(p => p.test(lowercaseText))) {
                        matched.push(u.xid);
                        matchedNames.push(u.name);
                    }
                }
                if (matched.length > 0) {
                    updates.assignees = matched;
                    matchedFields.push(`Assignees → ${matchedNames.join(', ')}`);
                }
            }

            // ── 4. Due Date ───────────────────────────────────────────────────────
            let parsedDue = null;
            if (/\btoday\b/i.test(lowercaseText)) {
                parsedDue = dayjs();
            } else if (/\btomorrow\b/i.test(lowercaseText)) {
                parsedDue = dayjs().add(1, 'day');
            } else if (/\bnext\s+week\b/i.test(lowercaseText)) {
                parsedDue = dayjs().add(7, 'day');
            } else if (/\bthis\s+week\b/i.test(lowercaseText)) {
                parsedDue = dayjs().endOf('week');
            } else if (/\bthis\s+month\b/i.test(lowercaseText)) {
                parsedDue = dayjs().endOf('month');
            } else {
                const weekdays = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
                for (let i = 0; i < 7; i++) {
                    const dName = weekdays[i];
                    if (new RegExp(`\\b(?:next|due|on|by)\\s+${dName}\\b`, 'i').test(lowercaseText)) {
                        let target = dayjs().day(i);
                        if (target.isBefore(dayjs(), 'day') || target.isSame(dayjs(), 'day')) target = target.add(7, 'day');
                        parsedDue = target;
                        break;
                    }
                    if (new RegExp(`\\b${dName}\\b`, 'i').test(lowercaseText)) {
                        let target = dayjs().day(i);
                        if (target.isBefore(dayjs(), 'day')) target = target.add(7, 'day');
                        parsedDue = target;
                        break;
                    }
                }
            }
            if (parsedDue) {
                updates.due_date = parsedDue.format('YYYY-MM-DD');
                matchedFields.push(`Due Date → ${parsedDue.format('DD MMM YYYY')}`);
            }

            // ── 5. Subject / Task Name ────────────────────────────────────────────
            let subjectText = text;
            const commandPrefixes = [
                /^\s*create\s+a?\s*task\s+(?:to\s+)?/i,
                /^\s*add\s+a?\s*task\s+(?:to\s+)?/i,
                /^\s*new\s+task\s+(?:to\s+)?/i,
                /^\s*task\s*:\s*/i,
                /^\s*remind\s+me\s+to\s+/i,
                /^\s*i\s+need\s+to\s+/i,
            ];
            for (const r of commandPrefixes) {
                if (r.test(subjectText)) {
                    subjectText = subjectText.replace(r, '').trim();
                    break;
                }
            }
            const endMarkers = [
                /\s+priority\s+/i,
                /\s+due\s+/i,
                /\s+assign(?:ed)?\s+/i,
                /\s+for\s+project\s+/i,
                /\s+project\s+/i,
                /\s+by\s+(?:next|this|monday|tuesday|wednesday|thursday|friday|saturday|sunday)/i,
                /\s+deadline\s+/i,
            ];
            let cutIndex = subjectText.length;
            for (const marker of endMarkers) {
                const m = subjectText.match(marker);
                if (m && m.index < cutIndex) cutIndex = m.index;
            }
            subjectText = subjectText.substring(0, cutIndex).trim();
            if (subjectText) {
                updates.name = subjectText.charAt(0).toUpperCase() + subjectText.slice(1);
                matchedFields.push(`Subject → "${updates.name}"`);
            }

            // Always fill description with full transcript
            updates.description = text;

            // ── Emit all changes to parent via event so formData.value is replaced ─
            // (Direct prop mutation doesn't trigger AntDV Select/DatePicker re-renders)
            emit('voiceFill', updates);

            if (matchedFields.length > 0) {
                message.success({
                    content: '✅ Auto-filled: ' + matchedFields.join('  |  '),
                    duration: 5,
                });
            } else {
                message.warning('Voice detected but no fields matched. Try speaking: "task name priority high due tomorrow assign to [person name]"');
            }
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,
            users,
            projects,
            filterOption,
            isSpeechSupported,
            isRecording,
            transcription,
            toggleRecording,
        };
    },
});
</script>

<style scoped>
.voice-assistant-section {
    margin-bottom: 24px;
}
.voice-card {
    background: linear-gradient(135deg, #f6f8fb 0%, #e9eff8 100%);
    border: 1px solid #d3e2f2;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}
.voice-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.voice-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e3a8a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.icon-voice {
    font-size: 18px;
    color: #3b82f6;
}
.voice-badge {
    background-color: #3b82f6;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
}
.voice-instructions {
    font-size: 12px;
    color: #4b5563;
    margin-bottom: 12px;
    line-height: 1.5;
}
.voice-actions {
    display: flex;
    justify-content: center;
    margin-bottom: 12px;
}
.btn-voice {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
    height: 40px;
    font-weight: 600;
    transition: all 0.3s ease;
}
.btn-voice.recording {
    background-color: #ef4444 !important;
    border-color: #ef4444 !important;
    box-shadow: 0 0 12px rgba(239, 68, 68, 0.6);
}
.pulse-icon {
    animation: pulse 1.5s infinite;
}
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
.voice-transcript {
    background-color: rgba(255, 255, 255, 0.7);
    border-left: 3px solid #52c41a;
    border-radius: 4px;
    padding: 10px;
    margin-top: 12px;
}
.transcript-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #1e3a8a;
    margin-bottom: 4px;
}
.transcript-quote {
    font-size: 13px;
    font-style: italic;
    color: #1f2937;
    margin: 0;
}
</style>
