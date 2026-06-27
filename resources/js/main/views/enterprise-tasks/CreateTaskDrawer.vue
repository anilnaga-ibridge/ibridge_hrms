<template>
    <a-drawer
        v-model:open="isOpen"
        title="Create New Task"
        width="600px"
        :maskClosable="false"
        @close="closeDrawer"
    >
        <template #extra>
            <a-button type="primary" :loading="saving" @click="handleCreateTask">
                Create Task
            </a-button>
        </template>

        <a-form layout="vertical">
            <a-form-item label="Task Templates (Select to Apply)">
                <a-select v-model:value="selectedTemplateXid" placeholder="Or select a template to prefill tasks..." allowClear>
                    <a-select-option v-for="tpl in templates" :key="tpl.xid" :value="tpl.xid">{{ tpl.name }} ({{ tpl.category }})</a-select-option>
                </a-select>
            </a-form-item>

            <a-divider style="margin: 12px 0;" />

            <a-form-item label="Task Title" :required="!selectedTemplateXid">
                <a-input v-model:value="taskForm.title" placeholder="What needs to be done?" :disabled="!!selectedTemplateXid" />
            </a-form-item>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Project">
                        <a-select v-model:value="taskForm.x_project_id" placeholder="Select project" allowClear @change="fetchProjectSections">
                            <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">{{ proj.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Section">
                        <a-select v-model:value="taskForm.x_section_id" placeholder="Select section" allowClear :disabled="!taskForm.x_project_id">
                            <a-select-option v-for="sec in activeSections" :key="sec.xid" :value="sec.xid">{{ sec.name }}</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Priority" required>
                        <a-select v-model:value="taskForm.priority" :disabled="!!selectedTemplateXid">
                            <a-select-option value="P1">P1 Critical</a-select-option>
                            <a-select-option value="P2">P2 High</a-select-option>
                            <a-select-option value="P3">P3 Medium</a-select-option>
                            <a-select-option value="P4">P4 Low</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Status" required>
                        <a-select v-model:value="taskForm.status" :disabled="!!selectedTemplateXid">
                            <a-select-option value="pending">Pending</a-select-option>
                            <a-select-option value="in_progress">In Progress</a-select-option>
                            <a-select-option value="under_review">Under Review</a-select-option>
                            <a-select-option value="testing">Testing</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- START DATE ROW -->
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Start Date">
                        <a-popover v-model:open="showStartDatePicker" trigger="click" placement="bottomLeft" overlayClassName="date-preset-popover">
                            <template #content>
                                <div class="date-preset-card">
                                    <div class="date-preset-header">Set Start Date</div>
                                    <div class="date-preset-shortcuts">
                                        <div :class="['preset-item', activeStartPreset === 'today' ? 'active' : '']" @click="setStartPreset('today')">
                                            <span class="preset-icon">📅</span>
                                            <span class="preset-label">Today</span>
                                            <span class="preset-date">{{ todayLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeStartPreset === 'tomorrow' ? 'active' : '']" @click="setStartPreset('tomorrow')">
                                            <span class="preset-icon">🌤️</span>
                                            <span class="preset-label">Tomorrow</span>
                                            <span class="preset-date">{{ tomorrowLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeStartPreset === 'next_week' ? 'active' : '']" @click="setStartPreset('next_week')">
                                            <span class="preset-icon">📆</span>
                                            <span class="preset-label">Next Week</span>
                                            <span class="preset-date">{{ nextWeekLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeStartPreset === 'next_weekend' ? 'active' : '']" @click="setStartPreset('next_weekend')">
                                            <span class="preset-icon">🏖️</span>
                                            <span class="preset-label">Next Weekend</span>
                                            <span class="preset-date">{{ nextWeekendLabel }}</span>
                                        </div>
                                    </div>
                                    <a-divider style="margin: 8px 0;" />
                                    <a-calendar v-model:value="startDateCalVal" :fullscreen="false" @select="onStartCalSelect" style="min-width: 280px;" />
                                    <div v-if="taskForm.start_date" style="display:flex; justify-content: flex-end; margin-top:8px; gap:8px;">
                                        <a-button size="small" danger @click="clearStartDate">Clear</a-button>
                                        <a-button size="small" type="primary" @click="showStartDatePicker = false">Done</a-button>
                                    </div>
                                </div>
                            </template>
                            <div class="date-trigger" :class="{ 'date-trigger--set': taskForm.start_date }">
                                <CalendarOutlined />
                                <span v-if="taskForm.start_date">{{ taskForm.start_date }}</span>
                                <span v-else style="color:#9ca3af;">Set start date</span>
                            </div>
                        </a-popover>
                    </a-form-item>
                </a-col>
                <a-col :span="12">
                    <a-form-item label="Due Date / Baseline Date">
                        <a-popover v-model:open="showDueDatePicker" trigger="click" placement="bottomLeft" overlayClassName="date-preset-popover">
                            <template #content>
                                <div class="date-preset-card">
                                    <div class="date-preset-header">Set Due Date</div>
                                    <div class="date-preset-shortcuts">
                                        <div :class="['preset-item', activeDuePreset === 'today' ? 'active' : '']" @click="setDuePreset('today')">
                                            <span class="preset-icon">📅</span>
                                            <span class="preset-label">Today</span>
                                            <span class="preset-date">{{ todayLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDuePreset === 'tomorrow' ? 'active' : '']" @click="setDuePreset('tomorrow')">
                                            <span class="preset-icon">🌤️</span>
                                            <span class="preset-label">Tomorrow</span>
                                            <span class="preset-date">{{ tomorrowLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDuePreset === 'next_week' ? 'active' : '']" @click="setDuePreset('next_week')">
                                            <span class="preset-icon">📆</span>
                                            <span class="preset-label">Next Week</span>
                                            <span class="preset-date">{{ nextWeekLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDuePreset === 'next_weekend' ? 'active' : '']" @click="setDuePreset('next_weekend')">
                                            <span class="preset-icon">🏖️</span>
                                            <span class="preset-label">Next Weekend</span>
                                            <span class="preset-date">{{ nextWeekendLabel }}</span>
                                        </div>
                                    </div>
                                    <a-divider style="margin: 8px 0;" />
                                    <a-calendar v-model:value="dueDateCalVal" :fullscreen="false" @select="onDueCalSelect" style="min-width: 280px;" />
                                    <div v-if="taskForm.due_date" style="display:flex; justify-content: flex-end; margin-top:8px; gap:8px;">
                                        <a-button size="small" danger @click="clearDueDate">Clear</a-button>
                                        <a-button size="small" type="primary" @click="showDueDatePicker = false">Done</a-button>
                                    </div>
                                </div>
                            </template>
                            <div class="date-trigger" :class="{ 'date-trigger--set': taskForm.due_date }">
                                <CalendarOutlined />
                                <span v-if="taskForm.due_date">{{ taskForm.due_date }}</span>
                                <span v-else style="color:#9ca3af;">Set due date</span>
                            </div>
                        </a-popover>
                    </a-form-item>
                </a-col>
            </a-row>

            <!-- DEADLINE ROW -->
            <a-row :gutter="16">
                <a-col :span="12">
                    <a-form-item label="Deadline">
                        <a-popover v-model:open="showDeadlinePicker" trigger="click" placement="bottomLeft" overlayClassName="date-preset-popover">
                            <template #content>
                                <div class="date-preset-card">
                                    <div class="date-preset-header">Set Deadline</div>
                                    <div class="date-preset-shortcuts">
                                        <div :class="['preset-item', activeDeadlinePreset === 'today' ? 'active' : '']" @click="setDeadlinePreset('today')">
                                            <span class="preset-icon">📅</span>
                                            <span class="preset-label">Today</span>
                                            <span class="preset-date">{{ todayLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDeadlinePreset === 'tomorrow' ? 'active' : '']" @click="setDeadlinePreset('tomorrow')">
                                            <span class="preset-icon">🌤️</span>
                                            <span class="preset-label">Tomorrow</span>
                                            <span class="preset-date">{{ tomorrowLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDeadlinePreset === 'next_week' ? 'active' : '']" @click="setDeadlinePreset('next_week')">
                                            <span class="preset-icon">📆</span>
                                            <span class="preset-label">Next Week</span>
                                            <span class="preset-date">{{ nextWeekLabel }}</span>
                                        </div>
                                        <div :class="['preset-item', activeDeadlinePreset === 'next_weekend' ? 'active' : '']" @click="setDeadlinePreset('next_weekend')">
                                            <span class="preset-icon">🏖️</span>
                                            <span class="preset-label">Next Weekend</span>
                                            <span class="preset-date">{{ nextWeekendLabel }}</span>
                                        </div>
                                    </div>
                                    <a-divider style="margin: 8px 0;" />
                                    <a-calendar v-model:value="deadlineCalVal" :fullscreen="false" @select="onDeadlineCalSelect" style="min-width: 280px;" />
                                    <div v-if="taskForm.deadline" style="display:flex; justify-content: flex-end; margin-top:8px; gap:8px;">
                                        <a-button size="small" danger @click="clearDeadline">Clear</a-button>
                                        <a-button size="small" type="primary" @click="showDeadlinePicker = false">Done</a-button>
                                    </div>
                                </div>
                            </template>
                            <div class="date-trigger" :class="{ 'date-trigger--set': taskForm.deadline }">
                                <CalendarOutlined />
                                <span v-if="taskForm.deadline">{{ taskForm.deadline }}</span>
                                <span v-else style="color:#9ca3af;">Set deadline</span>
                            </div>
                        </a-popover>
                    </a-form-item>
                </a-col>
            </a-row>

            <a-form-item label="Assignees">
                <a-select v-model:value="taskForm.assignees_xids" mode="multiple" placeholder="Select assignees" style="width: 100%">
                    <a-select-option v-for="user in users" :key="user.xid" :value="user.xid">{{ user.name }}</a-select-option>
                </a-select>
            </a-form-item>

            <a-form-item label="Labels" v-if="!selectedTemplateXid">
                <a-select v-model:value="taskForm.labels_xids" mode="multiple" placeholder="Select labels" style="width: 100%">
                    <a-select-option v-for="l in labelsList" :key="l.xid" :value="l.xid">{{ l.name }}</a-select-option>
                </a-select>
            </a-form-item>

            <div v-if="!selectedTemplateXid">
                <a-form-item label="Set Reminder" style="margin-bottom: 8px;">
                    <a-checkbox v-model:checked="taskForm.reminder_enabled">Enable Reminder</a-checkbox>
                </a-form-item>
                <a-form-item v-if="taskForm.reminder_enabled" label="Reminder Time" required>
                    <a-popover v-model:open="showReminderPicker" trigger="click" placement="bottomLeft" overlayClassName="date-preset-popover">
                        <template #content>
                            <div class="date-preset-card">
                                <div class="date-preset-header">Set Reminder</div>
                                <div class="date-preset-shortcuts">
                                    <div :class="['preset-item', activeReminderPreset === 'today_9am' ? 'active' : '']" @click="setReminderPreset('today_9am')">
                                        <span class="preset-icon">🌅</span>
                                        <span class="preset-label">Today 9 AM</span>
                                        <span class="preset-date">{{ todayLabel }}</span>
                                    </div>
                                    <div :class="['preset-item', activeReminderPreset === 'today_5pm' ? 'active' : '']" @click="setReminderPreset('today_5pm')">
                                        <span class="preset-icon">🌆</span>
                                        <span class="preset-label">Today 5 PM</span>
                                        <span class="preset-date">{{ todayLabel }}</span>
                                    </div>
                                    <div :class="['preset-item', activeReminderPreset === 'tomorrow_9am' ? 'active' : '']" @click="setReminderPreset('tomorrow_9am')">
                                        <span class="preset-icon">🌤️</span>
                                        <span class="preset-label">Tomorrow 9 AM</span>
                                        <span class="preset-date">{{ tomorrowLabel }}</span>
                                    </div>
                                    <div :class="['preset-item', activeReminderPreset === 'next_week_9am' ? 'active' : '']" @click="setReminderPreset('next_week_9am')">
                                        <span class="preset-icon">📆</span>
                                        <span class="preset-label">Next Week 9 AM</span>
                                        <span class="preset-date">{{ nextWeekLabel }}</span>
                                    </div>
                                </div>
                                <a-divider style="margin: 8px 0;" />
                                <a-date-picker v-model:value="taskForm.remind_at" show-time style="width: 100%;" value-format="YYYY-MM-DD HH:mm:ss" placeholder="Or pick a custom time" @change="onReminderCustomChange" />
                                <div v-if="taskForm.remind_at" style="display:flex; justify-content: flex-end; margin-top:8px; gap:8px;">
                                    <a-button size="small" danger @click="taskForm.remind_at = null; activeReminderPreset = null;">Clear</a-button>
                                    <a-button size="small" type="primary" @click="showReminderPicker = false">Done</a-button>
                                </div>
                            </div>
                        </template>
                        <div class="date-trigger" :class="{ 'date-trigger--set': taskForm.remind_at }">
                            <ClockCircleOutlined />
                            <span v-if="taskForm.remind_at">{{ taskForm.remind_at }}</span>
                            <span v-else style="color:#9ca3af;">Set reminder time</span>
                        </div>
                    </a-popover>
                </a-form-item>
            </div>

            <a-form-item label="Attachments" v-if="!selectedTemplateXid">
                <a-upload
                    name="file"
                    :beforeUpload="handleBeforeUpload"
                    :fileList="selectedFiles"
                    :remove="handleRemoveFile"
                    multiple
                >
                    <a-button><UploadOutlined /> Select Files (Max 10MB)</a-button>
                </a-upload>
            </a-form-item>
            <a-form-item label="Description" v-if="!selectedTemplateXid">
                <a-textarea v-model:value="taskForm.description" placeholder="Task description..." :rows="3" />
            </a-form-item>
        </a-form>
    </a-drawer>
</template>

<script>
import { defineComponent, ref, watch, onMounted, computed } from 'vue';
import { UploadOutlined, CalendarOutlined, ClockCircleOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';

import dayjs from 'dayjs';

export default defineComponent({
    props: {
        open: {
            type: Boolean,
            required: true
        },
        initialStartDate: {
            type: String,
            default: null
        },
        initialProjectXid: {
            type: String,
            default: null
        },
        initialSectionXid: {
            type: String,
            default: null
        },
        initialPriority: {
            type: String,
            default: null
        }
    },
    components: {
        UploadOutlined,
        CalendarOutlined,
        ClockCircleOutlined
    },
    emits: ['update:open', 'saved'],
    setup(props, { emit }) {
        const isOpen = ref(props.open);
        const templates = ref([]);
        const projects = ref([]);
        const activeSections = ref([]);
        const users = ref([]);
        const labelsList = ref([]);
        const selectedFiles = ref([]);
        const selectedTemplateXid = ref(undefined);
        const saving = ref(false);

        const taskForm = ref({
            title: '',
            x_project_id: undefined,
            x_section_id: undefined,
            priority: 'P3',
            status: 'pending',
            start_date: null,
            due_date: null,
            deadline: null,
            assignees_xids: [],
            labels_xids: [],
            reminder_enabled: false,
            remind_at: null,
            reminder_type: 'custom',
            description: '',
            recurrence_type: 'none'
        });

        // Popover open/close states
        const showStartDatePicker = ref(false);
        const showDueDatePicker = ref(false);
        const showDeadlinePicker = ref(false);
        const showReminderPicker = ref(false);

        // Calendar model values (dayjs objects for a-calendar)
        const startDateCalVal = ref(dayjs());
        const dueDateCalVal = ref(dayjs());
        const deadlineCalVal = ref(dayjs());

        const activeDuePreset = ref(null);
        const activeStartPreset = ref(null);
        const activeDeadlinePreset = ref(null);
        const activeReminderPreset = ref(null);

        watch(() => props.open, async (newVal) => {
            isOpen.value = newVal;
            if (newVal) {
                resetForm();
                // Apply initial start date if provided
                if (props.initialStartDate) {
                    taskForm.value.start_date = props.initialStartDate;
                    startDateCalVal.value = dayjs(props.initialStartDate);
                }
                // Pre-fill project and section/priority if provided
                if (props.initialProjectXid) {
                    taskForm.value.x_project_id = props.initialProjectXid;
                    await fetchProjectSections();
                }
                if (props.initialSectionXid) {
                    taskForm.value.x_section_id = props.initialSectionXid;
                }
                if (props.initialPriority) {
                    taskForm.value.priority = props.initialPriority;
                }
            }
        });

        watch(isOpen, (newVal) => {
            emit('update:open', newVal);
        });

        const resetForm = () => {
            selectedTemplateXid.value = undefined;
            taskForm.value = {
                title: '',
                x_project_id: undefined,
                x_section_id: undefined,
                priority: 'P3',
                status: 'pending',
                start_date: null,
                due_date: null,
                deadline: null,
                assignees_xids: [],
                labels_xids: [],
                reminder_enabled: false,
                remind_at: null,
                reminder_type: 'custom',
                description: '',
                recurrence_type: 'none'
            };
            selectedFiles.value = [];
            activeDuePreset.value = null;
            activeStartPreset.value = null;
            activeDeadlinePreset.value = null;
            activeReminderPreset.value = null;
            showStartDatePicker.value = false;
            showDueDatePicker.value = false;
            showDeadlinePicker.value = false;
            showReminderPicker.value = false;
            startDateCalVal.value = dayjs();
            dueDateCalVal.value = dayjs();
            deadlineCalVal.value = dayjs();
        };

        const closeDrawer = () => {
            isOpen.value = false;
        };

        const handleBeforeUpload = (file) => {
            selectedFiles.value.push(file);
            return false; // prevent auto upload
        };

        const handleRemoveFile = (file) => {
            const index = selectedFiles.value.indexOf(file);
            if (index > -1) {
                selectedFiles.value.splice(index, 1);
            }
        };

        const fetchProjectSections = async () => {
            taskForm.value.x_section_id = undefined;
            if (!taskForm.value.x_project_id) {
                activeSections.value = [];
                return;
            }
            try {
                const response = await axiosAdmin.get(`/enterprise-tasks/projects/${taskForm.value.x_project_id}`);
                activeSections.value = response.project.sections || [];
            } catch (error) {
                console.error(error);
            }
        };

        const getNextWeek = () => {
            const today = dayjs();
            const dayOfWeek = today.day();
            let daysToAdd = 1 - dayOfWeek;
            if (daysToAdd <= 0) daysToAdd += 7;
            return today.add(daysToAdd, 'day');
        };

        const getNextWeekend = () => {
            const today = dayjs();
            const dayOfWeek = today.day();
            let daysToAdd = 6 - dayOfWeek;
            if (dayOfWeek >= 5 || dayOfWeek === 0) daysToAdd += 7;
            return today.add(daysToAdd, 'day');
        };

        // Computed labels for presets
        const todayLabel = computed(() => dayjs().format('ddd MMM D'));
        const tomorrowLabel = computed(() => dayjs().add(1, 'day').format('ddd MMM D'));
        const nextWeekLabel = computed(() => getNextWeek().format('ddd MMM D'));
        const nextWeekendLabel = computed(() => getNextWeekend().format('ddd MMM D'));

        // Start Date helpers
        const setStartPreset = (preset) => {
            activeStartPreset.value = preset;
            let d;
            if (preset === 'today') d = dayjs();
            else if (preset === 'tomorrow') d = dayjs().add(1, 'day');
            else if (preset === 'next_week') d = getNextWeek();
            else if (preset === 'next_weekend') d = getNextWeekend();
            if (d) {
                taskForm.value.start_date = d.format('YYYY-MM-DD');
                startDateCalVal.value = d;
                showStartDatePicker.value = false;
            }
        };

        const onStartCalSelect = (val) => {
            taskForm.value.start_date = val.format('YYYY-MM-DD');
            startDateCalVal.value = val;
            activeStartPreset.value = null;
            showStartDatePicker.value = false;
        };

        const clearStartDate = () => {
            taskForm.value.start_date = null;
            activeStartPreset.value = null;
            startDateCalVal.value = dayjs();
            showStartDatePicker.value = false;
        };

        // Due Date helpers
        const setDuePreset = (preset) => {
            activeDuePreset.value = preset;
            let d;
            if (preset === 'today') d = dayjs();
            else if (preset === 'tomorrow') d = dayjs().add(1, 'day');
            else if (preset === 'next_week') d = getNextWeek();
            else if (preset === 'next_weekend') d = getNextWeekend();
            if (d) {
                taskForm.value.due_date = d.format('YYYY-MM-DD');
                dueDateCalVal.value = d;
                showDueDatePicker.value = false;
            }
        };

        const onDueCalSelect = (val) => {
            taskForm.value.due_date = val.format('YYYY-MM-DD');
            dueDateCalVal.value = val;
            activeDuePreset.value = null;
            showDueDatePicker.value = false;
        };

        const clearDueDate = () => {
            taskForm.value.due_date = null;
            activeDuePreset.value = null;
            dueDateCalVal.value = dayjs();
            showDueDatePicker.value = false;
        };

        // Deadline helpers
        const setDeadlinePreset = (preset) => {
            activeDeadlinePreset.value = preset;
            let d;
            if (preset === 'today') d = dayjs();
            else if (preset === 'tomorrow') d = dayjs().add(1, 'day');
            else if (preset === 'next_week') d = getNextWeek();
            else if (preset === 'next_weekend') d = getNextWeekend();
            if (d) {
                taskForm.value.deadline = d.format('YYYY-MM-DD');
                deadlineCalVal.value = d;
                showDeadlinePicker.value = false;
            }
        };

        const onDeadlineCalSelect = (val) => {
            taskForm.value.deadline = val.format('YYYY-MM-DD');
            deadlineCalVal.value = val;
            activeDeadlinePreset.value = null;
            showDeadlinePicker.value = false;
        };

        const clearDeadline = () => {
            taskForm.value.deadline = null;
            activeDeadlinePreset.value = null;
            deadlineCalVal.value = dayjs();
            showDeadlinePicker.value = false;
        };

        // Reminder helpers
        const setReminderPreset = (preset) => {
            activeReminderPreset.value = preset;
            if (preset === 'today_9am') {
                taskForm.value.remind_at = dayjs().hour(9).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            } else if (preset === 'today_5pm') {
                taskForm.value.remind_at = dayjs().hour(17).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            } else if (preset === 'tomorrow_9am') {
                taskForm.value.remind_at = dayjs().add(1, 'day').hour(9).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            } else if (preset === 'next_week_9am') {
                taskForm.value.remind_at = getNextWeek().hour(9).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            }
            showReminderPicker.value = false;
        };

        const onReminderCustomChange = () => {
            activeReminderPreset.value = null;
        };

        const handleCreateTask = async () => {
            if (selectedTemplateXid.value) {
                if (!taskForm.value.x_project_id) {
                    message.error('Project is required to apply template');
                    return;
                }
                saving.value = true;
                try {
                    await axiosAdmin.post(`/enterprise-tasks/templates/${selectedTemplateXid.value}/apply`, {
                        x_project_id: taskForm.value.x_project_id,
                        x_section_id: taskForm.value.x_section_id,
                        baseline_date: taskForm.value.due_date,
                        assignees_xids: taskForm.value.assignees_xids
                    });
                    message.success('Template tasks created successfully');
                    isOpen.value = false;
                    emit('saved');
                } catch (error) {
                    console.error(error);
                    message.error('Error applying template');
                } finally {
                    saving.value = false;
                }
            } else {
                if (!taskForm.value.title) {
                    message.error('Task title is required');
                    return;
                }
                saving.value = true;
                try {
                    const response = await axiosAdmin.post('/enterprise-tasks/tasks', {
                        title: taskForm.value.title,
                        x_project_id: taskForm.value.x_project_id,
                        x_section_id: taskForm.value.x_section_id,
                        priority: taskForm.value.priority,
                        status: taskForm.value.status,
                        start_date: taskForm.value.start_date,
                        due_date: taskForm.value.due_date,
                        deadline: taskForm.value.deadline,
                        assignees_xids: taskForm.value.assignees_xids,
                        labels_xids: taskForm.value.labels_xids,
                        description: taskForm.value.description,
                        recurrence_type: taskForm.value.recurrence_type
                    });
                    const newTask = response;

                    // Handle reminder if enabled
                    if (taskForm.value.reminder_enabled && taskForm.value.remind_at) {
                        try {
                            await axiosAdmin.post(`/enterprise-tasks/tasks/${newTask.xid}/reminders`, {
                                reminder_type: 'custom',
                                remind_at: taskForm.value.remind_at
                            });
                        } catch (remErr) {
                            console.error('Error setting reminder:', remErr);
                            message.warning('Task created, but failed to set reminder');
                        }
                    }

                    // Handle attachments upload
                    if (selectedFiles.value.length > 0) {
                        let uploadFailed = false;
                        for (const file of selectedFiles.value) {
                            try {
                                const formData = new FormData();
                                formData.append('file', file);
                                await axiosAdmin.post(`/enterprise-tasks/tasks/${newTask.xid}/attachments`, formData, {
                                    headers: { 'Content-Type': 'multipart/form-data' }
                                });
                            } catch (attErr) {
                                console.error('Error uploading attachment:', attErr);
                                uploadFailed = true;
                            }
                        }
                        if (uploadFailed) {
                            message.warning('Task created, but some attachments failed to upload');
                        }
                    }

                    window.dispatchEvent(new CustomEvent('task-created', { detail: newTask }));
                    message.success('Task created successfully');
                    isOpen.value = false;
                    emit('saved');
                } catch (error) {
                    console.error(error);
                    message.error('Error creating task');
                } finally {
                    saving.value = false;
                }
            }
        };

        const loadMetadata = async () => {
            try {
                const [projRes, templRes, userRes, labelRes] = await Promise.all([
                    axiosAdmin.get('/enterprise-tasks/projects'),
                    axiosAdmin.get('/enterprise-tasks/templates'),
                    axiosAdmin.get('/get-all-employees'),
                    axiosAdmin.get('/enterprise-tasks/labels')
                ]);
                projects.value = projRes;
                templates.value = templRes;
                users.value = userRes;
                labelsList.value = labelRes;
            } catch (error) {
                console.error('Error loading metadata:', error);
            }
        };

        onMounted(() => {
            loadMetadata();
        });

        return {
            isOpen,
            taskForm,
            templates,
            projects,
            activeSections,
            users,
            labelsList,
            selectedFiles,
            selectedTemplateXid,
            saving,
            showStartDatePicker,
            showDueDatePicker,
            showDeadlinePicker,
            showReminderPicker,
            startDateCalVal,
            dueDateCalVal,
            deadlineCalVal,
            activeDuePreset,
            activeStartPreset,
            activeDeadlinePreset,
            activeReminderPreset,
            setStartPreset,
            setDuePreset,
            setDeadlinePreset,
            setReminderPreset,
            onStartCalSelect,
            onDueCalSelect,
            onDeadlineCalSelect,
            clearStartDate,
            clearDueDate,
            clearDeadline,
            onReminderCustomChange,
            todayLabel,
            tomorrowLabel,
            nextWeekLabel,
            nextWeekendLabel,
            fetchProjectSections,
            handleBeforeUpload,
            handleRemoveFile,
            handleCreateTask,
            closeDrawer
        };
    }
});
</script>

<style scoped>
/* Date trigger button */
.date-trigger {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    color: #374151;
    background: #fff;
    transition: all 0.2s;
    width: 100%;
}
.date-trigger:hover {
    border-color: #4096ff;
    color: #4096ff;
}
.date-trigger--set {
    border-color: #10b981;
    color: #10b981;
    background: #f0fdf4;
}
.date-trigger--set:hover {
    border-color: #059669;
    color: #059669;
}
</style>

<style>
/* Global popover overrides for Todoist-style date presets */
.date-preset-popover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-radius: 8px;
}
.date-preset-popover .ant-popover-inner {
    padding: 0 !important;
    border-radius: 8px;
    overflow: hidden;
}
.date-preset-popover .ant-popover-inner-content {
    padding: 0 !important;
}

.date-preset-card {
    width: 300px;
    background: #fff;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    padding: 12px;
}

.date-preset-header {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f3f4f6;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.date-preset-shortcuts {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 8px;
}

.preset-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}

.preset-item:hover {
    background-color: #f3f4f6;
}

.preset-item.active {
    background-color: #eff6ff;
    color: #1d4ed8;
}

.preset-icon {
    font-size: 16px;
    margin-right: 10px;
    display: inline-flex;
    align-items: center;
}

.preset-label {
    font-weight: 500;
    font-size: 13px;
    color: #374151;
    flex-grow: 1;
}

.preset-item.active .preset-label {
    color: #1d4ed8;
}

.preset-date {
    font-size: 12px;
    color: #9ca3af;
}

.preset-item.active .preset-date {
    color: #3b82f6;
    font-weight: 500;
}

/* Calendar adjustments inside the card */
.date-preset-card .ant-picker-calendar {
    background: transparent !important;
    border: none !important;
}
.date-preset-card .ant-picker-calendar-header {
    padding: 8px 0 !important;
}
.date-preset-card .ant-picker-calendar-header .ant-select {
    font-size: 12px !important;
}
.date-preset-card .ant-picker-calendar-date-panel {
    border: none !important;
}
.date-preset-card .ant-picker-cell {
    padding: 2px 0 !important;
}
.date-preset-card .ant-picker-cell-inner {
    border-radius: 4px !important;
}
</style>
