<template>
    <a-card class="notifications-container" :bodyStyle="{ padding: '10px' }">
        <template #title>
            <TrophyOutlined />
            Recent Achievements
        </template>

        <div v-if="loading" class="loading-state">
            <img :src="logoUrl" class="loading-logo" />
            <div class="spinner"></div>
            <div class="typing-text">{{ typedText }}<span class="cursor">|</span></div>
        </div>

        <a-empty v-else-if="!achievements.length" description="No achievements yet" />

        <div v-else class="pending-leave-hrm">
            <perfect-scrollbar :options="{ wheelSpeed: 1, swipeEasing: true, suppressScrollX: true }">
                <div v-for="item in achievements" :key="item.xid" class="achievement-card" @click="showCertificate(item)">
                    <div class="achievement-header">
                        <a-avatar :src="item.user_image" :size="32" />
                        <div class="achievement-info">
                            <strong>{{ item.user_name }}</strong>
                            <span class="award-badge">{{ item.award_name }}</span>
                        </div>
                        <span class="achievement-date">{{ item.date }}</span>
                    </div>
                    <div v-if="item.certificate_html" class="certificate-thumb">
                        <div class="thumb-wrapper">
                            <div class="ql-editor" style="transform: scale(0.22); transform-origin: top left; width: 1123px !important; height: 794px !important; max-width: none !important; max-height: none !important; padding: 0 !important; overflow: hidden !important; white-space: normal !important;" v-html="parsedHtml(item.certificate_html)"></div>
                        </div>
                    </div>
                </div>
            </perfect-scrollbar>
        </div>

        <a-modal
            :visible="certificateModalVisible"
            :title="'Certificate - ' + (selectedAchievement ? selectedAchievement.user_name : '')"
            :footer="null"
            width="800px"
            @cancel="certificateModalVisible = false"
        >
            <div v-if="selectedAchievement && selectedAchievement.certificate_html">
                <div style="width: 700px; height: 495px; overflow: hidden; border: 1px solid #d9d9d9; border-radius: 4px; background: #fff; margin: 0 auto;">
                    <div class="ql-editor" style="transform: scale(0.625); transform-origin: top left; width: 1123px !important; height: 794px !important; max-width: none !important; max-height: none !important; padding: 0 !important; overflow: hidden !important; white-space: normal !important;" v-html="parsedHtml(selectedAchievement.certificate_html)"></div>
                </div>
            </div>
            <div v-else>
                <a-empty description="Certificate not available" />
            </div>
        </a-modal>
    </a-card>
</template>

<script>
import { TrophyOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";

export default {
    name: "RecentAchievements",
    components: { TrophyOutlined },
    data() {
        return {
            achievements: [],
            loading: true,
            certificateModalVisible: false,
            selectedAchievement: null,
            typedText: "",
            logoUrl: "",
        };
    },
    mounted() {
        const appSetting = common().appSetting.value;
        this.logoUrl = appSetting ? (appSetting.light_logo_url || appSetting.dark_logo_url || '') : '';
        this.startTyping();
        this.fetchAchievements();
    },
    methods: {
        fetchAchievements() {
            axiosAdmin.post("self/dashboard", { type: "all" }).then((response) => {
                const data = response.data || response;
                if (data.recentAppreciations) {
                    this.achievements = data.recentAppreciations;
                }
                this.loading = false;
            }).catch(() => {
                this.loading = false;
            });
        },
        showCertificate(item) {
            this.selectedAchievement = item;
            this.certificateModalVisible = true;
        },
        escapeHtml(string) {
            if (!string) return '';
            return string
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },
        parsedHtml(html) {
            if (!html) return "";
            const appSetting = common().appSetting.value;
            const logoUrl = appSetting ? this.escapeHtml(appSetting.light_logo_url) : null;
            const logoHtml = logoUrl ? '<img src="' + logoUrl + '" style="max-height: 30px; max-width: 130px; display: block; margin: 0 auto; object-fit: contain;" />' : '';
            const companyName = appSetting ? this.escapeHtml(appSetting.name || '') : '';
            let result = html.replace(/##COMPANY_LOGO##/g, logoHtml);
            result = result.replace(/##COMPANY_NAME##/g, companyName);
            return result;
        },
        startTyping() {
            const text = "ibridge digital";
            let i = 0;
            const interval = setInterval(() => {
                this.typedText = text.substring(0, i + 1);
                i++;
                if (i >= text.length) clearInterval(interval);
            }, 120);
        },
    },
};
</script>

<style scoped>
.notifications-container {
    width: 100%;
    height: 495px;
}
.pending-leave-hrm .ps {
    height: 428px;
}
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 350px;
    gap: 20px;
}
.loading-logo {
    max-height: 45px;
    max-width: 160px;
    object-fit: contain;
}
.spinner {
    width: 36px;
    height: 36px;
    border: 3px solid #e8e8e8;
    border-top-color: #c39d43;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.typing-text {
    font-size: 18px;
    font-weight: 600;
    color: #555;
    letter-spacing: 2px;
    text-transform: lowercase;
}
.cursor {
    animation: blink 0.8s step-end infinite;
    color: #c39d43;
    font-weight: 100;
}
@keyframes blink {
    50% { opacity: 0; }
}
.achievement-card {
    padding: 8px 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}
.achievement-card:hover {
    background: #fafafa;
}
.achievement-card:last-child {
    border-bottom: none;
}
.achievement-header {
    display: flex;
    align-items: center;
    gap: 10px;
}
.achievement-info {
    flex: 1;
    min-width: 0;
}
.achievement-info strong {
    display: block;
    font-size: 13px;
    line-height: 1.3;
}
.award-badge {
    display: inline-block;
    font-size: 11px;
    color: #c39d43;
    background: #fdf6e3;
    padding: 1px 8px;
    border-radius: 10px;
    font-weight: 600;
    margin-top: 2px;
}
.achievement-date {
    font-size: 11px;
    color: #999;
    white-space: nowrap;
}
.certificate-thumb {
    margin-top: 6px;
    margin-left: 42px;
    border: 1px solid #e8e8e8;
    border-radius: 4px;
    overflow: hidden;
    height: 80px;
}
.thumb-wrapper {
    width: 247px;
    height: 175px;
    overflow: hidden;
}
</style>
