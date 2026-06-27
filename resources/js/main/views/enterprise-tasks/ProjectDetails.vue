<template>
    <AdminPageHeader v-if="project && !project.is_inbox && project.name !== 'Inbox'">
        <template #header>
            <a-page-header :title="project.name" :sub-title="project.description" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openSectionModal">
                        <PlusOutlined /> Create Section
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.enterprise_tasks.projects' }">Projects</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>{{ project.name }}</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div v-if="project" style="margin: 20px 16px;">
        <!-- REGULAR PROJECT VIEW -->
        <div v-if="!project.is_inbox && project.name !== 'Inbox'">
            <!-- Analytics summary -->
            <a-row :gutter="16" style="margin-bottom: 24px;">
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Total Tasks" :value="analytics.total_tasks" />
                    </a-card>
                </a-col>
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Completed" :value="analytics.completed_tasks" valueStyle="color: #10b981;" />
                    </a-card>
                </a-col>
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Pending" :value="analytics.pending_tasks" valueStyle="color: #3b82f6;" />
                    </a-card>
                </a-col>
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Overdue" :value="analytics.overdue_tasks" valueStyle="color: #ef4444;" />
                    </a-card>
                </a-col>
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Members" :value="analytics.members_count" />
                    </a-card>
                </a-col>
                <a-col :xs="12" :md="4">
                    <a-card :bordered="false" style="border-radius: 8px; text-align: center; background: #fafafa;">
                        <a-statistic title="Progress" :value="analytics.productivity_percentage" suffix="%" valueStyle="color: #8b5cf6;" />
                    </a-card>
                </a-col>
            </a-row>

            <a-row :gutter="24">
                <!-- Sections Management -->
                <a-col :xs="24" :md="16">
                    <a-card title="Custom Sections" :bordered="false" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                        <div style="font-size: 13px; color: #6b7280; margin-bottom: 16px;">
                            Drag and drop sections to reorder them on the Kanban Board.
                        </div>

                        <div class="sections-list">
                            <div
                                v-for="(sec, index) in sections"
                                :key="sec.xid"
                                class="section-row"
                                draggable="true"
                                @dragstart="dragStart($event, index)"
                                @dragover.prevent
                                @drop="drop($event, index)"
                                style="padding: 12px 16px; border: 1px solid #f3f4f6; border-radius: 8px; margin-bottom: 8px; background: #ffffff; cursor: grab; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);"
                            >
                                <div style="display: flex; align-items: center;">
                                    <span style="color: #d1d5db; margin-right: 12px; font-size: 16px;"><DragOutlined /></span>
                                    <span style="font-weight: 500; font-size: 14px;">{{ sec.name }}</span>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a-button size="small" type="link" @click="openRenameModal(sec)">Rename</a-button>
                                    <a-button size="small" type="link" danger @click="confirmDeleteSection(sec)">Delete</a-button>
                                </div>
                            </div>
                        </div>

                        <div v-if="sections.length === 0" style="text-align: center; padding: 40px 0;">
                            <a-empty description="No custom sections. Create sections to organize tasks!" />
                        </div>
                    </a-card>
                </a-col>

                <!-- Project Members list -->
                <a-col :xs="24" :md="8">
                    <a-card title="Project Members" :bordered="false" style="border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                        <a-list item-layout="horizontal" :data-source="project.members">
                            <template #renderItem="{ item }">
                                <a-list-item>
                                    <a-list-item-meta :description="item.role.toUpperCase()">
                                        <template #title>
                                            {{ item.user ? item.user.name : 'Unknown Employee' }}
                                        </template>
                                        <template #avatar>
                                            <a-avatar :src="item.user ? item.user.profile_image_url : ''" />
                                        </template>
                                    </a-list-item-meta>
                                </a-list-item>
                            </template>
                        </a-list>
                    </a-card>
                </a-col>
            </a-row>

            <!-- CREATE / RENAME SECTION MODAL -->
            <a-modal v-model:open="sectionModalOpen" :title="editMode ? 'Rename Section' : 'Create Section'" @ok="handleSectionOk">
                <a-form layout="vertical">
                    <a-form-item label="Section Name" required>
                        <a-input v-model:value="sectionForm.name" placeholder="Enter section name (e.g. Backlog, Testing)" />
                    </a-form-item>
                </a-form>
            </a-modal>
        </div>

        <!-- INBOX PAGE -->
        <div v-else class="inbox-page-container" style="max-width: 800px; margin: 0 auto; padding: 10px 10px;">
            <!-- Heading / Header -->
            <div style="margin-bottom: 24px; border-bottom: 1px solid #f0f0f0; padding-bottom: 16px;">
                <h1 style="font-size: 28px; font-weight: 700; margin: 0; color: #1f2937; line-height: 1.2;">Inbox</h1>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="inboxTasks.length === 0" style="text-align: center; padding: 60px 20px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div class="empty-inbox-illustration" style="margin-bottom: 24px; position: relative;">
                    <svg width="180" height="180" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" style="max-width: 100%;">
                        <circle cx="100" cy="100" r="80" fill="#fef2f2" />
                        <path d="M60 80H140V130C140 135.523 135.523 140 130 140H70C64.4772 140 60 135.523 60 130V80Z" fill="#fee2e2" stroke="#fca5a5" stroke-width="3" stroke-linejoin="round"/>
                        <path d="M60 80L100 105L140 80" stroke="#fca5a5" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M90 140H110" stroke="#ef4444" stroke-width="4" stroke-linecap="round"/>
                        <circle cx="100" cy="100" r="15" fill="#fca5a5" style="opacity: 0.15;" />
                    </svg>
                </div>
                <h2 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">Inbox</h2>
                <h3 style="font-size: 16px; font-weight: 600; color: #db4c3f; margin-bottom: 12px; letter-spacing: 0.5px;">Capture now, plan later</h3>
                <p style="font-size: 14px; color: #6b7280; max-width: 440px; line-height: 1.6; margin-bottom: 24px;">
                    Inbox is your go-to spot for quick task entry. Clear your mind now, organize when you’re ready.
                </p>
                <a-button type="primary" size="large" style="background: #db4c3f; border-color: #db4c3f; border-radius: 8px; font-weight: 600; padding: 0 28px; height: 44px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(219, 76, 63, 0.2);" @click="openCreateTaskDrawer">
                    <PlusOutlined /> Add task
                </a-button>
            </div>

            <!-- TASKS LIST -->
            <div v-else style="display: flex; flex-direction: column;">
                <div v-for="task in inboxTasks" :key="task.xid" class="inbox-task-item" style="display: flex; align-items: flex-start; gap: 8px; padding: 12px 4px; border-bottom: 1px solid #f0f0f0; transition: all 0.2s ease; cursor: pointer; position: relative;" @click="viewTaskDetails(task)">
                    <!-- Drag handle -->
                    <div class="drag-handle-container" style="display: flex; align-items: center; justify-content: center; height: 20px; width: 16px; margin-right: 2px;">
                        <HolderOutlined class="drag-holder-icon" style="font-size: 14px; color: #ccc; cursor: grab;" />
                    </div>

                    <!-- Custom checkbox -->
                    <div style="padding-top: 1px;" @click.stop="completeTask(task)">
                        <div class="checkbox-circle" style="width: 18px; height: 18px; border: 2px solid #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                            <CheckOutlined class="check-icon" style="font-size: 10px; color: transparent; transition: all 0.2s;" />
                        </div>
                    </div>

                    <!-- Title / description / metadata -->
                    <div style="flex: 1; min-width: 0; padding-left: 4px;">
                        <div style="font-size: 14px; font-weight: 400; color: #1f2937; margin-bottom: 2px;">
                            {{ task.title }}
                        </div>
                        <div v-if="task.description" style="font-size: 12px; color: #888;">
                            {{ task.description }}
                        </div>

                        <!-- Due date / Priority / Labels / Assignees -->
                        <div v-if="task.due_date || task.priority || (task.labels && task.labels.length > 0) || (task.assignees && task.assignees.length > 0)" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-top: 6px;">
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <!-- Due Date -->
                                <span v-if="task.due_date" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;" :style="{ color: isOverdue(task) ? '#ef4444' : '#6b7280' }">
                                    <CalendarOutlined />
                                    {{ task.due_date }}
                                </span>

                                <!-- Priority -->
                                <span v-if="task.priority" style="font-size: 11px; display: inline-flex; align-items: center; gap: 4px; font-weight: 500;" :style="{ color: getPriorityColorHex(task.priority) }">
                                    <span :style="{ display: 'inline-block', width: '8px', height: '8px', borderRadius: '50%', background: getPriorityColorHex(task.priority) }" />
                                    {{ task.priority }}
                                </span>

                                <!-- Labels -->
                                <div v-if="task.labels && task.labels.length > 0" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <a-tag v-for="l in task.labels" :key="l.xid" :color="l.color" size="small" style="font-size: 10px; padding: 0 6px; border-radius: 4px;">
                                        {{ l.name }}
                                    </a-tag>
                                </div>
                            </div>

                            <!-- Assignees -->
                            <div style="display: flex; align-items: center;">
                                <a-tooltip v-for="a in task.assignees" :key="a.xid" :title="a.name">
                                    <a-avatar size="small" :src="a.profile_image_url" style="border: 1px solid #fff; margin-right: -4px;" />
                                </a-tooltip>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons (visible on hover) -->
                    <div class="task-action-buttons" style="display: none; align-items: center; gap: 8px; padding-left: 12px; align-self: flex-start; margin-top: 1px;">
                        <a-tooltip title="Edit task name">
                            <EditOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                        </a-tooltip>
                        <a-tooltip title="Set due date">
                            <CalendarOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                        </a-tooltip>
                        <a-tooltip title="Comment">
                            <MessageOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.stop="viewTaskDetails(task)" />
                        </a-tooltip>
                        <a-dropdown :trigger="['click']">
                            <MoreOutlined class="action-icon-btn" style="font-size: 16px; color: #666; cursor: pointer;" @click.prevent.stop />
                            <template #overlay>
                                <a-menu class="todoist-context-menu" style="min-width: 250px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 6px 0;">
                                    <a-menu-item key="add_above" @click="addTaskAbove(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><ArrowUpOutlined /></span>
                                            <span class="menu-title">Add task above</span>
                                        </span>
                                    </a-menu-item>
                                    <a-menu-item key="add_below" @click="addTaskBelow(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><ArrowDownOutlined /></span>
                                            <span class="menu-title">Add task below</span>
                                        </span>
                                    </a-menu-item>
                                    
                                    <a-menu-divider />
                                    
                                    <a-menu-item key="edit" @click="viewTaskDetails(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><EditOutlined /></span>
                                            <span class="menu-title">Edit</span>
                                            <span class="menu-shortcut">⌘ E</span>
                                        </span>
                                    </a-menu-item>
                                    
                                    <a-menu-divider />
                                    
                                    <div class="menu-section-header">Date <span class="header-shortcut">T</span></div>
                                    <div class="date-quick-actions">
                                        <a-tooltip title="Today">
                                            <div class="date-icon-btn today-btn" @click="setTaskDate(task, 'today')">
                                                <span class="date-day-num">{{ currentDayNumber }}</span>
                                            </div>
                                        </a-tooltip>
                                        <a-tooltip title="Tomorrow">
                                            <div class="date-icon-btn tomorrow-btn" @click="setTaskDate(task, 'tomorrow')">
                                                <span class="date-icon-sun">☀️</span>
                                            </div>
                                        </a-tooltip>
                                        <a-tooltip title="Next week">
                                            <div class="date-icon-btn nextweek-btn" @click="setTaskDate(task, 'next_week')">
                                                <span class="date-icon-next">➡️</span>
                                            </div>
                                        </a-tooltip>
                                        <a-tooltip title="Weekend">
                                            <div class="date-icon-btn weekend-btn" @click="setTaskDate(task, 'weekend')">
                                                <span class="date-icon-couch">🛋️</span>
                                            </div>
                                        </a-tooltip>
                                        <a-tooltip title="Custom Date">
                                            <a-date-picker 
                                                :value="null"
                                                size="small"
                                                :bordered="false"
                                                class="date-icon-btn custom-picker-btn"
                                                @change="(date, dateString) => handleCustomDateChange(task, dateString)"
                                                placeholder="..."
                                            >
                                                <template #suffixIcon></template>
                                            </a-date-picker>
                                        </a-tooltip>
                                    </div>
                                    
                                    <div class="menu-section-header">Priority <span class="header-shortcut">Y</span></div>
                                    <div class="priority-quick-actions">
                                        <a-tooltip title="P1 Critical">
                                            <FlagFilled class="priority-flag flag-p1" @click="setTaskPriority(task, 'P1')" />
                                        </a-tooltip>
                                        <a-tooltip title="P2 High">
                                            <FlagFilled class="priority-flag flag-p2" @click="setTaskPriority(task, 'P2')" />
                                        </a-tooltip>
                                        <a-tooltip title="P3 Medium">
                                            <FlagFilled class="priority-flag flag-p3" @click="setTaskPriority(task, 'P3')" />
                                        </a-tooltip>
                                        <a-tooltip title="P4 Low">
                                            <FlagOutlined class="priority-flag flag-p4" @click="setTaskPriority(task, 'P4')" />
                                        </a-tooltip>
                                    </div>

                                    <!-- Assigned Members Section -->
                                    <a-menu-divider />
                                    <div class="menu-section-header" style="display:flex; align-items:center; justify-content:space-between;">
                                        <span>Assigned Members</span>
                                        <a-tooltip title="Manage assignees">
                                            <span
                                                style="font-size:11px; color:#4f46e5; cursor:pointer; font-weight:600;"
                                                @click="viewTaskDetails(task)"
                                            >+ Assign</span>
                                        </a-tooltip>
                                    </div>
                                    <div v-if="task.assignees && task.assignees.length > 0" class="assignees-in-menu">
                                        <div
                                            v-for="a in task.assignees"
                                            :key="a.xid"
                                            class="assignee-row"
                                        >
                                            <a-avatar
                                                :src="a.profile_image_url"
                                                :size="26"
                                                style="flex-shrink:0; border: 1.5px solid #e0e7ff;"
                                            >
                                                <template #icon v-if="!a.profile_image_url">
                                                    <UserOutlined />
                                                </template>
                                            </a-avatar>
                                            <span class="assignee-name">{{ a.name }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="assignees-in-menu assignees-empty">
                                        <UserOutlined style="color:#94a3b8; font-size:13px;" />
                                        <span style="font-size:12px; color:#94a3b8; margin-left:6px;">No assignees yet</span>
                                    </div>

                                    <a-menu-divider />

                                    <a-menu-item key="deadline" @click="openDeadlinePicker(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><ClockCircleOutlined /></span>
                                            <span class="menu-title">Deadline</span>
                                            <span class="menu-shortcut">D</span>
                                        </span>
                                    </a-menu-item>
                                    <a-menu-item key="reminders" @click="openReminders(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><BellOutlined /></span>
                                            <span class="menu-title">Reminders</span>
                                        </span>
                                    </a-menu-item>
                                    
                                    <a-menu-divider />
                                    
                                    <a-menu-item key="move" @click="openMoveToModal(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><FolderOpenOutlined /></span>
                                            <span class="menu-title">Move to...</span>
                                            <span class="menu-shortcut">V</span>
                                        </span>
                                    </a-menu-item>
                                    <a-menu-item key="duplicate" @click="duplicateTask(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><CopyOutlined /></span>
                                            <span class="menu-title">Duplicate</span>
                                        </span>
                                    </a-menu-item>
                                    <a-menu-item key="copy_link" @click="copyLinkToTask(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><LinkOutlined /></span>
                                            <span class="menu-title">Copy link to task</span>
                                            <span class="menu-shortcut">⇧ ⌘ C</span>
                                        </span>
                                    </a-menu-item>
                                    <a-menu-item key="open_new" @click="openInNewWindow(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><ExportOutlined /></span>
                                            <span class="menu-title">Open in new window</span>
                                            <span class="menu-shortcut">⌘ ⇧ N</span>
                                        </span>
                                    </a-menu-item>
                                    
                                    <a-menu-divider />
                                    
                                    <a-menu-item key="add_extension" @click="addExtension(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><ApiOutlined /></span>
                                            <span class="menu-title">Add extension...</span>
                                        </span>
                                    </a-menu-item>
                                    
                                    <a-menu-divider />
                                    
                                    <a-menu-item key="delete" danger @click="confirmDeleteTask(task)">
                                        <span class="menu-item-content">
                                            <span class="menu-icon"><DeleteOutlined /></span>
                                            <span class="menu-title">Delete</span>
                                            <span class="menu-shortcut">⌘ ⌫</span>
                                        </span>
                                    </a-menu-item>
                                </a-menu>
                            </template>
                        </a-dropdown>
                    </div>
                </div>

                <!-- Add Task trigger button at bottom of list -->
                <div style="margin-top: 16px; padding: 8px 4px; cursor: pointer; transition: all 0.2s;" class="add-task-trigger-btn" @click="openCreateTaskDrawer">
                    <span style="font-weight: 400; font-size: 14px; color: #db4c3f; display: inline-flex; align-items: center; gap: 8px;">
                        <PlusOutlined style="font-size: 16px;" />
                        <span style="color: #666;" class="add-task-text">Add task</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- CREATE TASK DRAWER -->
        <CreateTaskDrawer
            v-model:open="createDrawerOpen"
            :initial-project-xid="project ? project.xid : null"
            @saved="fetchInboxTasks"
        />

        <!-- TASK DETAILS MODAL -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchInboxTasks"
        />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import dayjs from 'dayjs';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { 
    PlusOutlined, 
    DragOutlined, 
    InboxOutlined, 
    CheckOutlined, 
    CalendarOutlined, 
    EditOutlined, 
    DeleteOutlined,
    MessageOutlined,
    MoreOutlined,
    HolderOutlined,
    ArrowUpOutlined,
    ArrowDownOutlined,
    ClockCircleOutlined,
    BellOutlined,
    FolderOpenOutlined,
    CopyOutlined,
    LinkOutlined,
    ExportOutlined,
    ApiOutlined,
    FlagFilled,
    FlagOutlined,
    UserOutlined
} from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PlusOutlined,
        DragOutlined,
        InboxOutlined,
        CheckOutlined,
        CalendarOutlined,
        EditOutlined,
        DeleteOutlined,
        MessageOutlined,
        MoreOutlined,
        HolderOutlined,
        CreateTaskDrawer,
        TaskDetailsModal,
        ArrowUpOutlined,
        ArrowDownOutlined,
        ClockCircleOutlined,
        BellOutlined,
        FolderOpenOutlined,
        CopyOutlined,
        LinkOutlined,
        ExportOutlined,
        ApiOutlined,
        FlagFilled,
        FlagOutlined,
        UserOutlined
    },
    setup() {
        const route = useRoute();
        const project = ref(null);
        const sections = ref([]);
        const analytics = ref({});
        
        // Inbox State
        const inboxTasks = ref([]);
        const loadingTasks = ref(false);
        const createDrawerOpen = ref(false);
        const selectedTask = ref(null);
        const detailsVisible = ref(false);

        // Section modal state
        const sectionModalOpen = ref(false);
        const editMode = ref(false);
        const editingSectionId = ref(null);
        const sectionForm = ref({ name: '' });

        // Drag and drop sorting
        const draggedIndex = ref(null);

        const fetchInboxTasks = async () => {
            if (!project.value) return;
            loadingTasks.value = true;
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: { x_project_id: project.value.xid, per_page: 200 }
                });
                inboxTasks.value = response.data.filter(t => t.status !== 'completed');
            } catch (err) {
                console.error(err);
            } finally {
                loadingTasks.value = false;
            }
        };

        const fetchProjectDetails = async () => {
            try {
                const response = await axiosAdmin.get(`/enterprise-tasks/projects/${route.params.id}`);
                project.value = response.project;
                sections.value = response.project.sections;
                analytics.value = response.analytics;
                
                if (project.value.is_inbox || project.value.name === 'Inbox') {
                    fetchInboxTasks();
                }
            } catch (error) {
                console.error(error);
                message.error('Error fetching project details');
            }
        };

        const completeTask = async (task) => {
            try {
                inboxTasks.value = inboxTasks.value.filter(t => t.xid !== task.xid);
                await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, {
                    ...task,
                    status: 'completed'
                });
                message.success('Task marked completed');
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (error) {
                console.error(error);
                message.error('Failed to complete task');
                fetchInboxTasks();
            }
        };

        const confirmDeleteTask = (task) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this task?',
                okText: 'Yes, Delete',
                okType: 'danger',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/tasks/${task.xid}`);
                        message.success('Task deleted successfully');
                        fetchInboxTasks();
                        window.dispatchEvent(new CustomEvent('task-created'));
                    } catch (err) {
                        console.error(err);
                        message.error('Failed to delete task');
                    }
                }
            });
        };

        const openCreateTaskDrawer = () => {
            window.__openQuickAdd?.();
        };

        const viewTaskDetails = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
            fetchInboxTasks();
        };

        const getPriorityColorHex = (priority) => {
            const colors = {
                P1: '#ef4444',
                P2: '#f59e0b',
                P3: '#3b82f6',
                P4: '#10b981'
            };
            return colors[priority] || '#d1d5db';
        };

        const isOverdue = (record) => {
            if (!record.due_date || record.status === 'completed') return false;
            return new Date(record.due_date) < new Date().setHours(0,0,0,0);
        };

        const openSectionModal = () => {
            editMode.value = false;
            sectionForm.value = { name: '' };
            sectionModalOpen.value = true;
        };

        const openRenameModal = (section) => {
            editMode.value = true;
            editingSectionId.value = section.xid;
            sectionForm.value = { name: section.name };
            sectionModalOpen.value = true;
        };

        const handleSectionOk = async () => {
            if (!sectionForm.value.name) {
                message.error('Section Name is required');
                return;
            }

            try {
                if (editMode.value) {
                    await axiosAdmin.put(`/enterprise-tasks/sections/${editingSectionId.value}`, sectionForm.value);
                    message.success('Section renamed successfully');
                } else {
                    await axiosAdmin.post(`/enterprise-tasks/projects/${route.params.id}/sections`, sectionForm.value);
                    message.success('Section created successfully');
                }
                sectionModalOpen.value = false;
                fetchProjectDetails();
            } catch (error) {
                console.error(error);
                message.error('Error saving section');
            }
        };

        const confirmDeleteSection = (section) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this section?',
                content: 'Tasks attached to this section will remain but will no longer belong to any section.',
                okText: 'Yes, Delete',
                okType: 'danger',
                cancelText: 'Cancel',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/sections/${section.xid}`);
                        message.success('Section deleted successfully');
                        fetchProjectDetails();
                    } catch (error) {
                        console.error(error);
                        message.error('Error deleting section');
                    }
                }
            });
        };

        const dragStart = (e, index) => {
            draggedIndex.value = index;
        };

        const drop = async (e, index) => {
            const list = [...sections.value];
            const dragged = list.splice(draggedIndex.value, 1)[0];
            list.splice(index, 0, dragged);
            sections.value = list;

            try {
                await axiosAdmin.post(`/enterprise-tasks/projects/${route.params.id}/sections/reorder`, {
                    sections: list
                });
                message.success('Sections reordered successfully');
            } catch (error) {
                console.error(error);
                message.error('Error reordering sections');
                fetchProjectDetails();
            }
        };

        const handleTaskCreated = () => {
            fetchProjectDetails();
            if (project.value && (project.value.is_inbox || project.value.name === 'Inbox')) {
                fetchInboxTasks();
            }
        };

        const currentDayNumber = ref(dayjs().date());

        const updateTaskField = async (task, field, value) => {
            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${task.xid}`, {
                    ...task,
                    [field]: value
                });
                message.success('Task updated successfully');
                fetchInboxTasks();
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (error) {
                console.error(error);
                message.error('Failed to update task');
            }
        };

        const setTaskDate = (task, preset) => {
            let dateStr = null;
            if (preset === 'today') {
                dateStr = dayjs().format('YYYY-MM-DD');
            } else if (preset === 'tomorrow') {
                dateStr = dayjs().add(1, 'day').format('YYYY-MM-DD');
            } else if (preset === 'next_week') {
                dateStr = dayjs().add(1, 'week').startOf('week').add(1, 'day').format('YYYY-MM-DD');
            } else if (preset === 'weekend') {
                dateStr = dayjs().endOf('week').subtract(1, 'day').format('YYYY-MM-DD');
            }
            updateTaskField(task, 'due_date', dateStr);
        };

        const handleCustomDateChange = (task, dateString) => {
            updateTaskField(task, 'due_date', dateString || null);
        };

        const setTaskPriority = (task, priority) => {
            updateTaskField(task, 'priority', priority);
        };

        const duplicateTask = async (task) => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${task.xid}/duplicate`);
                message.success('Task duplicated');
                fetchInboxTasks();
                window.dispatchEvent(new CustomEvent('task-created'));
            } catch (error) {
                console.error(error);
                message.error('Failed to duplicate task');
            }
        };

        const copyLinkToTask = (task) => {
            const url = `${window.location.origin}/admin/enterprise-tasks/tasks?task=${task.xid}`;
            navigator.clipboard.writeText(url).then(() => {
                message.success('Link copied to clipboard');
            }).catch(() => {
                message.error('Failed to copy link');
            });
        };

        const openInNewWindow = (task) => {
            const url = `${window.location.origin}/admin/enterprise-tasks/tasks?task=${task.xid}`;
            window.open(url, '_blank');
        };

        const addTaskAbove = (task) => {
            openCreateTaskDrawer();
        };

        const addTaskBelow = (task) => {
            openCreateTaskDrawer();
        };

        const openDeadlinePicker = (task) => {
            viewTaskDetails(task);
        };

        const openReminders = (task) => {
            viewTaskDetails(task);
        };

        const openMoveToModal = (task) => {
            viewTaskDetails(task);
        };

        const addExtension = (task) => {
            message.info('Extensions can be configured in Settings -> Extensions');
        };

        onMounted(() => {
            fetchProjectDetails();
            window.addEventListener('task-created', handleTaskCreated);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', handleTaskCreated);
        });

        return {
            project,
            sections,
            analytics,
            sectionModalOpen,
            editMode,
            sectionForm,
            inboxTasks,
            loadingTasks,
            createDrawerOpen,
            selectedTask,
            detailsVisible,
            openSectionModal,
            openRenameModal,
            handleSectionOk,
            confirmDeleteSection,
            dragStart,
            drop,
            openCreateTaskDrawer,
            viewTaskDetails,
            closeTaskDetails,
            completeTask,
            confirmDeleteTask,
            getPriorityColorHex,
            isOverdue,
            fetchInboxTasks,
            currentDayNumber,
            addTaskAbove,
            addTaskBelow,
            setTaskDate,
            handleCustomDateChange,
            setTaskPriority,
            openDeadlinePicker,
            openReminders,
            openMoveToModal,
            duplicateTask,
            copyLinkToTask,
            openInNewWindow,
            addExtension
        };
    }
});
</script>

<style>
.checkbox-circle:hover {
    border-color: #666 !important;
    background: #f3f3f3;
}
.checkbox-circle:hover .check-icon {
    color: #666 !important;
}
.inbox-task-item .drag-holder-icon {
    opacity: 0;
    transition: opacity 0.2s;
}
.inbox-task-item:hover .drag-holder-icon {
    opacity: 1;
}
.inbox-task-item:hover .task-action-buttons {
    display: flex !important;
}
.action-icon-btn {
    transition: all 0.2s;
    padding: 2px;
}
.action-icon-btn:hover {
    color: #1f2937 !important;
    background-color: #f3f4f6;
    border-radius: 4px;
}
.add-task-trigger-btn:hover .add-task-text {
    color: #db4c3f !important;
}
.add-task-trigger-btn:hover {
    background: rgba(219, 76, 63, 0.05);
    border-radius: 4px;
    padding-left: 8px;
}

/* Todoist Context Menu Styles */
.todoist-context-menu {
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
}

.todoist-context-menu .ant-dropdown-menu-item,
.todoist-context-menu .ant-menu-item {
    padding: 8px 14px !important;
    line-height: 1.5 !important;
}

.menu-item-content {
    display: flex;
    align-items: center;
    width: 100%;
}

.menu-icon {
    display: inline-flex;
    align-items: center;
    margin-right: 12px;
    font-size: 15px;
    color: #6b7280;
}

.menu-title {
    font-size: 13.5px;
    color: #1f2937;
    flex: 1;
}

.menu-shortcut {
    font-size: 11px;
    color: #9ca3af;
    margin-left: 12px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, monospace;
}

.menu-section-header {
    padding: 8px 14px 4px 14px;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Assignees in context menu */
.assignees-in-menu {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 6px 14px 10px 14px;
}

.assignees-empty {
    flex-direction: row;
    align-items: center;
    padding: 6px 14px 10px 14px;
}

.assignee-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 6px;
    border-radius: 6px;
    transition: background 0.15s;
    cursor: default;
}

.assignee-row:hover {
    background: #f5f3ff;
}

.assignee-name {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-shortcut {
    font-size: 10px;
    color: #c0c4cc;
    font-weight: normal;
}

/* Date Presets Row */
.date-quick-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 14px 10px 14px;
}

.date-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    background: #ffffff;
    user-select: none;
    font-size: 14px;
}

.date-icon-btn:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    transform: translateY(-1px);
}

.today-btn {
    color: #10b981;
    font-weight: 700;
    border-color: #a7f3d0;
}

.today-btn .date-day-num {
    font-size: 11px;
    color: #10b981;
    border: 1.5px solid #10b981;
    border-radius: 3px;
    padding: 0 2px;
    line-height: 1;
    font-family: sans-serif;
}

.tomorrow-btn {
    border-color: #fde047;
}

.nextweek-btn {
    border-color: #bfdbfe;
}

.weekend-btn {
    border-color: #fed7aa;
}

.custom-picker-btn {
    padding: 0 !important;
    color: #9ca3af;
}

.custom-picker-btn .ant-picker-input {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.custom-picker-btn .ant-picker-input input {
    width: 100%;
    text-align: center;
    cursor: pointer;
    font-weight: bold;
    color: #6b7280;
}

/* Priority Flags Row */
.priority-quick-actions {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 6px 14px 10px 14px;
}

.priority-flag {
    font-size: 20px;
    cursor: pointer;
    transition: all 0.2s;
}

.priority-flag:hover {
    transform: scale(1.2);
}

.flag-p1 {
    color: #ef4444; /* Red */
}

.flag-p2 {
    color: #f97316; /* Orange */
}

.flag-p3 {
    color: #3b82f6; /* Blue */
}

.flag-p4 {
    color: #9ca3af; /* Outline/Gray */
}
</style>
