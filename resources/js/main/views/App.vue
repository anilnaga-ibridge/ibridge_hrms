<template>
    <router-view v-slot="{ Component, route }">
        <suspense>
            <template #default>
                <a-config-provider
                    :theme="{
                        algorithm: [
                            themeMode == 'dark'
                                ? theme.darkAlgorithm
                                : theme.defaultAlgorithm,
                        ],
                        token: {
                            colorPrimary: appSetting.primary_color,
                            fontFamily: 'Nunito,sans-serif',
                            borderRadius: 4,
                        },
                    }"
                    :direction="appSetting.rtl ? 'rtl' : 'ltr'"
                >
                    <div class="theme-container">
                        <ThemeProvider :theme="{ ...themeVars }">
                            <LoadingApp v-if="appChecking" />
                            <template v-else>
                                <component :is="Component" />
                                <QuickAddModal v-model:open="quickAddOpen" :initial-date="quickAddInitialDate" />
                                <CommandPalette v-model:open="commandPaletteOpen" />
                            </template>
                        </ThemeProvider>
                    </div>
                </a-config-provider>
            </template>
            <template #fallback> <LoadingApp /> </template>
        </suspense>
    </router-view>
</template>

<script>
import { ref, watch, onMounted, onUnmounted, computed } from "vue";
import { theme } from "ant-design-vue";
import { ThemeProvider } from "vue3-styled-components";
import { themeVars } from "../config/theme/themeVariables";
import { useRoute, useRouter } from "vue-router";
import common from "../../common/composable/common";
import LoadingApp from "./LoadingApp.vue";
import { useAuthStore } from "../store/authStore";
import QuickAddModal from "./enterprise-tasks/QuickAddModal.vue";
import CommandPalette from "./enterprise-tasks/CommandPalette.vue";

export default {
    name: "App",
    components: {
        ThemeProvider,
        LoadingApp,
        QuickAddModal,
        CommandPalette,
    },
    setup() {
        const authStore = useAuthStore();
        const route = useRoute();
        const router = useRouter();
        const darkTheme = "dark";
        const {
            updatePageTitle,
            appSetting,
            frontWarehouse,
            appType,
            themeMode,
        } = common();
        const appChecking = computed(() => authStore.appChecking);

        const quickAddOpen = ref(false);
        const quickAddInitialDate = ref(null);
        const commandPaletteOpen = ref(false);

        const handleGlobalKeydown = (e) => {
            if (!e.key) return;
            const activeEl = document.activeElement;
            if (activeEl && (
                activeEl.tagName === "INPUT" ||
                activeEl.tagName === "TEXTAREA" ||
                activeEl.contentEditable === "true" ||
                activeEl.tagName === "SELECT"
            )) {
                return;
            }

            // Q to open Quick Add Modal
            if (e.key.toLowerCase() === "q" && !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey) {
                e.preventDefault();
                quickAddInitialDate.value = null;
                quickAddOpen.value = true;
            }

            // Ctrl + K or Cmd + K to open Command Palette
            if (e.key.toLowerCase() === "k" && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                commandPaletteOpen.value = true;
            }

            // Sequence G + T / G + U
            if (e.key.toLowerCase() === "g") {
                window.__g_pressed = true;
                setTimeout(() => {
                    window.__g_pressed = false;
                }, 1000);
            } else if (window.__g_pressed) {
                if (e.key.toLowerCase() === "t") {
                    e.preventDefault();
                    window.__g_pressed = false;
                    router.push({ name: "admin.enterprise_tasks.today" });
                } else if (e.key.toLowerCase() === "u") {
                    e.preventDefault();
                    window.__g_pressed = false;
                    router.push({ name: "admin.enterprise_tasks.upcoming" });
                }
            }
        };

        onMounted(() => {
            window.addEventListener("keydown", handleGlobalKeydown);
            window.__openQuickAdd = (initialDate = null) => {
                quickAddInitialDate.value = initialDate;
                quickAddOpen.value = true;
            };
            window.__openCommandPalette = () => {
                commandPaletteOpen.value = true;
            };
            if (
                router.currentRoute &&
                router.currentRoute.value &&
                router.currentRoute.value.meta.isFrontStore == undefined
            ) {
                setInterval(() => {
                    authStore.refreshTokenAction();
                }, 10 * 60 * 1000);
            }
        });

        onUnmounted(() => {
            window.removeEventListener("keydown", handleGlobalKeydown);
        });

        watch(route, (newVal, oldVal) => {
            const menuKey =
                newVal.meta && newVal.meta.menuKey
                    ? typeof newVal.meta.menuKey == "function"
                        ? newVal.meta.menuKey(newVal)
                        : newVal.meta.menuKey
                    : "";

            updatePageTitle(menuKey);

            // Redirecting if plan is expired
            if (
                appType == "saas" &&
                appSetting.value.is_global == 0 &&
                appSetting.value.status == "license_expired" &&
                newVal &&
                newVal.meta &&
                !(
                    newVal.meta.menuParent == "subscription" ||
                    newVal.name == "admin.login" ||
                    newVal.name == "verify.main"
                )
            ) {
                router.push({
                    name: "admin.subscription.current_plan",
                }).catch(() => {});
            }
        });

        return {
            theme,
            themeVars,
            darkTheme,
            appChecking,
            appSetting,
            themeMode,
            quickAddOpen,
            quickAddInitialDate,
            commandPaletteOpen,
        };
    },
};
</script>

<style>
body {
    background: #f0f2f5 !important;
}
</style>
