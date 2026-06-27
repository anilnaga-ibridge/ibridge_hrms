<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="AI Task Assistant" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>AI Task Assistant</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="ai-assistant-container">
        <div class="ai-hero-card">
            <div class="ai-hero-content">
                <div class="ai-badge">AI Assistant</div>
                <h1 class="ai-title">Accelerate Your Workspace Productivity</h1>
                <p class="ai-subtitle">Generate subtasks, suggest optimized schedules, balance assignees workload and prepare daily standup summaries instantly.</p>
            </div>
            <div class="ai-hero-pattern">🔮</div>
        </div>

        <a-tabs v-model:activeKey="activeTab" class="ai-tabs">
            <!-- TAB 1: TASK OPTIMIZER -->
            <a-tab-pane key="optimizer" tab="Task Optimizer">
                <div class="card-grid">
                    <div class="form-card">
                        <h3>Optimized Task Creator</h3>
                        <p class="section-desc mb-4">Input a task title to generate smart description templates, subtask checklists, and priority level recommendations.</p>
                        
                        <div class="mb-4">
                            <label class="form-label">Task Title</label>
                            <a-input v-model:value="optimizerTitle" placeholder="e.g., Implement OAuth2 sign-in flow" size="large" />
                        </div>

                        <div class="flex gap-4">
                            <a-button type="primary" size="large" :loading="loadingSubtasks" @click="runOptimizer" class="gradient-btn">
                                Optimize Task
                            </a-button>
                        </div>
                    </div>

                    <!-- Optimizer Results -->
                    <div class="result-card" v-if="hasOptimizerResults">
                        <div class="result-header">
                            <h4>AI Suggestions</h4>
                            <span class="badge" :class="suggestedPriority">{{ suggestedPriority || 'P3' }}</span>
                        </div>

                        <div class="result-section">
                            <h5>Suggested Checklist Items</h5>
                            <ul class="checklist-preview">
                                <li v-for="(item, index) in suggestedSubtasks" :key="index">
                                    <span class="bullet">✓</span> {{ item }}
                                </li>
                            </ul>
                        </div>

                        <div class="result-section">
                            <h5>Generated Markdown Description</h5>
                            <pre class="markdown-preview"><code>{{ suggestedDescription }}</code></pre>
                        </div>
                    </div>
                </div>
            </a-tab-pane>

            <!-- TAB 2: SMART SCHEDULER -->
            <a-tab-pane key="scheduler" tab="Smart Scheduler">
                <div class="card-grid">
                    <div class="form-card">
                        <h3>Redistribution & Workload Planner</h3>
                        <p class="section-desc mb-4">Select a project and task to find the least loaded team member and the best category section for it.</p>
                        
                        <div class="mb-4">
                            <label class="form-label">Project</label>
                            <a-select
                                v-model:value="selectedProjectId"
                                placeholder="Select a Project"
                                size="large"
                                style="width: 100%"
                                @change="onProjectChange"
                            >
                                <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">
                                    {{ proj.name }}
                                </a-select-option>
                            </a-select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Task Title</label>
                            <a-input v-model:value="schedulerTitle" placeholder="e.g., Fix database replication crash" size="large" />
                        </div>

                        <a-button type="primary" size="large" :loading="loadingSchedule" @click="runScheduler" class="gradient-btn" :disabled="!selectedProjectId || !schedulerTitle">
                            Analyze & Schedule
                        </a-button>
                    </div>

                    <!-- Scheduler Results -->
                    <div class="result-card" v-if="hasSchedulerResults">
                        <div class="result-header">
                            <h4>Smart Scheduling Report</h4>
                        </div>

                        <div class="result-section">
                            <h5>Recommended Assignee</h5>
                            <div class="recommendation-item">
                                <span class="rec-icon">👤</span>
                                <div>
                                    <strong>{{ schedulerResults.assignee_suggestion.suggested_user.name }}</strong>
                                    <p class="text-xs text-gray-500 mb-0">{{ schedulerResults.assignee_suggestion.reason }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="result-section">
                            <h5>Recommended Project Section</h5>
                            <div class="recommendation-item">
                                <span class="rec-icon">📂</span>
                                <div>
                                    <strong>{{ schedulerResults.section_suggestion.suggested_section.name }}</strong>
                                    <p class="text-xs text-gray-500 mb-0">{{ schedulerResults.section_suggestion.reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a-tab-pane>

            <!-- TAB 3: DAILY STANDUP -->
            <a-tab-pane key="standup" tab="Daily Standup Summary">
                <div class="card-grid">
                    <div class="form-card">
                        <h3>Standup Report Generator</h3>
                        <p class="section-desc mb-4">Pull all tasks completed, in progress, or blocked for today to generate your daily standup outline.</p>
                        
                        <div class="mb-4">
                            <label class="form-label">Report Date</label>
                            <a-date-picker v-model:value="standupDate" style="width: 100%" size="large" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
                        </div>

                        <a-button type="primary" size="large" :loading="loadingStandup" @click="generateStandup" class="gradient-btn">
                            Generate Report
                        </a-button>
                    </div>

                    <!-- Standup Results -->
                    <div class="result-card" v-if="hasStandupResults">
                        <div class="result-header">
                            <h4>Daily Standup Outline</h4>
                        </div>

                        <div class="result-section">
                            <h5 class="text-green-600">What I Did Today (Completed)</h5>
                            <ul class="standup-list" v-if="standupResults.done.length">
                                <li v-for="t in standupResults.done" :key="t">✅ {{ t }}</li>
                            </ul>
                            <p class="no-items" v-else>No tasks completed today.</p>
                        </div>

                        <div class="result-section">
                            <h5 class="text-blue-600">What I'm Working On (In Progress)</h5>
                            <ul class="standup-list" v-if="standupResults.in_progress.length">
                                <li v-for="t in standupResults.in_progress" :key="t">⚡ {{ t }}</li>
                            </ul>
                            <p class="no-items" v-else>No tasks in progress.</p>
                        </div>

                        <div class="result-section">
                            <h5 class="text-red-600">Any Blockers / Overdue</h5>
                            <ul class="standup-list" v-if="standupResults.blocked.length">
                                <li v-for="t in standupResults.blocked" :key="t">⚠️ {{ t }}</li>
                            </ul>
                            <p class="no-items" v-else>No blockers or overdue tasks.</p>
                        </div>
                    </div>
                </div>
            </a-tab-pane>
        </a-tabs>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader },
    setup() {
        const activeTab = ref('optimizer');
        const projects = ref([]);

        // Task Optimizer
        const optimizerTitle = ref('');
        const loadingSubtasks = ref(false);
        const suggestedSubtasks = ref([]);
        const suggestedPriority = ref('P3');
        const suggestedDescription = ref('');
        const hasOptimizerResults = ref(false);

        // Smart Scheduler
        const selectedProjectId = ref(null);
        const schedulerTitle = ref('');
        const loadingSchedule = ref(false);
        const schedulerResults = ref(null);
        const hasSchedulerResults = ref(false);

        // Daily Standup
        const standupDate = ref(dayjs().format('YYYY-MM-DD'));
        const loadingStandup = ref(false);
        const standupResults = ref(null);
        const hasStandupResults = ref(false);

        const fetchProjects = async () => {
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = res;
            } catch (e) {
                message.error('Failed to load projects');
            }
        };

        const runOptimizer = async () => {
            if (!optimizerTitle.value.trim()) {
                message.warning('Please enter a task title');
                return;
            }

            loadingSubtasks.value = true;
            try {
                const [subtasksRes, priorityRes, descRes] = await Promise.all([
                    axiosAdmin.post('/enterprise-tasks/ai/generate-subtasks', { task_title: optimizerTitle.value }),
                    axiosAdmin.post('/enterprise-tasks/ai/suggest-priority', { task_title: optimizerTitle.value }),
                    axiosAdmin.post('/enterprise-tasks/ai/generate-description', { task_title: optimizerTitle.value })
                ]);

                suggestedSubtasks.value = subtasksRes.subtasks;
                suggestedPriority.value = priorityRes.suggested_priority;
                suggestedDescription.value = descRes.suggested_description;
                hasOptimizerResults.value = true;
                message.success('AI Optimization Complete!');
            } catch (e) {
                message.error('Error generating AI suggestions');
            } finally {
                loadingSubtasks.value = false;
            }
        };

        const runScheduler = async () => {
            loadingSchedule.value = true;
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/ai/smart-schedule', {
                    params: {
                        x_project_id: selectedProjectId.value,
                        task_title: schedulerTitle.value
                    }
                });
                schedulerResults.value = res;
                hasSchedulerResults.value = true;
                message.success('Smart scheduling report loaded');
            } catch (e) {
                message.error('Failed to generate smart schedule recommendations');
            } finally {
                loadingSchedule.value = false;
            }
        };

        const generateStandup = async () => {
            loadingStandup.value = true;
            try {
                const res = await axiosAdmin.post('/enterprise-tasks/ai/standup-summary', {
                    date: standupDate.value
                });
                standupResults.value = res;
                hasStandupResults.value = true;
                message.success('Standup summary generated');
            } catch (e) {
                message.error('Failed to load standup summary');
            } finally {
                loadingStandup.value = false;
            }
        };

        onMounted(() => {
            fetchProjects();
        });

        return {
            activeTab,
            projects,
            // Optimizer
            optimizerTitle,
            loadingSubtasks,
            suggestedSubtasks,
            suggestedPriority,
            suggestedDescription,
            hasOptimizerResults,
            runOptimizer,
            // Scheduler
            selectedProjectId,
            schedulerTitle,
            loadingSchedule,
            schedulerResults,
            hasSchedulerResults,
            runScheduler,
            // Standup
            standupDate,
            loadingStandup,
            standupResults,
            hasStandupResults,
            generateStandup
        };
    }
});
</script>

<style scoped>
.ai-assistant-container {
    padding: 24px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.ai-hero-card {
    background: linear-gradient(135deg, #1e1b4b, #312e81, #4338ca);
    border-radius: 16px;
    padding: 32px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
}

.ai-hero-content {
    max-width: 60%;
    position: relative;
    z-index: 2;
}

.ai-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(4px);
    display: inline-block;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 12px;
}

.ai-title {
    font-size: 28px;
    font-weight: 800;
    color: white;
    margin-bottom: 8px;
}

.ai-subtitle {
    font-size: 14px;
    color: #e0e7ff;
    line-height: 1.5;
}

.ai-hero-pattern {
    position: absolute;
    right: 48px;
    bottom: -16px;
    font-size: 120px;
    opacity: 0.2;
    transform: rotate(15deg);
}

.card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 16px;
}

.form-card, .result-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}

.form-label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    display: block;
    margin-bottom: 6px;
}

.section-desc {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
}

.gradient-btn {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    border: none;
    font-weight: 600;
}

.gradient-btn:hover {
    background: linear-gradient(135deg, #4338ca, #4f46e5);
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
    margin-bottom: 16px;
}

.result-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
}

.badge.P1 { background: #fee2e2; color: #ef4444; }
.badge.P2 { background: #ffedd5; color: #f97316; }
.badge.P3 { background: #dbeafe; color: #3b82f6; }
.badge.P4 { background: #f1f5f9; color: #64748b; }

.result-section {
    margin-bottom: 20px;
}

.result-section h5 {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 8px;
}

.checklist-preview {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.checklist-preview li {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f8fafc;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 13px;
    border: 1px solid #f1f5f9;
}

.bullet {
    color: #10b981;
    font-weight: bold;
}

.markdown-preview {
    background: #0f172a;
    color: #f1f5f9;
    border-radius: 8px;
    padding: 12px;
    max-height: 200px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
}

.recommendation-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.rec-icon {
    font-size: 24px;
    background: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    flex-shrink: 0;
}

.standup-list {
    list-style: none;
    padding-left: 0;
}

.standup-list li {
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 13px;
    border-left: 3px solid #cbd5e1;
}

.no-items {
    font-style: italic;
    color: #94a3b8;
    font-size: 13px;
}

@media (max-width: 768px) {
    .card-grid {
        grid-template-columns: 1fr;
    }
    .ai-hero-content {
        max-width: 100%;
    }
    .ai-hero-pattern {
        display: none;
    }
}
</style>
