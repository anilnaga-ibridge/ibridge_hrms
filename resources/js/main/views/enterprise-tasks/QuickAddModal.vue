<template>
    <a-modal
        v-model:open="isOpen"
        :footer="null"
        :closable="false"
        :title="null"
        width="550px"
        @cancel="closeModal"
        style="top: 150px;"
        :body-style="{ padding: '0', borderRadius: '12px', overflow: 'hidden' }"
    >
        <div class="quick-add-container">
            <!-- Title Input -->
            <input
                ref="titleInputRef"
                v-model="taskForm.title"
                type="text"
                placeholder="Task name"
                class="quick-add-title-input"
                @keydown.enter="handleSubmit"
            />
            
            <!-- Description Input -->
            <textarea
                v-model="taskForm.description"
                placeholder="Description"
                class="quick-add-desc-input"
                rows="2"
            />

            <!-- Action Buttons Row -->
            <div class="quick-add-actions-row">
                <!-- Date Button (Popover) -->
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
                    <button class="quick-add-btn" :class="{ 'quick-add-btn--active': taskForm.due_date }">
                        <CalendarOutlined />
                        <span>{{ taskForm.due_date ? taskForm.due_date : 'Date' }}</span>
                    </button>
                </a-popover>

                <!-- Attachment Button -->
                <button class="quick-add-btn" :class="{ 'quick-add-btn--active': selectedFiles.length > 0 }" @click="triggerFileInput">
                    <PaperClipOutlined />
                    <span>{{ selectedFiles.length > 0 ? `Attachment (${selectedFiles.length})` : 'Attachment' }}</span>
                </button>
                <input type="file" ref="fileInputRef" multiple @change="handleFileChange" style="display: none;" />

                <!-- Priority Button (Popover) -->
                <a-popover v-model:open="showPriorityPicker" trigger="click" placement="bottomLeft" overlayClassName="priority-preset-popover">
                    <template #content>
                        <div class="priority-list" style="min-width: 140px;">
                            <div v-for="prio in ['P1', 'P2', 'P3', 'P4']" :key="prio" class="priority-item" @click="selectPriority(prio)">
                                <FlagOutlined :style="{ color: getPriorityColorHex(prio) }" />
                                <span style="margin-left: 8px; font-weight: 500;">Priority {{ prio }}</span>
                            </div>
                        </div>
                    </template>
                    <button class="quick-add-btn" :class="{ 'quick-add-btn--active': taskForm.priority !== 'P3' }">
                        <FlagOutlined :style="{ color: getPriorityColorHex(taskForm.priority) }" />
                        <span>Priority {{ taskForm.priority }}</span>
                    </button>
                </a-popover>

                <!-- Reminders Button (Popover) -->
                <a-popover v-model:open="showReminderPicker" trigger="click" placement="bottomLeft" overlayClassName="date-preset-popover">
                    <template #content>
                        <div class="date-preset-card">
                            <div class="date-preset-header">Set Reminder</div>
                            <div class="date-preset-shortcuts">
                                <div :class="['preset-item', activeReminderPreset === 'today_9am' ? 'active' : '']" @click="setReminderPreset('today_9am')">
                                    <span class="preset-icon">🌅</span>
                                    <span class="preset-label">Today 9 AM</span>
                                </div>
                                <div :class="['preset-item', activeReminderPreset === 'today_5pm' ? 'active' : '']" @click="setReminderPreset('today_5pm')">
                                    <span class="preset-icon">🌆</span>
                                    <span class="preset-label">Today 5 PM</span>
                                </div>
                                <div :class="['preset-item', activeReminderPreset === 'tomorrow_9am' ? 'active' : '']" @click="setReminderPreset('tomorrow_9am')">
                                    <span class="preset-icon">🌤️</span>
                                    <span class="preset-label">Tomorrow 9 AM</span>
                                </div>
                            </div>
                            <a-divider style="margin: 8px 0;" />
                            <a-date-picker v-model:value="taskForm.remind_at" show-time style="width: 100%;" value-format="YYYY-MM-DD HH:mm:ss" placeholder="Or pick custom time" @change="onReminderCustomChange" />
                            <div v-if="taskForm.remind_at" style="display:flex; justify-content: flex-end; margin-top:8px; gap:8px;">
                                <a-button size="small" danger @click="taskForm.remind_at = null; activeReminderPreset = null;">Clear</a-button>
                                <a-button size="small" type="primary" @click="showReminderPicker = false">Done</a-button>
                            </div>
                        </div>
                    </template>
                    <button class="quick-add-btn" :class="{ 'quick-add-btn--active': taskForm.remind_at }" @click="taskForm.reminder_enabled = true">
                        <ClockCircleOutlined />
                        <span>{{ taskForm.remind_at ? 'Reminder Set' : 'Reminders' }}</span>
                    </button>
                </a-popover>

                <!-- More Button (Dropdown for Assignees, Labels, Start Date, Deadline) -->
                <a-dropdown :trigger="['click']" :getPopupContainer="(triggerNode) => triggerNode.parentNode">
                    <button class="quick-add-btn">
                        <EllipsisOutlined />
                    </button>
                    <template #overlay>
                        <a-menu class="quick-add-more-menu" style="min-width: 240px; padding: 8px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                            <!-- Start Date -->
                            <a-menu-item key="start_date">
                                <a-popover v-model:open="showStartDatePicker" trigger="click" placement="rightTop" overlayClassName="date-preset-popover">
                                    <template #content>
                                        <div class="date-preset-card">
                                            <div class="date-preset-header">Set Start Date</div>
                                            <div class="date-preset-shortcuts">
                                                <div :class="['preset-item', activeStartPreset === 'today' ? 'active' : '']" @click="setStartPreset('today')">
                                                    <span class="preset-icon">📅</span>
                                                    <span class="preset-label">Today</span>
                                                </div>
                                                <div :class="['preset-item', activeStartPreset === 'tomorrow' ? 'active' : '']" @click="setStartPreset('tomorrow')">
                                                    <span class="preset-icon">🌤️</span>
                                                    <span class="preset-label">Tomorrow</span>
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
                                    <div style="display:flex; justify-content: space-between; align-items:center; width:100%;">
                                        <span>🏁 Start Date:</span>
                                        <a-tag size="small" v-if="taskForm.start_date" color="blue">{{ taskForm.start_date }}</a-tag>
                                        <span v-else style="color:#9ca3af; font-size:12px;">Not set</span>
                                    </div>
                                </a-popover>
                            </a-menu-item>
                            
                            <!-- Deadline -->
                            <a-menu-item key="deadline">
                                <a-popover v-model:open="showDeadlinePicker" trigger="click" placement="rightTop" overlayClassName="date-preset-popover">
                                    <template #content>
                                        <div class="date-preset-card">
                                            <div class="date-preset-header">Set Deadline</div>
                                            <div class="date-preset-shortcuts">
                                                <div :class="['preset-item', activeDeadlinePreset === 'today' ? 'active' : '']" @click="setDeadlinePreset('today')">
                                                    <span class="preset-icon">📅</span>
                                                    <span class="preset-label">Today</span>
                                                </div>
                                                <div :class="['preset-item', activeDeadlinePreset === 'tomorrow' ? 'active' : '']" @click="setDeadlinePreset('tomorrow')">
                                                    <span class="preset-icon">🌤️</span>
                                                    <span class="preset-label">Tomorrow</span>
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
                                    <div style="display:flex; justify-content: space-between; align-items:center; width:100%;">
                                        <span>🚨 Deadline:</span>
                                        <a-tag size="small" v-if="taskForm.deadline" color="red">{{ taskForm.deadline }}</a-tag>
                                        <span v-else style="color:#9ca3af; font-size:12px;">Not set</span>
                                    </div>
                                </a-popover>
                            </a-menu-item>
                            <a-menu-divider />
                            
                            <!-- Assignees -->
                            <div style="padding: 4px 8px; font-weight: 500; font-size: 11px; color: #9ca3af; text-transform: uppercase;">Assignees</div>
                            <div style="padding: 0 8px 6px 8px;">
                                <a-select v-model:value="taskForm.assignees_xids" mode="multiple" placeholder="Assign users..." style="width: 100%;" size="small">
                                    <a-select-option v-for="user in users" :key="user.xid" :value="user.xid">{{ user.name }}</a-select-option>
                                </a-select>
                            </div>

                            <!-- Labels -->
                            <div style="padding: 4px 8px; font-weight: 500; font-size: 11px; color: #9ca3af; text-transform: uppercase;">Labels</div>
                            <div style="padding: 0 8px 4px 8px;">
                                <a-select v-model:value="taskForm.labels_xids" mode="multiple" placeholder="Select labels..." style="width: 100%;" size="small">
                                    <a-select-option v-for="l in labelsList" :key="l.xid" :value="l.xid">{{ l.name }}</a-select-option>
                                </a-select>
                            </div>
                        </a-menu>
                    </template>
                </a-dropdown>
            </div>

            <a-divider style="margin: 12px 0 12px 0;" />

            <!-- Bottom Row -->
            <div class="quick-add-footer">
                <!-- Project Selector Dropdown -->
                <a-dropdown :trigger="['click']">
                    <div class="quick-add-project-selector">
                        <InboxOutlined v-if="!selectedProjectName || selectedProjectName === 'Inbox'" />
                        <FolderOutlined v-else />
                        <span>{{ selectedProjectName || 'Inbox' }}</span>
                        <DownOutlined style="font-size: 10px;" />
                    </div>
                    <template #overlay>
                        <a-menu @click="handleProjectSelect" style="max-height: 250px; overflow-y: auto;">
                            <a-menu-item key="inbox">
                                <InboxOutlined style="margin-right: 8px;" /> Inbox
                            </a-menu-item>
                            <a-menu-divider />
                            <a-menu-item v-for="proj in projects" :key="proj.xid">
                                <FolderOutlined style="margin-right: 8px;" /> {{ proj.name }}
                            </a-menu-item>
                        </a-menu>
                    </template>
                </a-dropdown>

                <!-- Cancel and Save Buttons -->
                <div class="quick-add-footer-btns">
                    <button class="quick-add-cancel-btn" @click="closeModal">Cancel</button>
                    <button class="quick-add-save-btn" :disabled="!taskForm.title || saving" @click="handleSubmit">
                        <a-spin v-if="saving" size="small" style="margin-right: 6px; color:#fff;" /> Add task
                    </button>
                </div>
            </div>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, watch, nextTick, computed } from 'vue';
import {
    CalendarOutlined, PaperClipOutlined, FlagOutlined,
    ClockCircleOutlined, EllipsisOutlined, FolderOutlined,
    InboxOutlined, DownOutlined
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    props: {
        open: Boolean,
        initialDate: String
    },
    components: {
        CalendarOutlined,
        PaperClipOutlined,
        FlagOutlined,
        ClockCircleOutlined,
        EllipsisOutlined,
        FolderOutlined,
        InboxOutlined,
        DownOutlined
    },
    setup(props, { emit }) {
        const isOpen = ref(false);
        const saving = ref(false);
        const projects = ref([]);
        const users = ref([]);
        const labelsList = ref([]);
        const inboxProjectXid = ref(null);
        
        const selectedProjectXid = ref(null);
        const selectedProjectName = ref('Inbox');
        const selectedFiles = ref([]);
        
        // Form Fields
        const taskForm = ref({
            title: '',
            description: '',
            x_project_id: null,
            x_section_id: null,
            priority: 'P3',
            status: 'pending',
            start_date: null,
            due_date: null,
            deadline: null,
            assignees_xids: [],
            labels_xids: [],
            reminder_enabled: false,
            remind_at: null,
            recurrence_type: 'none'
        });

        // Date Picker controls
        const showStartDatePicker = ref(false);
        const showDueDatePicker = ref(false);
        const showDeadlinePicker = ref(false);
        const showReminderPicker = ref(false);
        const showPriorityPicker = ref(false);

        const startDateCalVal = ref(dayjs());
        const dueDateCalVal = ref(dayjs());
        const deadlineCalVal = ref(dayjs());

        const activeStartPreset = ref(null);
        const activeDuePreset = ref(null);
        const activeDeadlinePreset = ref(null);
        const activeReminderPreset = ref(null);

        const titleInputRef = ref(null);
        const fileInputRef = ref(null);

        // Track if manually set to prevent NLP override
        const isDateManuallySet = ref(false);
        const isPriorityManuallySet = ref(false);

        // Date helper
        const getNextWeek = () => dayjs().add(1, 'week').startOf('week').add(1, 'day'); // Monday
        const getNextWeekend = () => dayjs().add(1, 'week').startOf('week').add(6, 'day'); // Saturday

        const todayLabel = computed(() => dayjs().format('ddd MMM D'));
        const tomorrowLabel = computed(() => dayjs().add(1, 'day').format('ddd MMM D'));
        const nextWeekLabel = computed(() => getNextWeek().format('ddd MMM D'));
        const nextWeekendLabel = computed(() => getNextWeekend().format('ddd MMM D'));

        watch(() => props.open, (newVal) => {
            isOpen.value = newVal;
            if (newVal) {
                // Reset form
                taskForm.value = {
                    title: props.initialDate ? 'on ' + props.initialDate + ' ' : '',
                    description: '',
                    x_project_id: null,
                    x_section_id: null,
                    priority: 'P3',
                    status: 'pending',
                    start_date: null,
                    due_date: props.initialDate || null,
                    deadline: null,
                    assignees_xids: [],
                    labels_xids: [],
                    reminder_enabled: false,
                    remind_at: null,
                    recurrence_type: 'none'
                };
                
                selectedFiles.value = [];
                isDateManuallySet.value = !!props.initialDate;
                isPriorityManuallySet.value = false;
                
                dueDateCalVal.value = props.initialDate ? dayjs(props.initialDate) : dayjs();
                startDateCalVal.value = dayjs();
                deadlineCalVal.value = dayjs();
                
                activeDuePreset.value = null;
                activeStartPreset.value = null;
                activeDeadlinePreset.value = null;
                activeReminderPreset.value = null;
                
                selectedProjectXid.value = null;
                selectedProjectName.value = 'Inbox';

                loadInitialData();
                nextTick(() => {
                    if (titleInputRef.value) {
                        titleInputRef.value.focus();
                    }
                });
            }
        });

        watch(isOpen, (newVal) => {
            emit('update:open', newVal);
            if (!newVal) {
                emit('close');
            }
        });

        // NLP real-time scanner on Title input
        watch(() => taskForm.value.title, (newVal) => {
            if (!newVal) return;

            // 1. Priority parsing (e.g., p1 or P1)
            if (!isPriorityManuallySet.value) {
                const pMatch = newVal.match(/\b(p[1-4])\b/i);
                if (pMatch) {
                    taskForm.value.priority = pMatch[1].toUpperCase();
                }
            }

            // 2. Simple date parser keywords
            if (!isDateManuallySet.value) {
                if (newVal.match(/\btomorrow\b/i)) {
                    const tom = dayjs().add(1, 'day');
                    taskForm.value.due_date = tom.format('YYYY-MM-DD');
                    dueDateCalVal.value = tom;
                    activeDuePreset.value = 'tomorrow';
                } else if (newVal.match(/\btoday\b/i)) {
                    const tod = dayjs();
                    taskForm.value.due_date = tod.format('YYYY-MM-DD');
                    dueDateCalVal.value = tod;
                    activeDuePreset.value = 'today';
                } else if (newVal.match(/\bnext week\b/i)) {
                    const nW = getNextWeek();
                    taskForm.value.due_date = nW.format('YYYY-MM-DD');
                    dueDateCalVal.value = nW;
                    activeDuePreset.value = 'next_week';
                } else if (newVal.match(/\bnext weekend\b/i)) {
                    const nWknd = getNextWeekend();
                    taskForm.value.due_date = nWknd.format('YYYY-MM-DD');
                    dueDateCalVal.value = nWknd;
                    activeDuePreset.value = 'next_weekend';
                }
            }

            // 3. Hashtags for labels
            const tagMatches = newVal.match(/#([a-zA-Z0-9_-]+)/g);
            if (tagMatches) {
                tagMatches.forEach(tag => {
                    const labelName = tag.replace('#', '').toLowerCase();
                    const foundLabel = labelsList.value.find(l => l.name.toLowerCase() === labelName);
                    if (foundLabel && !taskForm.value.labels_xids.includes(foundLabel.xid)) {
                        taskForm.value.labels_xids.push(foundLabel.xid);
                    }
                });
            }
        });

        const loadInitialData = async () => {
            try {
                const [projRes, inboxRes, userRes, labelRes] = await Promise.all([
                    axiosAdmin.get('/enterprise-tasks/projects'),
                    axiosAdmin.get('/enterprise-tasks/projects-inbox'),
                    axiosAdmin.get('/get-all-employees'),
                    axiosAdmin.get('/enterprise-tasks/labels')
                ]);
                projects.value = projRes.filter(p => p.xid !== inboxRes.xid);
                inboxProjectXid.value = inboxRes.xid;
                users.value = userRes;
                labelsList.value = labelRes;
            } catch (err) {
                console.error('Failed to load initial data:', err);
            }
        };

        // Project Selection
        const handleProjectSelect = (e) => {
            if (e.key === 'inbox') {
                selectedProjectXid.value = null;
                selectedProjectName.value = 'Inbox';
                taskForm.value.x_project_id = null;
            } else {
                const selected = projects.value.find(p => p.xid === e.key);
                if (selected) {
                    selectedProjectXid.value = selected.xid;
                    selectedProjectName.value = selected.name;
                    taskForm.value.x_project_id = selected.xid;
                }
            }
        };

        // File attachment triggers
        const triggerFileInput = () => {
            if (fileInputRef.value) {
                fileInputRef.value.click();
            }
        };

        const handleFileChange = (e) => {
            const files = Array.from(e.target.files);
            selectedFiles.value.push(...files);
        };

        // Priority helpers
        const selectPriority = (prio) => {
            isPriorityManuallySet.value = true;
            taskForm.value.priority = prio;
            showPriorityPicker.value = false;
        };

        const getPriorityColorHex = (prio) => {
            const colors = { P1: '#dc2626', P2: '#d97706', P3: '#2563eb', P4: '#16a34a' };
            return colors[prio] || '#2563eb';
        };

        // Presets date handling
        const setStartPreset = (preset) => {
            activeStartPreset.value = preset;
            let d;
            if (preset === 'today') d = dayjs();
            else if (preset === 'tomorrow') d = dayjs().add(1, 'day');
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

        const setDuePreset = (preset) => {
            isDateManuallySet.value = true;
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
            isDateManuallySet.value = true;
            taskForm.value.due_date = val.format('YYYY-MM-DD');
            dueDateCalVal.value = val;
            activeDuePreset.value = null;
            showDueDatePicker.value = false;
        };

        const clearDueDate = () => {
            isDateManuallySet.value = true;
            taskForm.value.due_date = null;
            activeDuePreset.value = null;
            dueDateCalVal.value = dayjs();
            showDueDatePicker.value = false;
        };

        const setDeadlinePreset = (preset) => {
            activeDeadlinePreset.value = preset;
            let d;
            if (preset === 'today') d = dayjs();
            else if (preset === 'tomorrow') d = dayjs().add(1, 'day');
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

        const setReminderPreset = (preset) => {
            taskForm.value.reminder_enabled = true;
            activeReminderPreset.value = preset;
            if (preset === 'today_9am') {
                taskForm.value.remind_at = dayjs().hour(9).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            } else if (preset === 'today_5pm') {
                taskForm.value.remind_at = dayjs().hour(17).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            } else if (preset === 'tomorrow_9am') {
                taskForm.value.remind_at = dayjs().add(1, 'day').hour(9).minute(0).second(0).format('YYYY-MM-DD HH:mm:ss');
            }
            showReminderPicker.value = false;
        };

        const onReminderCustomChange = () => {
            taskForm.value.reminder_enabled = true;
            activeReminderPreset.value = null;
        };

        const closeModal = () => {
            isOpen.value = false;
            emit('close');
        };

        // Submit action
        const handleSubmit = async () => {
            if (!taskForm.value.title.trim()) {
                message.error('Task title is required');
                return;
            }
            saving.value = true;
            try {
                // Strip NLP helper tokens from the title to keep it clean
                let cleanTitle = taskForm.value.title;
                cleanTitle = cleanTitle.replace(/\b(p[1-4])\b/i, '');
                cleanTitle = cleanTitle.replace(/#([a-zA-Z0-9_-]+)/g, '');
                cleanTitle = cleanTitle.replace(/\b(tomorrow|today|next week|next weekend)\b/i, '');
                cleanTitle = cleanTitle.trim().replace(/\s+/, ' ');
                if (!cleanTitle) {
                    cleanTitle = taskForm.value.title;
                }

                let projId = taskForm.value.x_project_id;
                if (!projId) {
                    projId = inboxProjectXid.value;
                }

                let secId = null;
                if (projId) {
                    const sectionsRes = await axiosAdmin.get(`/enterprise-tasks/projects/${projId}`);
                    const sections = sectionsRes.sections || [];
                    if (sections.length > 0) {
                        secId = sections[0].xid;
                    }
                }

                const response = await axiosAdmin.post('/enterprise-tasks/tasks', {
                    title: cleanTitle,
                    x_project_id: projId,
                    x_section_id: secId,
                    priority: taskForm.value.priority,
                    status: 'pending',
                    start_date: taskForm.value.start_date,
                    due_date: taskForm.value.due_date,
                    deadline: taskForm.value.deadline,
                    assignees_xids: taskForm.value.assignees_xids,
                    labels_xids: taskForm.value.labels_xids,
                    description: taskForm.value.description,
                    recurrence_type: taskForm.value.recurrence_type
                });

                const newTask = response;

                // Create reminders
                if (taskForm.value.reminder_enabled && taskForm.value.remind_at) {
                    try {
                        await axiosAdmin.post(`/enterprise-tasks/tasks/${newTask.xid}/reminders`, {
                            reminder_type: 'custom',
                            remind_at: taskForm.value.remind_at
                        });
                    } catch (remErr) {
                        console.error('Failed to create reminder:', remErr);
                    }
                }

                // Handle attachments
                if (selectedFiles.value.length > 0) {
                    for (const file of selectedFiles.value) {
                        try {
                            const formData = new FormData();
                            formData.append('file', file);
                            await axiosAdmin.post(`/enterprise-tasks/tasks/${newTask.xid}/attachments`, formData, {
                                headers: { 'Content-Type': 'multipart/form-data' }
                            });
                        } catch (attErr) {
                            console.error('Failed to upload attachment:', attErr);
                        }
                    }
                }

                window.dispatchEvent(new CustomEvent('task-created', { detail: newTask }));
                message.success('Task created successfully');
                emit('created', newTask);
                closeModal();
            } catch (error) {
                console.error(error);
                message.error('Failed to quick add task');
            } finally {
                saving.value = false;
            }
        };

        return {
            isOpen,
            saving,
            projects,
            users,
            labelsList,
            selectedProjectXid,
            selectedProjectName,
            selectedFiles,
            taskForm,
            
            showStartDatePicker,
            showDueDatePicker,
            showDeadlinePicker,
            showReminderPicker,
            showPriorityPicker,
            
            startDateCalVal,
            dueDateCalVal,
            deadlineCalVal,
            
            activeStartPreset,
            activeDuePreset,
            activeDeadlinePreset,
            activeReminderPreset,
            
            todayLabel,
            tomorrowLabel,
            nextWeekLabel,
            nextWeekendLabel,
            
            titleInputRef,
            fileInputRef,
            
            handleProjectSelect,
            triggerFileInput,
            handleFileChange,
            selectPriority,
            getPriorityColorHex,
            
            setStartPreset,
            onStartCalSelect,
            clearStartDate,
            
            setDuePreset,
            onDueCalSelect,
            clearDueDate,
            
            setDeadlinePreset,
            onDeadlineCalSelect,
            clearDeadline,
            
            setReminderPreset,
            onReminderCustomChange,
            
            closeModal,
            handleSubmit
        };
    }
});
</script>

<style scoped>
.quick-add-container {
    padding: 16px;
    background: #ffffff;
    display: flex;
    flex-direction: column;
}

.quick-add-title-input {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    font-size: 17px;
    font-weight: 500;
    color: #1f2937;
    width: 100%;
    padding: 0;
    margin-bottom: 6px;
    background: transparent;
}
.quick-add-title-input::placeholder {
    color: #9ca3af;
}

.quick-add-desc-input {
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    font-size: 13px;
    color: #4b5563;
    width: 100%;
    padding: 0;
    margin-bottom: 12px;
    background: transparent;
    resize: none;
}
.quick-add-desc-input::placeholder {
    color: #9ca3af;
}

.quick-add-actions-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.quick-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    font-size: 12px;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
}
.quick-add-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    color: #1f2937;
}
.quick-add-btn--active {
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #2563eb;
}

.quick-add-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}

.quick-add-project-selector {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #4b5563;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 6px;
    transition: all 0.2s;
}
.quick-add-project-selector:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.quick-add-footer-btns {
    display: flex;
    gap: 8px;
}

.quick-add-cancel-btn {
    background: #f3f4f6;
    border: none;
    color: #4b5563;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: background 0.2s;
}
.quick-add-cancel-btn:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.quick-add-save-btn {
    background: #e99e96; /* Coral/peach */
    border: none;
    color: #fff;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s;
}
.quick-add-save-btn:hover {
    background: #e08b82;
}
.quick-add-save-btn:disabled {
    background: #f3c1bc;
    cursor: not-allowed;
}

.priority-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.priority-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    transition: background 0.2s;
}
.priority-item:hover {
    background: #f3f4f6;
}
</style>

<style>
/* Priority popover override */
.priority-preset-popover .ant-popover-inner {
    padding: 4px !important;
}
.priority-preset-popover .ant-popover-inner-content {
    padding: 0 !important;
}

/* Global popover overrides for Todoist-style date presets */
.date-preset-popover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border-radius: 8px;
    z-index: 100000;
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
