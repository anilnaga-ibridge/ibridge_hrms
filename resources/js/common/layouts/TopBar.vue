<template>
    <a-layout-header
        :style="{
            padding: '0 16px',
            background: themeMode == 'dark' ? '#141414' : '#fff',
        }"
    >
        <a-row>
            <a-col :span="4">
                <a-space>
                    <MenuOutlined class="trigger" @click="showHideMenu" />
                </a-space>
            </a-col>
            <a-col :span="20">
                <HeaderRightIcons>
                    <a-space>
                        <ThemeModeChanger />
                        <a-divider type="vertical" />

                        <!-- Notifications Dropdown -->
                        <a-dropdown
                            :placement="appSetting.rtl ? 'bottomLeft' : 'bottomRight'"
                            v-model:open="notificationsOpen"
                            :trigger="['click']"
                        >
                            <a class="ant-dropdown-link" @click.prevent="fetchNotifications">
                                <a-badge :count="unreadCount" :overflow-count="99">
                                    <BellOutlined :style="{ fontSize: '18px', cursor: 'pointer', color: themeMode == 'dark' ? '#fff' : '#000000a6' }" />
                                </a-badge>
                            </a>
                            <template #overlay>
                                <div class="notification-dropdown-container">
                                    <div class="notification-header">
                                        <span class="notification-title">Notifications</span>
                                        <a-button type="link" size="small" @click="markAllNotificationsAsRead" :disabled="unreadCount === 0" style="padding: 0; height: auto;">
                                            Mark all as read
                                        </a-button>
                                    </div>
                                    <div class="notification-list-scroll">
                                        <div v-if="notifications.length === 0" class="no-notifications">
                                            <span style="font-size: 28px; display: block; margin-bottom: 8px;">🔔</span>
                                            No new notifications
                                        </div>
                                        <div 
                                            v-else 
                                            v-for="item in notifications" 
                                            :key="item.id" 
                                            class="notification-item"
                                            :class="{ 'unread': !item.read_at }"
                                            @click="markSingleNotificationAsRead(item.id)"
                                        >
                                            <div class="notification-icon-wrapper">
                                                <span v-if="item.data.send_for === 'employee_task_assigned'">📝</span>
                                                <span v-else-if="item.data.send_for === 'employee_birthday_reminder'">🎂</span>
                                                <span v-else-if="item.data.send_for === 'holiday_reminder'">🎉</span>
                                                <span v-else-if="item.data.send_for === 'employee_birthday_wish'">🍰</span>
                                                <span v-else>📢</span>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-message">
                                                    {{ getNotificationMessage(item) }}
                                                </div>
                                                <div class="notification-time">
                                                    {{ formatNotificationTime(item.created_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </a-dropdown>
                        <a-divider type="vertical" />
                        <a-dropdown
                            :placement="appSetting.rtl ? 'bottomLeft' : 'bottomRight'"
                        >
                            <a class="ant-dropdown-link" @click.prevent>
                                {{ selectedLang }}
                                <DownOutlined />
                            </a>
                            <template #overlay>
                                <a-menu>
                                    <a-menu-item
                                        v-for="lang in langs"
                                        :key="lang.key"
                                        @click="langSelected(lang.key)"
                                    >
                                        <a-space>
                                            <a-avatar
                                                shape="square"
                                                size="small"
                                                :src="lang.image_url"
                                            />
                                            {{ lang.name }}
                                        </a-space>
                                    </a-menu-item>
                                </a-menu>
                            </template>
                        </a-dropdown>
                        <a-divider type="vertical" />
                        <a-button
                            type="link"
                            @click="
                                () => {
                                    $router.push({
                                        name: 'admin.settings.profile.index',
                                    });
                                }
                            "
                            class="p-0"
                        >
                            <a-avatar size="small" :src="user.profile_image_url" />
                        </a-button>
                    </a-space>
                </HeaderRightIcons>
            </a-col>
        </a-row>
    </a-layout-header>
</template>

<script>
import { ref, reactive, computed, onMounted } from "vue";
import { useAuthStore } from "../../main/store/authStore";
import { MenuOutlined, DownOutlined, BellOutlined } from "@ant-design/icons-vue";
import { useI18n } from "vue-i18n";
import { loadLocaleMessages } from "../i18n";
import { HeaderRightIcons } from "./style";
import common from "../../common/composable/common";
import MenuMode from "./MenuMode.vue";
import AffixButton from "./AffixButton.vue";
import ThemeModeChanger from "./ThemeModeChanger.vue";

export default {
    components: {
        MenuOutlined,
        DownOutlined,
        BellOutlined,
        HeaderRightIcons,
        MenuMode,
        AffixButton,
        ThemeModeChanger,
    },
    setup(props, { emit }) {
        const { user, appSetting, permsArray, menuCollapsed, themeMode } = common();
        const authStore = useAuthStore();
        const selectedLang = ref(authStore.lang);
        const { locale, t } = useI18n();
        const themeModeLoading = ref(false);

        const notifications = ref([]);
        const unreadCount = ref(0);
        const notificationsOpen = ref(false);

        const fetchNotifications = () => {
            axiosAdmin.get("notifications").then((response) => {
                notifications.value = response.data.notifications;
                unreadCount.value = response.data.unread_count;
            });
        };

        const markAllNotificationsAsRead = () => {
            axiosAdmin.post("notifications/mark-read").then((response) => {
                unreadCount.value = 0;
                notifications.value.forEach(item => {
                    item.read_at = new Date().toISOString();
                });
            });
        };

        const markSingleNotificationAsRead = (id) => {
            axiosAdmin.post("notifications/mark-read", { id }).then((response) => {
                unreadCount.value = response.data.unread_count;
                const notification = notifications.value.find(item => item.id === id);
                if (notification) {
                    notification.read_at = new Date().toISOString();
                }
            });
        };

        const getNotificationMessage = (item) => {
            const sendFor = item.data.send_for;
            const data = item.data.data;
            if (sendFor === 'employee_task_assigned') {
                const taskName = data.task ? data.task.name : 'Unknown Task';
                return `You have been assigned to task: ${taskName}`;
            } else if (sendFor === 'employee_birthday_reminder') {
                const birthdayUser = data.birthday_user ? data.birthday_user.name : 'A colleague';
                return `Today is ${birthdayUser}'s birthday! 🎉 Wish them a happy birthday!`;
            } else if (sendFor === 'holiday_reminder') {
                const holidayName = data.holiday ? data.holiday.name : 'Holiday';
                return `Tomorrow is a holiday: ${holidayName} 🏖️`;
            } else if (sendFor === 'employee_birthday_wish') {
                return `Happy Birthday! 🎂 Have a wonderful day ahead!`;
            }
            return `New update: ${item.type}`;
        };

        const formatNotificationTime = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMins / 60);
            
            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        };

        onMounted(() => {
            fetchNotifications();
            // Poll for notifications every 60 seconds
            const interval = setInterval(fetchNotifications, 60000);
        });

        const langSelected = async (lang) => {
            authStore.updateLang(lang);
            await loadLocaleMessages(i18n, lang);

            selectedLang.value = lang;
            locale.value = lang;
        };

        const showHideMenu = () => {
            authStore.updateMenuCollapsed(!menuCollapsed.value);
        };

        const logout = () => {
            authStore.logoutAction();
        };

        const themeModeChanged = (checked) => {
            const mode = checked ? "dark" : "light";
            themeModeLoading.value = true;

            axiosAdmin
                .post("change-theme-mode", {
                    theme_mode: mode,
                })
                .then((response) => {
                    if (response.data.status == "success") {
                        window.location.reload();
                    }
                    themeModeLoading.value = false;
                });
        };

        return {
            permsArray,
            appSetting,
            logout,
            showHideMenu,
            langSelected,
            selectedLang,
            langs: computed(() => authStore.allLangs),

            user,

            themeMode,
            themeModeChanged,
            themeModeLoading,
            
            notifications,
            unreadCount,
            notificationsOpen,
            fetchNotifications,
            markAllNotificationsAsRead,
            markSingleNotificationAsRead,
            getNotificationMessage,
            formatNotificationTime,
        };
    },
};
</script>

<style lang="less">
.trigger {
    font-size: 18px;
    line-height: 64px;
    padding-top: 4px;
    cursor: pointer;
    transition: color 0.3s;
}

.trigger:hover {
    color: #0b59a9;
}

.notification-dropdown-container {
    width: 320px;
    background: v-bind("themeMode == 'dark' ? '#1f1f1f' : '#ffffff'");
    border: 1px solid v-bind("themeMode == 'dark' ? '#303030' : '#f0f0f0'");
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 12px;
    overflow: hidden;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid v-bind("themeMode == 'dark' ? '#303030' : '#f0f0f0'");
    background: v-bind("themeMode == 'dark' ? '#262626' : '#fafafa'");
}

.notification-title {
    font-weight: 600;
    color: v-bind("themeMode == 'dark' ? '#ffffff' : '#000000d9'");
}

.notification-list-scroll {
    max-height: 360px;
    overflow-y: auto;
}

.no-notifications {
    padding: 40px 16px;
    text-align: center;
    color: v-bind("themeMode == 'dark' ? '#8c8c8c' : '#00000073'");
}

.notification-item {
    display: flex;
    padding: 12px 16px;
    border-bottom: 1px solid v-bind("themeMode == 'dark' ? '#303030' : '#f0f0f0'");
    cursor: pointer;
    transition: background 0.2s ease-in-out;
}

.notification-item:hover {
    background: v-bind("themeMode == 'dark' ? '#262626' : '#f5f5f5'");
}

.notification-item.unread {
    background: v-bind("themeMode == 'dark' ? 'rgba(24, 144, 255, 0.08)' : 'rgba(24, 144, 255, 0.04)'");
}

.notification-icon-wrapper {
    font-size: 20px;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-content {
    flex: 1;
}

.notification-message {
    font-size: 14px;
    line-height: 1.4;
    color: v-bind("themeMode == 'dark' ? '#d9d9d9' : '#000000a6'");
    margin-bottom: 4px;
}

.notification-time {
    font-size: 12px;
    color: v-bind("themeMode == 'dark' ? '#595959' : '#00000045'");
}
</style>
