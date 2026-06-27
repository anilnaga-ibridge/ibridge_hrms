<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Gantt Timeline Chart" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Gantt Chart</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="gantt-chart-container">
        <!-- Control Bar -->
        <div class="control-bar mb-4">
            <div class="flex items-center gap-4">
                <div style="width: 260px">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Project</label>
                    <a-select
                        v-model:value="selectedProjectId"
                        placeholder="Select a Project"
                        style="width: 100%"
                        @change="loadProjectGanttData"
                    >
                        <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">
                            {{ proj.name }}
                        </a-select-option>
                    </a-select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Zoom Level</label>
                    <a-radio-group v-model:value="zoomLevel" button-style="solid">
                        <a-radio-button value="day">Days</a-radio-button>
                        <a-radio-button value="week">Weeks</a-radio-button>
                        <a-radio-button value="month">Months</a-radio-button>
                    </a-radio-group>
                </div>

                <div class="flex items-center gap-2 self-end">
                    <a-button type="primary" :disabled="!selectedProjectId" @click="highlightCriticalPath" class="critical-btn">
                        Toggle Critical Path
                    </a-button>
                </div>
            </div>
        </div>

        <a-spin :spinning="loading">
            <div class="gantt-scroll-wrapper" v-if="selectedProjectId && tasks.length">
                <!-- SVG Timeline Graphic -->
                <svg :width="ganttWidth" :height="ganttHeight" class="gantt-svg">
                    <defs>
                        <!-- Grid pattern -->
                        <pattern id="grid" :width="colWidth" :height="ganttHeight" patternUnits="userSpaceOnUse">
                            <line x1="0" y1="0" :x2="0" :y2="ganttHeight" stroke="#f1f5f9" />
                        </pattern>
                        <!-- Arrow marker for dependencies -->
                        <marker id="arrow" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                            <path d="M 0 0 L 10 5 L 0 10 z" fill="#94a3b8" />
                        </marker>
                        <!-- Critical path arrow -->
                        <marker id="arrow-critical" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                            <path d="M 0 0 L 10 5 L 0 10 z" fill="#ef4444" />
                        </marker>
                    </defs>

                    <!-- Background Grid -->
                    <rect width="100%" height="100%" fill="url(#grid)" />

                    <!-- Header Headers -->
                    <g class="gantt-header">
                        <rect x="0" y="0" :width="ganttWidth" height="50" fill="#f8fafc" stroke="#e2e8f0" />
                        <text
                            v-for="(header, i) in timeHeaders"
                            :key="i"
                            :x="i * colWidth + (colWidth / 2)"
                            y="30"
                            text-anchor="middle"
                            class="header-text"
                        >
                            {{ header }}
                        </text>
                    </g>

                    <!-- Connections / Dependencies -->
                    <g class="gantt-dependencies">
                        <path
                            v-for="(path, i) in dependencyPaths"
                            :key="i"
                            :d="path.d"
                            fill="none"
                            :stroke="path.isCritical ? '#ef4444' : '#94a3b8'"
                            :stroke-width="path.isCritical ? 2.5 : 1.5"
                            :marker-end="path.isCritical ? 'url(#arrow-critical)' : 'url(#arrow)'"
                        />
                    </g>

                    <!-- Task Bars -->
                    <g v-for="(task, i) in positionedTasks" :key="task.xid" class="gantt-row">
                        <!-- Horizontal Lane highlight on hover -->
                        <rect
                            x="0"
                            :y="50 + (i * rowHeight)"
                            :width="ganttWidth"
                            :height="rowHeight"
                            fill="transparent"
                            class="lane-hover"
                        />

                        <!-- Task Bar itself -->
                        <rect
                            :x="task.x"
                            :y="50 + (i * rowHeight) + 12"
                            :width="Math.max(task.width, 30)"
                            height="24"
                            rx="6"
                            :fill="task.isMilestone ? '#f59e0b' : (task.isOnCriticalPath && showCriticalPath ? '#ef4444' : '#3b82f6')"
                            class="task-bar cursor-pointer"
                            @click="viewTaskDetails(task)"
                        />

                        <!-- Milestone Diamond -->
                        <polygon
                            v-if="task.isMilestone"
                            :points="getMilestonePoints(task.x, 50 + (i * rowHeight) + 24)"
                            fill="#d97706"
                        />

                        <!-- Task Title text next to the bar -->
                        <text
                            :x="task.x + Math.max(task.width, 30) + 10"
                            :y="50 + (i * rowHeight) + 28"
                            class="task-text"
                        >
                            {{ task.title }}
                        </text>
                    </g>
                </svg>
            </div>
            
            <div class="empty-state" v-else-if="selectedProjectId">
                <a-empty description="No tasks scheduled in this project yet." />
            </div>
            <div class="empty-state" v-else>
                <a-empty description="Please select a project to render its Gantt timeline." />
            </div>
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader },
    setup() {
        const loading = ref(false);
        const projects = ref([]);
        const selectedProjectId = ref(null);
        const zoomLevel = ref('week'); // day, week, month
        const tasks = ref([]);
        const dependencies = ref([]);
        const criticalTasksXids = ref([]);
        const showCriticalPath = ref(false);

        // SVG Constants
        const rowHeight = 48;
        const colWidth = computed(() => {
            if (zoomLevel.value === 'day') return 80;
            if (zoomLevel.value === 'week') return 120;
            return 180;
        });

        // Timeline date window range calculation
        const timelineStart = computed(() => {
            if (!tasks.value.length) return dayjs().startOf('month');
            let min = dayjs();
            tasks.value.forEach(t => {
                const date = dayjs(t.due_date || t.created_at);
                if (date.isBefore(min)) min = date;
            });
            return min.subtract(1, 'month').startOf('month');
        });

        const timelineEnd = computed(() => {
            if (!tasks.value.length) return dayjs().endOf('month');
            let max = dayjs();
            tasks.value.forEach(t => {
                const date = dayjs(t.due_date || t.created_at);
                if (date.isAfter(max)) max = date;
            });
            return max.add(2, 'month').endOf('month');
        });

        // Total grid columns
        const timeHeaders = computed(() => {
            const headers = [];
            let current = timelineStart.value;
            const end = timelineEnd.value;

            while (current.isBefore(end)) {
                if (zoomLevel.value === 'day') {
                    headers.push(current.format('DD MMM'));
                    current = current.add(1, 'day');
                } else if (zoomLevel.value === 'week') {
                    headers.push('Wk ' + current.format('WW'));
                    current = current.add(1, 'week');
                } else {
                    headers.push(current.format('MMMM YYYY'));
                    current = current.add(1, 'month');
                }
            }
            return headers;
        });

        const ganttWidth = computed(() => {
            return timeHeaders.value.length * colWidth.value;
        });

        const ganttHeight = computed(() => {
            return 60 + (tasks.value.length * rowHeight);
        });

        // Position tasks inside SVG grid
        const positionedTasks = computed(() => {
            const start = timelineStart.value;
            return tasks.value.map(t => {
                const due = dayjs(t.due_date || t.created_at);
                
                // Position X
                let diff = 0;
                let width = 100;

                if (zoomLevel.value === 'day') {
                    diff = due.diff(start, 'day');
                    width = 80;
                } else if (zoomLevel.value === 'week') {
                    diff = due.diff(start, 'week', true);
                    width = 120;
                } else {
                    diff = due.diff(start, 'month', true);
                    width = 150;
                }

                const x = Math.max(diff * colWidth.value, 20);
                const isMilestone = t.priority === 'P1';

                return {
                    ...t,
                    x,
                    width: isMilestone ? 24 : width,
                    isMilestone,
                    isOnCriticalPath: criticalTasksXids.value.includes(t.xid)
                };
            });
        });

        // Calculate dependency SVG paths
        const dependencyPaths = computed(() => {
            const paths = [];
            if (!tasks.value.length) return [];

            // Helper to get task by XID
            const taskMap = {};
            positionedTasks.value.forEach((pt, index) => {
                taskMap[pt.xid] = { pt, y: 50 + (index * rowHeight) + 24 };
            });

            dependencies.value.forEach(dep => {
                const fromTask = taskMap[dep.x_depends_on_task_id || dep.depends_on_task_id];
                const toTask = taskMap[dep.x_task_id || dep.task_id];

                if (fromTask && toTask) {
                    const fromX = fromTask.pt.x + Math.max(fromTask.pt.width, 30);
                    const fromY = fromTask.y;
                    const toX = toTask.pt.x;
                    const toY = toTask.y;

                    // Draw orthoganal path: Right -> Down -> Right
                    const midX = fromX + (toX - fromX) / 2;
                    const d = `M ${fromX} ${fromY} L ${midX} ${fromY} L ${midX} ${toY} L ${toX} ${toY}`;

                    paths.push({
                        d,
                        isCritical: fromTask.pt.isOnCriticalPath && toTask.pt.isOnCriticalPath
                    });
                }
            });

            return paths;
        });

        const fetchProjects = async () => {
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = res;
            } catch (e) {
                message.error('Failed to load projects');
            }
        };

        const loadProjectGanttData = async () => {
            if (!selectedProjectId.value) return;
            loading.value = true;
            try {
                // Load tasks
                const tasksRes = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: { project_xid: selectedProjectId.value }
                });
                tasks.value = tasksRes.data || tasksRes;

                // Load dependencies
                dependencies.value = [];
                for (let t of tasks.value) {
                    const depRes = await axiosAdmin.get(`/enterprise-tasks/tasks/${t.xid}/dependencies`);
                    dependencies.value.push(...depRes);
                }

                // Load Critical Path
                const criticalRes = await axiosAdmin.get(`/enterprise-tasks/projects/${selectedProjectId.value}/critical-path`);
                criticalTasksXids.value = criticalRes.map(t => t.xid);
            } catch (e) {
                message.error('Failed to render Gantt timeline data');
            } finally {
                loading.value = false;
            }
        };

        const highlightCriticalPath = () => {
            showCriticalPath.value = !showCriticalPath.value;
            if (showCriticalPath.value) {
                message.info('Highlighting critical dependency paths');
            }
        };

        const getMilestonePoints = (x, y) => {
            return `${x},${y} ${x+12},${y-12} ${x+24},${y} ${x+12},${y+12}`;
        };

        const viewTaskDetails = (task) => {
            message.info(`Task Details: ${task.title} (Due: ${task.due_date || 'No due date'})`);
        };

        const handleTaskCreated = () => {
            fetchProjects();
            loadProjectGanttData();
        };

        onMounted(() => {
            fetchProjects();
            window.addEventListener('task-created', handleTaskCreated);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', handleTaskCreated);
        });

        return {
            loading,
            projects,
            selectedProjectId,
            zoomLevel,
            tasks,
            timeHeaders,
            ganttWidth,
            ganttHeight,
            colWidth,
            rowHeight,
            positionedTasks,
            dependencyPaths,
            showCriticalPath,
            loadProjectGanttData,
            highlightCriticalPath,
            getMilestonePoints,
            viewTaskDetails
        };
    }
});
</script>

<style scoped>
.gantt-chart-container {
    padding: 24px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.control-bar {
    background: white;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}

.critical-btn {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    font-weight: 600;
}

.critical-btn:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.gantt-scroll-wrapper {
    background: white;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow-x: auto;
    overflow-y: hidden;
    padding: 12px;
}

.gantt-svg {
    background: #ffffff;
    user-select: none;
}

.header-text {
    font-size: 11px;
    font-weight: 700;
    fill: #64748b;
}

.task-bar {
    transition: filter 0.2s, stroke-width 0.2s;
}

.task-bar:hover {
    filter: brightness(0.9);
}

.task-text {
    font-size: 11px;
    font-weight: 600;
    fill: #334155;
    alignment-baseline: middle;
}

.lane-hover:hover {
    fill: #f8fafc;
}

.empty-state {
    background: white;
    border-radius: 12px;
    padding: 60px 40px;
    text-align: center;
    border: 1px solid #f1f5f9;
}
</style>
