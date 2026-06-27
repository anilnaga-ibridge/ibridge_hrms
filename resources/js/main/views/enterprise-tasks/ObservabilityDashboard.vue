<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Observability & System Health" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Observability</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="observability-container">
        <!-- Hero stats row -->
        <div class="stats-row mb-6">
            <div class="stat-card">
                <span class="stat-icon bg-red">⚠️</span>
                <div class="stat-content">
                    <span class="stat-label">System Errors Logged</span>
                    <span class="stat-value text-red">{{ totalErrors }}</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon bg-yellow">⏱</span>
                <div class="stat-content">
                    <span class="stat-label">Slow Queries (>= 200ms)</span>
                    <span class="stat-value text-yellow">{{ slowQueriesCount }}</span>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon bg-green">⚙️</span>
                <div class="stat-content">
                    <span class="stat-label">Feature Flags Enabled</span>
                    <span class="stat-value text-green">{{ activeFlagsCount }} / {{ totalFlagsCount }}</span>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Left panel: Observability Logs -->
            <div class="main-panel">
                <div class="panel-header mb-4">
                    <h3>Recent System Logs</h3>
                    <a-button type="default" size="small" @click="fetchLogs">Refresh</a-button>
                </div>

                <a-spin :spinning="loadingLogs">
                    <div class="logs-list" v-if="logs.length">
                        <div v-for="log in logs" :key="log.id" class="log-item" :class="log.severity">
                            <div class="log-meta">
                                <span class="badge-sev" :class="log.severity">{{ log.severity.toUpperCase() }}</span>
                                <span class="log-type">{{ log.type }}</span>
                                <span class="log-time">{{ formatTime(log.created_at) }}</span>
                            </div>
                            <p class="log-message">{{ log.message }}</p>
                            <pre class="log-context" v-if="log.context && Object.keys(log.context).length"><code>{{ log.context }}</code></pre>
                        </div>
                    </div>
                    <div class="empty-logs" v-else>
                        <a-empty description="No observability logs recorded." />
                    </div>
                </a-spin>
            </div>

            <!-- Right panel: Feature Flags control -->
            <div class="side-panel">
                <div class="panel-header mb-4">
                    <h3>Premium Modules Configuration</h3>
                </div>

                <a-spin :spinning="loadingFlags">
                    <div class="flags-list">
                        <div v-for="(isEnabled, flag) in featureFlags" :key="flag" class="flag-item">
                            <div class="flag-details">
                                <strong class="flag-name">{{ formatFlagName(flag) }}</strong>
                                <span class="flag-code">{{ flag }}</span>
                            </div>
                            <a-switch
                                :checked="isEnabled"
                                :loading="updatingFlag === flag"
                                @change="checked => toggleFlag(flag, checked)"
                            />
                        </div>
                    </div>
                </a-spin>
            </div>
        </div>
    </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { message } from 'ant-design-vue';
import dayjs from 'dayjs';

export default defineComponent({
    components: { AdminPageHeader },
    setup() {
        const loadingLogs = ref(false);
        const loadingFlags = ref(false);
        const logs = ref([]);
        const featureFlags = ref({});
        const updatingFlag = ref(null);

        // Computed metrics
        const totalErrors = computed(() => {
            return logs.value.filter(l => l.severity === 'error' || l.severity === 'critical').length;
        });

        const slowQueriesCount = computed(() => {
            return logs.value.filter(l => l.type === 'slow_query').length;
        });

        const activeFlagsCount = computed(() => {
            return Object.values(featureFlags.value).filter(Boolean).length;
        });

        const totalFlagsCount = computed(() => {
            return Object.keys(featureFlags.value).length;
        });

        const fetchLogs = async () => {
            loadingLogs.value = true;
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/admin/logs');
                logs.value = res;
            } catch (e) {
                message.error('Failed to load observability logs');
            } finally {
                loadingLogs.value = false;
            }
        };

        const fetchFlags = async () => {
            loadingFlags.value = true;
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/admin/feature-flags');
                featureFlags.value = res;
            } catch (e) {
                message.error('Failed to load feature flags configuration');
            } finally {
                loadingFlags.value = false;
            }
        };

        const toggleFlag = async (feature, isEnabled) => {
            updatingFlag.value = feature;
            try {
                await axiosAdmin.post('/enterprise-tasks/admin/feature-flags/toggle', {
                    feature,
                    is_enabled: isEnabled
                });
                featureFlags.value[feature] = isEnabled;
                message.success(`${formatFlagName(feature)} module ${isEnabled ? 'enabled' : 'disabled'}`);
            } catch (e) {
                message.error('Failed to toggle feature flag');
            } finally {
                updatingFlag.value = null;
            }
        };

        const formatFlagName = (code) => {
            return code
                .replace(/_/g, ' ')
                .split(' ')
                .map(w => w.charAt(0).toUpperCase() + w.slice(1))
                .join(' ');
        };

        const formatTime = (time) => {
            return dayjs(time).format('YYYY-MM-DD HH:mm:ss');
        };

        onMounted(() => {
            fetchLogs();
            fetchFlags();
        });

        return {
            loadingLogs,
            loadingFlags,
            logs,
            featureFlags,
            updatingFlag,
            totalErrors,
            slowQueriesCount,
            activeFlagsCount,
            totalFlagsCount,
            fetchLogs,
            toggleFlag,
            formatFlagName,
            formatTime
        };
    }
});
</script>

<style scoped>
.observability-container {
    padding: 24px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.bg-red { background: linear-gradient(135deg, #fee2e2, #fca5a5); }
.bg-yellow { background: linear-gradient(135deg, #fef3c7, #fde68a); }
.bg-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); }

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
}

.stat-value {
    font-size: 20px;
    font-weight: 800;
}

.text-red { color: #ef4444; }
.text-yellow { color: #d97706; }
.text-green { color: #10b981; }

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.main-panel, .side-panel {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
}

.logs-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-height: 500px;
    overflow-y: auto;
}

.log-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    border: 1px solid #f1f5f9;
    border-left: 4px solid #cbd5e1;
}

.log-item.error, .log-item.critical {
    border-left-color: #ef4444;
}

.log-item.warning {
    border-left-color: #f59e0b;
}

.log-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.badge-sev {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 800;
}

.badge-sev.error, .badge-sev.critical { background: #fee2e2; color: #ef4444; }
.badge-sev.warning { background: #fef3c7; color: #d97706; }
.badge-sev.info { background: #dbeafe; color: #3b82f6; }

.log-type {
    font-size: 11px;
    font-weight: 700;
    color: #475569;
}

.log-time {
    font-size: 10px;
    color: #94a3b8;
    margin-left: auto;
}

.log-message {
    font-size: 12px;
    color: #334155;
    margin: 0;
}

.log-context {
    background: #0f172a;
    color: #cbd5e1;
    border-radius: 6px;
    padding: 8px;
    font-size: 10px;
    margin-top: 8px;
    overflow-x: auto;
}

.flags-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.flag-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.flag-details {
    display: flex;
    flex-direction: column;
}

.flag-name {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
}

.flag-code {
    font-size: 11px;
    color: #94a3b8;
    font-family: monospace;
}

@media (max-width: 992px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    .stats-row {
        grid-template-columns: 1fr;
    }
}
</style>
