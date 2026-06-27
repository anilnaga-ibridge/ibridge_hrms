<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Notification Settings" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Notification Settings</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="notification-settings-container">
        <a-spin :spinning="loading">
            <div class="settings-grid">

                <!-- Email Notifications -->
                <div class="settings-section">
                    <div class="section-icon-header">
                        <div class="section-icon bg-blue">
                            <MailOutlined />
                        </div>
                        <div>
                            <h3 class="section-title">Email Notifications</h3>
                            <p class="section-desc">Receive email alerts for key events</p>
                        </div>
                    </div>

                    <div class="toggle-list">
                        <div class="toggle-item" v-for="item in emailToggles" :key="item.key">
                            <div class="toggle-label">
                                <span class="toggle-title">{{ item.title }}</span>
                                <span class="toggle-desc">{{ item.desc }}</span>
                            </div>
                            <a-switch v-model:checked="prefs[item.key]" @change="savePref" />
                        </div>
                    </div>
                </div>

                <!-- Push Notifications -->
                <div class="settings-section">
                    <div class="section-icon-header">
                        <div class="section-icon bg-purple">
                            <BellOutlined />
                        </div>
                        <div>
                            <h3 class="section-title">Push Notifications</h3>
                            <p class="section-desc">In-app and browser notifications</p>
                        </div>
                    </div>

                    <div class="toggle-list">
                        <div class="toggle-item" v-for="item in pushToggles" :key="item.key">
                            <div class="toggle-label">
                                <span class="toggle-title">{{ item.title }}</span>
                                <span class="toggle-desc">{{ item.desc }}</span>
                            </div>
                            <a-switch v-model:checked="prefs[item.key]" @change="savePref" />
                        </div>
                    </div>
                </div>

                <!-- Daily Digest -->
                <div class="settings-section">
                    <div class="section-icon-header">
                        <div class="section-icon bg-orange">
                            <CalendarOutlined />
                        </div>
                        <div>
                            <h3 class="section-title">Daily Digest</h3>
                            <p class="section-desc">Summary email each morning</p>
                        </div>
                    </div>

                    <div class="toggle-list">
                        <div class="toggle-item">
                            <div class="toggle-label">
                                <span class="toggle-title">Daily Digest Email</span>
                                <span class="toggle-desc">Get a summary of your tasks each morning at 7 AM</span>
                            </div>
                            <a-switch v-model:checked="prefs.daily_digest_enabled" @change="savePref" />
                        </div>
                    </div>
                </div>

                <!-- Notification Preview -->
                <div class="settings-section preview-section">
                    <div class="section-icon-header">
                        <div class="section-icon bg-green">
                            <EyeOutlined />
                        </div>
                        <div>
                            <h3 class="section-title">Notification Preview</h3>
                            <p class="section-desc">Test how notifications appear</p>
                        </div>
                    </div>

                    <div class="preview-card">
                        <div class="preview-notification">
                            <div class="preview-icon">✅</div>
                            <div class="preview-content">
                                <span class="preview-title">Task Assigned to You</span>
                                <span class="preview-body">"Design Login Screen" was assigned to you by Admin</span>
                                <span class="preview-time">Just now</span>
                            </div>
                        </div>
                        <div class="preview-notification" style="margin-top: 10px;">
                            <div class="preview-icon">⚠️</div>
                            <div class="preview-content">
                                <span class="preview-title">Task Overdue</span>
                                <span class="preview-body">"API Integration" was due yesterday</span>
                                <span class="preview-time">Yesterday, 5:00 PM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </a-spin>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { MailOutlined, BellOutlined, CalendarOutlined, EyeOutlined } from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';


export default defineComponent({
    components: { AdminPageHeader, MailOutlined, BellOutlined, CalendarOutlined, EyeOutlined },
    setup() {
        const loading = ref(false);

        const prefs = ref({
            email_on_assign: true,
            email_on_comment: true,
            email_on_overdue: true,
            push_on_assign: false,
            push_on_comment: false,
            push_on_reminder: true,
            daily_digest_enabled: true,
        });

        const emailToggles = [
            { key: 'email_on_assign', title: 'Task Assigned', desc: 'When a task is assigned to you' },
            { key: 'email_on_comment', title: 'New Comment', desc: 'When someone comments on your task' },
            { key: 'email_on_overdue', title: 'Overdue Alert', desc: 'When your tasks become overdue' },
        ];

        const pushToggles = [
            { key: 'push_on_assign', title: 'Task Assigned', desc: 'Instant push notification on assignment' },
            { key: 'push_on_comment', title: 'New Comment', desc: 'Instant push when someone replies' },
            { key: 'push_on_reminder', title: 'Reminder Alerts', desc: 'Receive scheduled reminder notifications' },
        ];

        const fetchPrefs = async () => {
            loading.value = true;
            try {
                const res = await axiosAdmin.get('/enterprise-tasks/notification-preferences');
                if (res) {
                    Object.assign(prefs.value, res);
                }
            } catch (e) {
                // Use defaults if no prefs saved yet
            } finally {
                loading.value = false;
            }
        };

        const savePref = async () => {
            try {
                await axiosAdmin.post('/enterprise-tasks/notification-preferences', prefs.value);
                message.success('Preferences saved');
            } catch (e) {
                message.error('Failed to save preferences');
            }
        };

        onMounted(fetchPrefs);

        return { loading, prefs, emailToggles, pushToggles, savePref };
    }
});
</script>

<style scoped>
.notification-settings-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.settings-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.section-icon-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
}

.section-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    flex-shrink: 0;
}

.bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.bg-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.bg-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.bg-green { background: linear-gradient(135deg, #10b981, #059669); }

.section-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 4px;
}

.section-desc {
    font-size: 12px;
    color: #94a3b8;
    margin: 0;
}

.toggle-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.toggle-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.toggle-label {
    display: flex;
    flex-direction: column;
}

.toggle-title {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.toggle-desc {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.preview-section {
    grid-column: 1 / -1;
}

.preview-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e2e8f0;
}

.preview-notification {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

.preview-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.preview-content {
    display: flex;
    flex-direction: column;
}

.preview-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e293b;
}

.preview-body {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.preview-time {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }

    .preview-section {
        grid-column: 1;
    }
}
</style>
