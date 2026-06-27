<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Achievements & Streaks" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Achievements</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="achievements-container">
        <a-spin :spinning="loading">

            <!-- Hero Stats Bar -->
            <div class="hero-stats-bar">
                <div class="hero-stat">
                    <span class="hero-icon">🔥</span>
                    <span class="hero-num">{{ streak.daily_streak || 0 }}</span>
                    <span class="hero-label">Day Streak</span>
                </div>
                <div class="hero-divider"></div>
                <div class="hero-stat">
                    <span class="hero-icon">✅</span>
                    <span class="hero-num">{{ totalCompleted }}</span>
                    <span class="hero-label">Tasks Completed</span>
                </div>
                <div class="hero-divider"></div>
                <div class="hero-stat">
                    <span class="hero-icon">🏅</span>
                    <span class="hero-num">{{ achievements.length }}</span>
                    <span class="hero-label">Badges Earned</span>
                </div>
                <div class="hero-divider"></div>
                <div class="hero-stat">
                    <span class="hero-icon">🎯</span>
                    <span class="hero-num">{{ activeGoals.length }}</span>
                    <span class="hero-label">Active Goals</span>
                </div>
            </div>

            <!-- Streak Calendar + Goal Progress -->
            <div class="main-layout">
                <!-- Left -->
                <div class="left-column">

                    <!-- Streak History -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <TrophyOutlined class="section-icon orange" />
                            <h3>Streak History</h3>
                        </div>
                        <div class="streak-calendar">
                            <div
                                v-for="(day, i) in streakCalendar"
                                :key="i"
                                :class="['streak-day', day.active ? 'streak-day-active' : '', day.today ? 'streak-day-today' : '']"
                                :title="day.label"
                            />
                        </div>
                        <div class="streak-legend">
                            <div class="legend-item"><div class="legend-box active"></div><span>Activity</span></div>
                            <div class="legend-item"><div class="legend-box"></div><span>No activity</span></div>
                            <div class="legend-item"><div class="legend-box today"></div><span>Today</span></div>
                        </div>
                        <div class="streak-row" v-if="streak.last_completed_date">
                            <span>Last activity:</span>
                            <strong>{{ formatDate(streak.last_completed_date) }}</strong>
                        </div>
                    </div>

                    <!-- Goals -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <AimOutlined class="section-icon purple" />
                            <h3>Active Goals</h3>
                            <a-button type="link" size="small" @click="showGoalModal = true" class="ml-auto">+ New Goal</a-button>
                        </div>

                        <div v-if="activeGoals.length === 0" class="empty-state-small">
                            No active goals. Set your first goal!
                        </div>

                        <div v-for="goal in activeGoals" :key="goal.xid" class="goal-item">
                            <div class="goal-top">
                                <span class="goal-name">{{ goal.name }}</span>
                                <span :class="['goal-status-badge', `status-${goal.status}`]">{{ goal.status }}</span>
                            </div>
                            <a-progress
                                :percent="Math.round((goal.current_progress / goal.target) * 100)"
                                :stroke-color="getGoalColor(goal)"
                                size="small"
                            />
                            <div class="goal-bottom">
                                <span>{{ goal.current_progress }} / {{ goal.target }}</span>
                                <span>{{ formatDate(goal.end_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Badge Wall -->
                <div class="right-column">
                    <div class="section-card badges-card">
                        <div class="section-card-header">
                            <StarOutlined class="section-icon yellow" />
                            <h3>Badge Collection</h3>
                        </div>

                        <div v-if="achievements.length === 0" class="empty-badges">
                            <div class="empty-badge-icon">🎖️</div>
                            <p>Complete tasks and build streaks to earn your first badge!</p>
                        </div>

                        <div class="badge-grid">
                            <div
                                v-for="item in achievements"
                                :key="item.xid"
                                class="badge-card"
                            >
                                <div class="badge-icon">{{ item.badge?.icon || '🏅' }}</div>
                                <div class="badge-name">{{ item.badge?.name || 'Achievement' }}</div>
                                <div class="badge-desc">{{ item.badge?.description || '' }}</div>
                                <div class="badge-date">{{ formatDate(item.unlocked_at) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a-spin>

        <!-- New Goal Modal -->
        <a-modal v-model:open="showGoalModal" title="Create New Goal" @ok="createGoal" :confirm-loading="saving">
            <a-form :model="goalForm" layout="vertical">
                <a-form-item label="Goal Name" required>
                    <a-input v-model:value="goalForm.name" placeholder="Complete 50 tasks this month" />
                </a-form-item>
                <a-form-item label="Goal Type" required>
                    <a-select v-model:value="goalForm.goal_type">
                        <a-select-option value="tasks_completed">Tasks Completed</a-select-option>
                        <a-select-option value="bugs_closed">Bugs Closed</a-select-option>
                        <a-select-option value="time_logged">Hours Logged</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Target" required>
                    <a-input-number v-model:value="goalForm.target" :min="1" style="width: 100%;" />
                </a-form-item>
                <a-form-item label="Date Range" required>
                    <a-range-picker v-model:value="goalForm.dateRange" style="width: 100%;" />
                </a-form-item>
            </a-form>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { TrophyOutlined, StarOutlined, AimOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader, TrophyOutlined, StarOutlined, AimOutlined },
    setup() {
        const loading = ref(false);
        const saving = ref(false);
        const showGoalModal = ref(false);

        const streak = ref({ daily_streak: 0, weekly_streak: 0, last_completed_date: null });
        const achievements = ref([]);
        const goals = ref([]);
        const totalCompleted = ref(0);

        const goalForm = ref({
            name: '',
            goal_type: 'tasks_completed',
            target: 10,
            dateRange: [dayjs(), dayjs().endOf('month')]
        });

        const activeGoals = computed(() => goals.value.filter(g => g.status === 'active'));

        // Build a 28-day streak calendar
        const streakCalendar = computed(() => {
            const days = [];
            const lastDate = streak.value.last_completed_date ? dayjs(streak.value.last_completed_date) : null;
            const streakLen = streak.value.daily_streak || 0;

            for (let i = 27; i >= 0; i--) {
                const d = dayjs().subtract(i, 'day');
                const isActive = lastDate && streakLen > 0 && (lastDate.diff(d, 'day') >= 0) && (lastDate.diff(d, 'day') < streakLen);
                days.push({
                    date: d,
                    active: isActive,
                    today: d.isSame(dayjs(), 'day'),
                    label: d.format('MMM D')
                });
            }
            return days;
        });

        const fetch = async () => {
            loading.value = true;
            try {
                const [achRes, goalsRes] = await Promise.all([
                    axiosAdmin.get('/enterprise-tasks/achievements'),
                    axiosAdmin.get('/enterprise-tasks/goals'),
                ]);

                streak.value = achRes.streak || streak.value;
                achievements.value = achRes.achievements || [];
                goals.value = goalsRes || [];
                totalCompleted.value = achievements.value.length > 0 ? 0 : 0; // Will be calculated server-side in future
            } catch (e) {
                message.error('Failed to load achievements');
            } finally {
                loading.value = false;
            }
        };

        const createGoal = async () => {
            if (!goalForm.value.name || !goalForm.value.dateRange) return;

            saving.value = true;
            try {
                await axiosAdmin.post('/enterprise-tasks/goals', {
                    name: goalForm.value.name,
                    goal_type: goalForm.value.goal_type,
                    target: goalForm.value.target,
                    start_date: dayjs(goalForm.value.dateRange[0]).format('YYYY-MM-DD'),
                    end_date: dayjs(goalForm.value.dateRange[1]).format('YYYY-MM-DD'),
                });
                message.success('Goal created!');
                showGoalModal.value = false;
                goalForm.value = { name: '', goal_type: 'tasks_completed', target: 10, dateRange: [dayjs(), dayjs().endOf('month')] };
                fetch();
            } catch (e) {
                message.error('Failed to create goal');
            } finally {
                saving.value = false;
            }
        };

        const formatDate = (d) => d ? dayjs(d).format('MMM D, YYYY') : '-';

        const getGoalColor = (goal) => {
            const pct = Math.round((goal.current_progress / goal.target) * 100);
            if (pct >= 80) return '#10b981';
            if (pct >= 50) return '#f97316';
            return '#8b5cf6';
        };

        onMounted(() => {
            fetch();
            window.addEventListener('task-created', fetch);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetch);
        });

        return {
            loading, saving, showGoalModal, streak, achievements, goals,
            totalCompleted, activeGoals, streakCalendar, goalForm,
            createGoal, formatDate, getGoalColor
        };
    }
});
</script>

<style scoped>
.achievements-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

/* Hero Stats Bar */
.hero-stats-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.hero-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.hero-icon { font-size: 28px; }

.hero-num {
    font-size: 32px;
    font-weight: 900;
    color: #1e293b;
    line-height: 1;
}

.hero-label {
    font-size: 12px;
    color: #64748b;
    text-align: center;
}

.hero-divider {
    width: 1px;
    height: 60px;
    background: #f1f5f9;
}

/* Layout */
.main-layout {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
    align-items: start;
}

.left-column, .right-column {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.section-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
}

.section-card-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}

.section-icon {
    font-size: 18px;
}

.section-icon.orange { color: #f97316; }
.section-icon.purple { color: #8b5cf6; }
.section-icon.yellow { color: #f59e0b; }

.ml-auto { margin-left: auto; }

/* Streak Calendar */
.streak-calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-bottom: 12px;
}

.streak-day {
    aspect-ratio: 1;
    border-radius: 3px;
    background: #f1f5f9;
    cursor: default;
    transition: background 0.2s;
}

.streak-day-active {
    background: #f97316;
}

.streak-day-today {
    outline: 2px solid #3b82f6;
    outline-offset: 1px;
}

.streak-legend {
    display: flex;
    gap: 16px;
    font-size: 11px;
    color: #64748b;
    margin-bottom: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.legend-box {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    background: #f1f5f9;
}

.legend-box.active { background: #f97316; }
.legend-box.today { background: #f1f5f9; outline: 2px solid #3b82f6; }

.streak-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #64748b;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}

/* Goals */
.empty-state-small {
    font-size: 13px;
    color: #94a3b8;
    text-align: center;
    padding: 16px;
}

.goal-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    border: 1px solid #f1f5f9;
}

.goal-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.goal-name {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.goal-status-badge {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 2px 8px;
    border-radius: 10px;
}

.status-active { background: #eff6ff; color: #3b82f6; }
.status-completed { background: #f0fdf4; color: #10b981; }
.status-failed { background: #fef2f2; color: #ef4444; }

.goal-bottom {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #64748b;
    margin-top: 4px;
}

/* Badge Grid */
.badges-card {
    height: 100%;
}

.empty-badges {
    text-align: center;
    padding: 40px 20px;
}

.empty-badge-icon { font-size: 48px; }
.empty-badges p { font-size: 13px; color: #94a3b8; margin-top: 8px; }

.badge-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.badge-card {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    text-align: center;
    transition: transform 0.2s;
}

.badge-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.badge-icon { font-size: 32px; margin-bottom: 6px; }
.badge-name { font-size: 12px; font-weight: 700; color: #1e293b; }
.badge-desc { font-size: 11px; color: #64748b; margin-top: 3px; }
.badge-date { font-size: 10px; color: #94a3b8; margin-top: 4px; }

@media (max-width: 900px) {
    .main-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .hero-stats-bar {
        flex-wrap: wrap;
        gap: 20px;
    }
    .hero-divider { display: none; }
}
</style>
