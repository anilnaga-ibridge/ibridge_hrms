<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="My Day" class="p-0">
                <template #extra>
                    <div class="myday-header-actions">
                        <div class="pomodoro-status" v-if="pomodoroRunning">
                            <div class="pomodoro-pulse" />
                            <span class="pomodoro-countdown">{{ pomodoroDisplay }}</span>
                        </div>
                        <a-button type="primary" @click="openQuickAdd">
                            <PlusOutlined /> Add Task
                        </a-button>
                    </div>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>My Day</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="myday-container">
        <div class="myday-layout">
            <!-- Left: Task Focus Area -->
            <div class="main-focus-area">
                <!-- Daily Greeting -->
                <div class="greeting-card">
                    <div class="greeting-text">
                        <span class="greeting-emoji">{{ getGreetingEmoji() }}</span>
                        <span class="greeting-msg">{{ greetingMessage }}, <strong>{{ user.name }}</strong>!</span>
                    </div>
                    <div class="today-stat-chips">
                        <div class="chip chip-red">
                            <ClockCircleOutlined />
                            <span>{{ overdueTasks.length }} Overdue</span>
                        </div>
                        <div class="chip chip-blue">
                            <CheckSquareOutlined />
                            <span>{{ todayTasks.length }} Today</span>
                        </div>
                        <div class="chip chip-green">
                            <CheckCircleOutlined />
                            <span>{{ completedTodayTasks.length }} Done</span>
                        </div>
                        <div class="chip chip-purple" v-if="pomodoroStats.daily_sessions > 0">
                            <ThunderboltOutlined />
                            <span>{{ pomodoroStats.daily_sessions }} Pomodoros</span>
                        </div>
                    </div>
                </div>

                <!-- My Focus List -->
                <div class="section-header">
                    <h3>Focus List</h3>
                    <span class="section-hint">Your tasks to handle today</span>
                </div>

                <div v-if="todayTasks.length === 0 && overdueTasks.length === 0" class="empty-focus">
                    <TrophyOutlined style="font-size: 48px; color: #c4b5fd;" />
                    <p>All clear! Nothing is demanding your attention right now.</p>
                </div>

                <!-- Overdue Section -->
                <div v-if="overdueTasks.length > 0" class="task-section overdue-section">
                    <div class="task-section-label">
                        <span class="indicator-red"></span>
                        Overdue ({{ overdueTasks.length }})
                    </div>
                    <div v-for="task in overdueTasks" :key="task.xid" class="focus-task-card overdue-card"
                        @click="viewTask(task)">
                        <a-checkbox :checked="false" @click.stop @change="completeTask(task)" />
                        <div class="task-body">
                            <span class="task-title">{{ task.title }}</span>
                            <div class="task-meta">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                    {{ task.project.name }}
                                </a-tag>
                                <span class="overdue-label">{{ task.due_date }}</span>
                                <a-tag :color="getPriorityColor(task.priority)" size="small">{{ task.priority }}</a-tag>
                            </div>
                        </div>
                        <div class="task-quick-actions" @click.stop>
                            <a-button size="small" type="link" @click="startPomodoro(task)">
                                <FieldTimeOutlined /> Focus
                            </a-button>
                        </div>
                    </div>
                </div>

                <!-- Today Tasks -->
                <div v-if="todayTasks.length > 0" class="task-section today-section">
                    <div class="task-section-label">
                        <span class="indicator-blue"></span>
                        Today ({{ todayTasks.length }})
                    </div>
                    <div v-for="task in todayTasks" :key="task.xid" class="focus-task-card" @click="viewTask(task)">
                        <a-checkbox :checked="false" @click.stop @change="completeTask(task)" />
                        <div class="task-body">
                            <span class="task-title">{{ task.title }}</span>
                            <div class="task-meta">
                                <a-tag v-if="task.project" :color="task.project.color || 'blue'" size="small">
                                    {{ task.project.name }}
                                </a-tag>
                                <span v-if="task.due_time" class="time-label">{{ task.due_time.substring(0, 5) }}</span>
                                <a-tag :color="getPriorityColor(task.priority)" size="small">{{ task.priority }}</a-tag>
                            </div>
                        </div>
                        <div class="task-quick-actions" @click.stop>
                            <a-button size="small" type="link" @click="startPomodoro(task)">
                                <FieldTimeOutlined /> Focus
                            </a-button>
                        </div>
                    </div>
                </div>

                <!-- Completed Today -->
                <div v-if="completedTodayTasks.length > 0" class="task-section completed-section">
                    <div class="task-section-label">
                        <span class="indicator-green"></span>
                        Completed Today ({{ completedTodayTasks.length }})
                    </div>
                    <div v-for="task in completedTodayTasks" :key="task.xid" class="focus-task-card completed-card"
                        @click="viewTask(task)">
                        <a-checkbox :checked="true" @click.stop @change="completeTask(task)" />
                        <div class="task-body">
                            <span class="task-title strikethrough">{{ task.title }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Pomodoro Widget + Streak -->
            <div class="sidebar-panel">
                <!-- Pomodoro Timer -->
                <a-card :bordered="false" class="widget-card pomodoro-card">
                    <div class="widget-title">
                        <FieldTimeOutlined class="icon-orange" />
                        <span>Pomodoro Timer</span>
                    </div>
                    <div class="pomodoro-ring">
                        <svg viewBox="0 0 120 120" class="ring-svg">
                            <circle cx="60" cy="60" r="50" class="ring-bg" />
                            <circle cx="60" cy="60" r="50" class="ring-progress"
                                :stroke-dasharray="`${pomodoroProgress} 314`" />
                        </svg>
                        <div class="ring-inner">
                            <div class="ring-time">{{ pomodoroDisplay }}</div>
                            <div class="ring-phase">{{ pomodoroPhase }}</div>
                        </div>
                    </div>
                    <div class="pomodoro-controls">
                        <a-button
                            v-if="!pomodoroRunning"
                            type="primary"
                            block
                            @click="startPomodoroTimer(null)"
                            :style="{ background: '#f97316', borderColor: '#f97316' }"
                        >
                            Start Focus
                        </a-button>
                        <a-button
                            v-else
                            danger
                            block
                            @click="stopPomodoroTimer"
                        >
                            Stop Timer
                        </a-button>
                    </div>
                    <div class="pomodoro-stats-row">
                        <div class="pomo-stat">
                            <span class="pomo-stat-num">{{ pomodoroStats.daily_sessions || 0 }}</span>
                            <span class="pomo-stat-label">Today</span>
                        </div>
                        <div class="pomo-stat">
                            <span class="pomo-stat-num">{{ pomodoroStats.weekly_sessions || 0 }}</span>
                            <span class="pomo-stat-label">This Week</span>
                        </div>
                    </div>
                </a-card>

                <!-- Streak Widget -->
                <a-card :bordered="false" class="widget-card streak-card" v-if="streakData">
                    <div class="widget-title">
                        <span style="font-size: 18px;">🔥</span>
                        <span>Daily Streak</span>
                    </div>
                    <div class="streak-count">{{ streakData.current_streak || streakData.daily_streak || 0 }}</div>
                    <div class="streak-subtext">days in a row</div>
                    <div class="streak-footer" v-if="streakData.last_completed_date">
                        Last activity: {{ streakData.last_completed_date }}
                    </div>
                </a-card>
            </div>
        </div>

        <!-- Task Details Modal -->
        <TaskDetailsModal
            v-if="selectedTask"
            :visible="detailsVisible"
            :task-xid="selectedTask.xid"
            @close="closeTaskDetails"
            @updated="fetchTasks"
        />

        <!-- Create Task Drawer -->
        <CreateTaskDrawer v-model:open="quickAddOpen" :initial-start-date="todayDateStr" @saved="fetchTasks" />
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted, computed } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import TaskDetailsModal from './TaskDetailsModal.vue';
import CreateTaskDrawer from './CreateTaskDrawer.vue';
import {
    PlusOutlined,
    CheckCircleOutlined,
    CheckSquareOutlined,
    ClockCircleOutlined,
    FieldTimeOutlined,
    TrophyOutlined,
    ThunderboltOutlined
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';
import common from '../../../common/composable/common';

export default defineComponent({
    components: {
        AdminPageHeader, TaskDetailsModal, CreateTaskDrawer,
        PlusOutlined, CheckCircleOutlined, CheckSquareOutlined,
        ClockCircleOutlined, FieldTimeOutlined, TrophyOutlined, ThunderboltOutlined
    },
    setup() {
        const { user } = common();

        const overdueTasks = ref([]);
        const todayTasks = ref([]);
        const completedTodayTasks = ref([]);
        const streakData = ref(null);
        const pomodoroStats = ref({ daily_sessions: 0, weekly_sessions: 0 });

        const selectedTask = ref(null);
        const detailsVisible = ref(false);
        const quickAddOpen = ref(false);
        const todayDateStr = ref(dayjs().format('YYYY-MM-DD'));

        // Pomodoro state
        const POMODORO_DURATION = 25 * 60; // 25 minutes in seconds
        const BREAK_DURATION = 5 * 60;
        const pomodoroRunning = ref(false);
        const pomodoroPhase = ref('Focus');
        const pomodoroSecondsLeft = ref(POMODORO_DURATION);
        const currentPomodoroSessionXid = ref(null);
        const currentPomodoroTaskXid = ref(null);
        let pomodoroInterval = null;

        const pomodoroProgress = computed(() => {
            const total = pomodoroPhase.value === 'Focus' ? POMODORO_DURATION : BREAK_DURATION;
            return Math.floor((pomodoroSecondsLeft.value / total) * 314);
        });

        const pomodoroDisplay = computed(() => {
            const mins = Math.floor(pomodoroSecondsLeft.value / 60);
            const secs = pomodoroSecondsLeft.value % 60;
            return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        });

        const greetingMessage = computed(() => {
            const h = dayjs().hour();
            if (h < 12) return 'Good Morning';
            if (h < 17) return 'Good Afternoon';
            return 'Good Evening';
        });

        const getGreetingEmoji = () => {
            const h = dayjs().hour();
            if (h < 12) return '🌅';
            if (h < 17) return '☀️';
            return '🌙';
        };

        const fetchTasks = async () => {
            try {
                const todayStr = dayjs().format('YYYY-MM-DD');
                const userXid = user.value?.xid;

                const [activeRes, completedRes, achievRes, pomRes] = await Promise.all([
                    axiosAdmin.get('/enterprise-tasks/tasks', {
                        params: { 
                            due_date_end: todayStr, 
                            include_overdue: 'true', 
                            parent_only: 'true', 
                            x_assignee_id: userXid,
                            per_page: 100 
                        }
                    }),
                    axiosAdmin.get('/enterprise-tasks/tasks', {
                        params: { 
                            completed_on: todayStr, 
                            parent_only: 'true', 
                            x_assignee_id: userXid,
                            per_page: 50 
                        }
                    }),
                    axiosAdmin.get('/enterprise-tasks/achievements'),
                    axiosAdmin.get('/enterprise-tasks/pomodoro/stats'),
                ]);

                const allActive = activeRes.data || [];
                overdueTasks.value = allActive.filter(t => t.status !== 'completed' && t.due_date && dayjs(t.due_date).isBefore(todayStr, 'day'));
                todayTasks.value = allActive.filter(t => t.status !== 'completed' && (!t.due_date || dayjs(t.due_date).isSame(todayStr, 'day')));
                completedTodayTasks.value = completedRes.data || [];
                streakData.value = achievRes.streak;
                pomodoroStats.value = pomRes || { daily_sessions: 0, weekly_sessions: 0 };
            } catch (err) {
                console.error(err);
                message.error('Failed to load tasks');
            }
        };

        const getPriorityColor = (p) => {
            const c = { P1: 'red', P2: 'orange', P3: 'blue', P4: 'default' };
            return c[p] || 'default';
        };

        const completeTask = async (task) => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${task.xid}/toggle-complete`);
                message.success('Task completed! 🎉');
                fetchTasks();
            } catch (e) {
                message.error('Failed to complete task');
            }
        };

        const viewTask = (task) => {
            selectedTask.value = task;
            detailsVisible.value = true;
        };

        const closeTaskDetails = () => {
            selectedTask.value = null;
            detailsVisible.value = false;
        };

        // Pomodoro operations
        const startPomodoroTimer = async (taskXid) => {
            try {
                const params = {};
                if (taskXid) params.x_task_id = taskXid;
                const res = await axiosAdmin.post('/enterprise-tasks/pomodoro/start', params);
                currentPomodoroSessionXid.value = res.xid;
                currentPomodoroTaskXid.value = taskXid;
            } catch (e) {
                // Continue with local timer even if API fails
            }

            pomodoroRunning.value = true;
            pomodoroPhase.value = 'Focus';
            pomodoroSecondsLeft.value = POMODORO_DURATION;

            pomodoroInterval = setInterval(async () => {
                pomodoroSecondsLeft.value--;
                if (pomodoroSecondsLeft.value <= 0) {
                    if (pomodoroPhase.value === 'Focus') {
                        // Complete session
                        if (currentPomodoroSessionXid.value) {
                            try {
                                await axiosAdmin.post(`/enterprise-tasks/pomodoro/${currentPomodoroSessionXid.value}/complete`);
                                fetchTasks(); // Refresh pomodoro stats
                            } catch (e) {}
                        }
                        message.success('Focus session complete! Take a 5-minute break 🎉', 4);
                        pomodoroPhase.value = 'Break';
                        pomodoroSecondsLeft.value = BREAK_DURATION;
                    } else {
                        // Break finished
                        clearInterval(pomodoroInterval);
                        pomodoroRunning.value = false;
                        pomodoroSecondsLeft.value = POMODORO_DURATION;
                        pomodoroPhase.value = 'Focus';
                        message.success('Break over! Ready for next focus session 💪');
                    }
                }
            }, 1000);
        };

        const startPomodoro = (task) => {
            if (!pomodoroRunning.value) {
                startPomodoroTimer(task.xid);
            }
        };

        const stopPomodoroTimer = () => {
            clearInterval(pomodoroInterval);
            pomodoroRunning.value = false;
            pomodoroPhase.value = 'Focus';
            pomodoroSecondsLeft.value = POMODORO_DURATION;
            currentPomodoroSessionXid.value = null;
        };

        onMounted(() => {
            fetchTasks();
            window.addEventListener('task-created', fetchTasks);
        });
        onUnmounted(() => {
            if (pomodoroInterval) clearInterval(pomodoroInterval);
            window.removeEventListener('task-created', fetchTasks);
        });

        return {
            user, overdueTasks, todayTasks, completedTodayTasks, streakData,
            pomodoroStats, pomodoroRunning, pomodoroPhase, pomodoroDisplay, pomodoroProgress,
            selectedTask, detailsVisible, quickAddOpen, todayDateStr,
            greetingMessage, getGreetingEmoji, getPriorityColor,
            completeTask, viewTask, closeTaskDetails,
            startPomodoro, startPomodoroTimer, stopPomodoroTimer,
            openQuickAdd: () => { window.__openQuickAdd?.(todayDateStr.value); },
            fetchTasks
        };
    }
});
</script>

<style scoped>
.myday-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.myday-header-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.pomodoro-status {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff7ed;
    border: 1px solid #fdba74;
    padding: 4px 12px;
    border-radius: 20px;
}

.pomodoro-pulse {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #f97316;
    animation: pulse 1s infinite;
}

.pomodoro-countdown {
    font-size: 13px;
    font-weight: 700;
    color: #f97316;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.myday-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
    align-items: start;
}

.main-focus-area {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.greeting-card {
    background: white;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 1px 3px 0 rgba(0,0,0,0.05);
}

.greeting-text {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.greeting-emoji {
    font-size: 28px;
}

.greeting-msg {
    font-size: 18px;
    color: #334155;
}

.today-stat-chips {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.chip {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.chip-red { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
.chip-blue { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
.chip-green { background: #f0fdf4; color: #10b981; border: 1px solid #a7f3d0; }
.chip-purple { background: #f5f3ff; color: #8b5cf6; border: 1px solid #ddd6fe; }

.section-header {
    display: flex;
    align-items: baseline;
    gap: 12px;
}

.section-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
    color: #1e293b;
}

.section-hint {
    font-size: 13px;
    color: #94a3b8;
}

.empty-focus {
    text-align: center;
    padding: 60px;
    background: white;
    border-radius: 12px;
    color: #94a3b8;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.empty-focus p {
    margin-top: 12px;
    font-size: 14px;
}

.task-section {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.task-section-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.indicator-red { width: 8px; height: 8px; border-radius: 50%; background: #ef4444; }
.indicator-blue { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; }
.indicator-green { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }

.focus-task-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.focus-task-card:hover {
    border-color: #e2e8f0;
    background: #f8fafc;
}

.overdue-card {
    border-left: 3px solid #ef4444;
    background: #fffbfb;
}

.completed-card {
    opacity: 0.6;
}

.task-body {
    flex-grow: 1;
}

.task-title {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
    display: block;
    margin-bottom: 4px;
}

.strikethrough {
    text-decoration: line-through;
    color: #94a3b8;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.overdue-label {
    font-size: 11px;
    color: #ef4444;
    font-weight: 600;
}

.time-label {
    font-size: 11px;
    color: #64748b;
}

.task-quick-actions {
    flex-shrink: 0;
}

/* Sidebar */
.sidebar-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.widget-card {
    border-radius: 12px !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
}

.widget-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 16px;
}

.icon-orange { color: #f97316; font-size: 18px; }

.pomodoro-ring {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto 16px;
}

.ring-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.ring-bg {
    fill: none;
    stroke: #f1f5f9;
    stroke-width: 8;
}

.ring-progress {
    fill: none;
    stroke: #f97316;
    stroke-width: 8;
    stroke-linecap: round;
    stroke-dasharray: 0 314;
    transition: stroke-dasharray 1s linear;
}

.ring-inner {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.ring-time {
    font-size: 24px;
    font-weight: 800;
    color: #1e293b;
}

.ring-phase {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pomodoro-controls {
    margin-bottom: 12px;
}

.pomodoro-stats-row {
    display: flex;
    justify-content: space-around;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}

.pomo-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pomo-stat-num {
    font-size: 20px;
    font-weight: 800;
    color: #f97316;
}

.pomo-stat-label {
    font-size: 11px;
    color: #94a3b8;
}

.streak-card {
    text-align: center;
}

.streak-count {
    font-size: 48px;
    font-weight: 900;
    color: #f97316;
    line-height: 1;
    margin: 8px 0;
}

.streak-subtext {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 8px;
}

.streak-footer {
    font-size: 11px;
    color: #94a3b8;
}

@media (max-width: 768px) {
    .myday-layout {
        grid-template-columns: 1fr;
    }
}
</style>
