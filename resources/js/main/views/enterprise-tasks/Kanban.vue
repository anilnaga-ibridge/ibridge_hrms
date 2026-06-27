<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Kanban Board" class="p-0">
                <template #extra>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <span style="font-weight: 500;">Select Project:</span>
                        <a-select v-model:value="selectedProjectXid" style="width: 200px" placeholder="Choose Project" @change="loadBoard">
                            <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">
                                {{ proj.name }}
                            </a-select-option>
                        </a-select>
                        <router-link v-if="selectedProjectXid" :to="{ name: 'admin.enterprise_tasks.project_details', params: { id: selectedProjectXid } }">
                            <a-button type="link" size="small" style="padding: 0;"><SettingOutlined /> Configure Sections</a-button>
                        </router-link>
                    </div>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Kanban Board</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div style="margin: 20px 16px; overflow: hidden; height: calc(100vh - 210px);">
        <!-- Filters panel -->
        <div v-if="selectedProjectXid" style="background: #ffffff; padding: 12px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
            <div style="display: flex; gap: 12px;">
                <a-input-search v-model:value="searchQuery" placeholder="Search board..." style="width: 220px;" size="small" @search="filterBoard" @change="filterBoard" />
                <a-select v-model:value="filterPriority" placeholder="Priority" style="width: 120px;" size="small" allowClear @change="filterBoard">
                    <a-select-option value="P1">P1 Critical</a-select-option>
                    <a-select-option value="P2">P2 High</a-select-option>
                    <a-select-option value="P3">P3 Medium</a-select-option>
                    <a-select-option value="P4">P4 Low</a-select-option>
                </a-select>
                <a-select v-model:value="filterAssignee" placeholder="Assignee" style="width: 150px;" size="small" allowClear @change="filterBoard">
                    <a-select-option v-for="u in teamMembers" :key="u.xid" :value="u.xid">{{ u.name }}</a-select-option>
                </a-select>
            </div>
            
            <div>
                <span style="font-size: 12px; color: #6b7280; margin-right: 8px;">Group Board By:</span>
                <a-radio-group v-model:value="groupBy" size="small" button-style="solid" @change="loadBoard">
                    <a-radio-button value="section">Sections</a-radio-button>
                    <a-radio-button value="priority">Priority</a-radio-button>
                </a-radio-group>
            </div>
        </div>

        <!-- Kanban Columns Container -->
        <div v-if="selectedProjectXid" style="display: flex; gap: 16px; overflow-x: auto; height: 100%; padding-bottom: 20px;">
            <div
                v-for="col in columns"
                :key="col.xid"
                style="flex: 0 0 280px; width: 280px; background: #f3f4f6; border-radius: 12px; display: flex; flex-direction: column; max-height: 100%; border-top: 4px solid #3b82f6;"
                :style="{ borderTopColor: col.color ?? '#3b82f6' }"
                @dragover.prevent
                @drop="onDrop($event, col)"
            >
                <!-- Column Header -->
                <div style="padding: 12px; display: flex; justify-content: space-between; align-items: center; background: #e5e7eb; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <span style="font-weight: bold; font-size: 14px; color: #374151;">{{ col.name }}</span>
                    <a-tag color="blue" size="small">{{ col.tasks.length }}</a-tag>
                </div>

                <!-- Cards List -->
                <div style="flex: 1; overflow-y: auto; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div
                        v-for="task in col.tasks"
                        :key="task.xid"
                        draggable="true"
                        @dragstart="onDragStart($event, task)"
                        @click="viewTaskDetails(task)"
                        style="background: #ffffff; padding: 12px; border-radius: 8px; cursor: grab; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1); border-left: 4px solid #d1d5db;"
                        :style="{ borderLeftColor: getPriorityColorHex(task.priority) }"
                    >
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #9ca3af; margin-bottom: 4px;">
                            <code>{{ task.task_number }}</code>
                            <span>{{ task.due_date ? task.due_date : '' }}</span>
                        </div>
                        <div style="font-weight: 500; font-size: 13px; color: #1f2937; margin-bottom: 8px;">
                            {{ task.title }}
                        </div>

                        <!-- Labels -->
                        <div v-if="task.labels && task.labels.length > 0" style="margin-bottom: 8px; display: flex; gap: 4px; flex-wrap: wrap;">
                            <a-tag v-for="l in task.labels" :key="l.xid" :color="l.color" size="small" style="font-size: 9px; padding: 0 4px;">
                                {{ l.name }}
                            </a-tag>
                        </div>

                        <!-- Assignees & Info footer -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; border-top: 1px solid #f3f4f6; padding-top: 8px;">
                            <div style="display: flex; -webkit-box-align: center; align-items: center;">
                                <a-tooltip v-for="a in task.assignees" :key="a.xid" :title="a.name">
                                    <a-avatar size="small" :src="a.profile_image_url" style="border: 1px solid #fff; margin-right: -4px;" />
                                </a-tooltip>
                            </div>
                            <div style="font-size: 11px; color: #9ca3af;">
                                <span v-if="task.completion_percentage > 0">{{ task.completion_percentage }}% complete</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Task Button at bottom of column -->
                <div style="padding: 8px 12px; border-top: 1px solid #e5e7eb; background: #f3f4f6; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <a-button type="link" size="small" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 4px; color: #4b5563; font-weight: 500;" @click="openCreateTaskDrawer(col)">
                        <PlusOutlined /> Add Task
                    </a-button>
                </div>
            </div>
        </div>

        <!-- Project Selector Empty State -->
        <div v-else style="text-align: center; padding: 100px 0;">
            <a-empty description="Please select a Project from the top-right selector to load the Kanban Board." />
        </div>

        <!-- TASK DETAILS MODAL -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="loadBoard"
        />

        <!-- CREATE TASK DRAWER -->
        <CreateTaskDrawer
            v-model:open="createDrawerOpen"
            :initial-project-xid="selectedProjectXid"
            :initial-section-xid="prefillSectionXid"
            :initial-priority="prefillPriority"
            @saved="loadBoard"
        />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import { SettingOutlined, PlusOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';


export default defineComponent({
    components: {
        AdminPageHeader,
        TaskDetailsModal,
        CreateTaskDrawer,
        SettingOutlined,
        PlusOutlined
    },
    setup() {
        const projects = ref([]);
        const selectedProjectXid = ref(undefined);
        const rawTasks = ref([]);
        const columns = ref([]);
        const teamMembers = ref([]);

        // Filters
        const searchQuery = ref('');
        const filterPriority = ref(undefined);
        const filterAssignee = ref(undefined);
        const groupBy = ref('section');

        // Detail modal state
        const selectedTask = ref(null);
        const detailsVisible = ref(false);

        // Drawer states
        const createDrawerOpen = ref(false);
        const prefillSectionXid = ref(null);
        const prefillPriority = ref(null);

        const openCreateTaskDrawer = (column) => {
            prefillSectionXid.value = null;
            prefillPriority.value = null;

            if (column.type === 'section') {
                prefillSectionXid.value = column.xid;
            } else if (column.type === 'priority') {
                prefillPriority.value = column.xid;
            }

            createDrawerOpen.value = true;
        };

        // Drag and drop state
        const draggedTask = ref(null);

        const fetchProjects = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const loadBoard = async () => {
            if (!selectedProjectXid.value) return;
            try {
                // Fetch project sections & tasks
                const [projRes, tasksRes] = await Promise.all([
                    axiosAdmin.get(`/enterprise-tasks/projects/${selectedProjectXid.value}`),
                    axiosAdmin.get('/enterprise-tasks/tasks', {
                        params: { x_project_id: selectedProjectXid.value, per_page: 200 }
                    })
                ]);

                rawTasks.value = tasksRes.data;
                
                // Set Team Members list for filtering
                teamMembers.value = projRes.project.members.map(m => m.user).filter(Boolean);

                if (groupBy.value === 'section') {
                    // Populate columns from project sections
                    columns.value = projRes.project.sections.map((sec, index) => {
                        const secTasks = rawTasks.value.filter(t => t.x_section_id === sec.xid);
                        return {
                            xid: sec.xid,
                            name: sec.name,
                            type: 'section',
                            color: getSectionColor(index),
                            tasks: secTasks
                        };
                    });
                } else if (groupBy.value === 'priority') {
                    // Populate columns by Priority
                    const priorities = [
                        { xid: 'P1', name: 'P1 Critical', color: '#ef4444' },
                        { xid: 'P2', name: 'P2 High', color: '#f59e0b' },
                        { xid: 'P3', name: 'P3 Medium', color: '#3b82f6' },
                        { xid: 'P4', name: 'P4 Low', color: '#10b981' }
                    ];
                    columns.value = priorities.map(p => {
                        return {
                            xid: p.xid,
                            name: p.name,
                            type: 'priority',
                            color: p.color,
                            tasks: rawTasks.value.filter(t => t.priority === p.xid)
                        };
                    });
                }

                filterBoard();
            } catch (error) {
                console.error(error);
                message.error('Error loading board data');
            }
        };

        const filterBoard = () => {
            columns.value.forEach(col => {
                let filtered = rawTasks.value.filter(t => {
                    if (groupBy.value === 'section') {
                        return t.x_section_id === col.xid;
                    } else {
                        return t.priority === col.xid;
                    }
                });

                // Apply text search
                if (searchQuery.value) {
                    const q = searchQuery.value.toLowerCase();
                    filtered = filtered.filter(t => t.title.toLowerCase().includes(q) || t.task_number.toLowerCase().includes(q));
                }

                // Apply priority filter
                if (filterPriority.value) {
                    filtered = filtered.filter(t => t.priority === filterPriority.value);
                }

                // Apply assignee filter
                if (filterAssignee.value) {
                    filtered = filtered.filter(t => t.assignees.some(a => a.xid === filterAssignee.value));
                }

                col.tasks = filtered;
            });
        };

        // Drag & Drop
        const onDragStart = (e, task) => {
            draggedTask.value = task;
        };

        const onDrop = async (e, column) => {
            if (!draggedTask.value) return;

            // Prevent drop on same status/priority
            if (column.type === 'section' && draggedTask.value.x_section_id === column.xid) return;
            if (column.type === 'priority' && draggedTask.value.priority === column.xid) return;

            // Optimistic update
            const oldSectionXid = draggedTask.value.x_section_id;
            const oldPriority = draggedTask.value.priority;

            if (column.type === 'section') {
                draggedTask.value.x_section_id = column.xid;
            } else {
                draggedTask.value.priority = column.xid;
            }
            filterBoard();

            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${draggedTask.value.xid}`, {
                    ...draggedTask.value,
                    x_section_id: column.type === 'section' ? column.xid : draggedTask.value.x_section_id,
                    priority: column.type === 'priority' ? column.xid : draggedTask.value.priority
                });
                message.success('Board updated successfully');
            } catch (error) {
                console.error(error);
                message.error('Error updating task status');
                // Revert
                draggedTask.value.x_section_id = oldSectionXid;
                draggedTask.value.priority = oldPriority;
                loadBoard();
            } finally {
                draggedTask.value = null;
            }
        };

        // Details Modal
        const viewTaskDetails = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
        };

        // Colors helpers
        const getSectionColor = (index) => {
            const colors = ['#6b7280', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#10b981'];
            return colors[index % colors.length];
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

        onMounted(() => {
            fetchProjects();
            window.addEventListener('task-created', loadBoard);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', loadBoard);
        });

        return {
            projects,
            selectedProjectXid,
            columns,
            teamMembers,
            searchQuery,
            filterPriority,
            filterAssignee,
            groupBy,
            selectedTask,
            detailsVisible,
            loadBoard,
            filterBoard,
            onDragStart,
            onDrop,
            viewTaskDetails,
            closeTaskDetails,
            getPriorityColorHex,
            openCreateTaskDrawer,
            createDrawerOpen,
            prefillSectionXid,
            prefillPriority
        };
    }
});
</script>
