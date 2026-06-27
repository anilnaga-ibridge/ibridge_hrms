<template>
    <a-modal
        v-model:open="isOpen"
        :footer="null"
        :closable="false"
        width="650px"
        @cancel="closePalette"
        style="top: 80px;"
        body-style="padding: 0; border-radius: 12px; overflow: hidden;"
    >
        <div style="background: #ffffff; border-radius: 12px; box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);">
            <!-- Search Input -->
            <div style="display: flex; align-items: center; border-bottom: 1px solid #f3f4f6; padding: 14px 18px;">
                <SearchOutlined style="font-size: 20px; color: #9ca3af; margin-right: 12px;" />
                <input
                    ref="searchRef"
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search tasks, projects, views, or type commands..."
                    style="border: none; outline: none; width: 100%; font-size: 16px; color: #111827;"
                    @input="handleSearch"
                    @keydown.down="moveSelection(1)"
                    @keydown.up="moveSelection(-1)"
                    @keydown.enter="confirmSelection"
                />
                <span style="font-size: 11px; background: #f3f4f6; padding: 2px 6px; border-radius: 4px; color: #6b7280; font-family: monospace; font-weight: bold;">
                    ESC
                </span>
            </div>

            <!-- Palette Results Container -->
            <div style="max-height: 400px; overflow-y: auto; padding: 8px 0;">
                
                <!-- Quick Actions / Commands (when search is empty) -->
                <div v-if="!searchQuery">
                    <div class="group-title">Navigation & Actions</div>
                    <div
                        v-for="(action, index) in staticActions"
                        :key="action.key"
                        :class="['result-item', { active: activeIndex === index }]"
                        @click="executeAction(action)"
                        @mouseenter="activeIndex = index"
                    >
                        <component :is="action.icon" style="font-size: 14px; margin-right: 10px; color: #8b5cf6;" />
                        <span style="font-weight: 500;">{{ action.title }}</span>
                        <span style="margin-left: auto; font-size: 11px; color: #9ca3af; font-family: monospace;">{{ action.shortcut || '' }}</span>
                    </div>
                </div>

                <!-- Live Search Results -->
                <div v-else>
                    <!-- Loading state -->
                    <div v-if="searching" style="padding: 24px; text-align: center; color: #9ca3af;">
                        <a-spin size="small" style="margin-right: 8px;" /> Searching...
                    </div>

                    <!-- Categorized Results -->
                    <div v-else-if="hasResults">
                        
                        <!-- Tasks Category -->
                        <div v-if="results.tasks && results.tasks.length > 0">
                            <div class="group-title">Tasks</div>
                            <div
                                v-for="(task, index) in results.tasks"
                                :key="task.xid"
                                :class="['result-item', { active: activeIndex === getGlobalIndex('tasks', index) }]"
                                @click="goToTask(task)"
                                @mouseenter="activeIndex = getGlobalIndex('tasks', index)"
                            >
                                <span style="font-family: monospace; font-size: 11px; padding: 2px 6px; background: #e5e7eb; border-radius: 4px; margin-right: 10px; font-weight: bold; color: #4b5563;">
                                    {{ task.task_number }}
                                </span>
                                <span>{{ task.title }}</span>
                                <a-tag :color="getPriorityColor(task.priority)" size="small" style="margin-left: auto;">
                                    {{ task.priority }}
                                </a-tag>
                            </div>
                        </div>

                        <!-- Projects Category -->
                        <div v-if="results.projects && results.projects.length > 0">
                            <div class="group-title">Projects</div>
                            <div
                                v-for="(proj, index) in results.projects"
                                :key="proj.xid"
                                :class="['result-item', { active: activeIndex === getGlobalIndex('projects', index) }]"
                                @click="goToProject(proj)"
                                @mouseenter="activeIndex = getGlobalIndex('projects', index)"
                            >
                                <FolderOutlined style="font-size: 14px; margin-right: 10px; color: #3b82f6;" />
                                <span>{{ proj.name }}</span>
                            </div>
                        </div>

                        <!-- Labels Category -->
                        <div v-if="results.labels && results.labels.length > 0">
                            <div class="group-title">Labels</div>
                            <div
                                v-for="(lbl, index) in results.labels"
                                :key="lbl.xid"
                                :class="['result-item', { active: activeIndex === getGlobalIndex('labels', index) }]"
                                @click="goToLabel(lbl)"
                                @mouseenter="activeIndex = getGlobalIndex('labels', index)"
                            >
                                <TagOutlined style="font-size: 14px; margin-right: 10px; color: #10b981;" />
                                <span>#{{ lbl.name }}</span>
                            </div>
                        </div>

                    </div>

                    <!-- No Results State -->
                    <div v-else style="padding: 24px; text-align: center; color: #9ca3af;">
                        No results found for "{{ searchQuery }}"
                    </div>
                </div>

            </div>
        </div>
    </a-modal>
</template>

<script>
import { defineComponent, ref, watch, nextTick, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
    SearchOutlined, PlusOutlined, DashboardOutlined,
    CalendarOutlined, BarChartOutlined, FolderOutlined, TagOutlined
} from '@ant-design/icons-vue';
import debounce from 'lodash/debounce';

export default defineComponent({
    props: {
        open: Boolean
    },
    components: {
        SearchOutlined,
        PlusOutlined,
        DashboardOutlined,
        CalendarOutlined,
        BarChartOutlined,
        FolderOutlined,
        TagOutlined
    },
    setup(props, { emit }) {
        const router = useRouter();
        const isOpen = ref(false);
        const searchQuery = ref('');
        const searching = ref(false);
        const activeIndex = ref(0);
        const searchRef = ref(null);
        
        const results = ref({
            tasks: [],
            projects: [],
            labels: [],
            views: [],
            templates: []
        });

        const staticActions = [
            { key: 'create_task', title: 'Create Task', icon: 'PlusOutlined', shortcut: 'Q' },
            { key: 'go_dashboard', title: 'Go To Dashboard', icon: 'DashboardOutlined', route: 'admin.enterprise_tasks.dashboard' },
            { key: 'go_calendar', title: 'Open Calendar', icon: 'CalendarOutlined', route: 'admin.enterprise_tasks.calendar' },
            { key: 'go_reports', title: 'Open Reports', icon: 'BarChartOutlined', route: 'admin.enterprise_tasks.reports' },
            { key: 'go_projects', title: 'View All Projects', icon: 'FolderOutlined', route: 'admin.enterprise_tasks.projects' }
        ];

        watch(() => props.open, (newVal) => {
            isOpen.value = newVal;
            if (newVal) {
                searchQuery.value = '';
                activeIndex.value = 0;
                results.value = { tasks: [], projects: [], labels: [], views: [], templates: [] };
                nextTick(() => {
                    if (searchRef.value) {
                        searchRef.value.focus();
                    }
                });
            }
        });

        // Compute flat index limits to help keyboard selection
        const flatItemsList = computed(() => {
            if (!searchQuery.value) {
                return staticActions;
            }
            const flat = [];
            if (results.value.tasks) {
                results.value.tasks.forEach(t => flat.push({ type: 'task', item: t }));
            }
            if (results.value.projects) {
                results.value.projects.forEach(p => flat.push({ type: 'project', item: p }));
            }
            if (results.value.labels) {
                results.value.labels.forEach(l => flat.push({ type: 'label', item: l }));
            }
            return flat;
        });

        const getGlobalIndex = (type, index) => {
            let offset = 0;
            if (type === 'projects') {
                offset += results.value.tasks ? results.value.tasks.length : 0;
            } else if (type === 'labels') {
                offset += results.value.tasks ? results.value.tasks.length : 0;
                offset += results.value.projects ? results.value.projects.length : 0;
            }
            return offset + index;
        };

        const hasResults = computed(() => {
            const r = results.value;
            return (r.tasks?.length || r.projects?.length || r.labels?.length) > 0;
        });

        const fetchResults = debounce(async () => {
            if (!searchQuery.value.trim()) return;
            searching.value = true;
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/global-search', {
                    params: { query: searchQuery.value }
                });
                results.value = response;
                activeIndex.value = 0;
            } catch (error) {
                console.error(error);
            } finally {
                searching.value = false;
            }
        }, 300);

        const handleSearch = () => {
            activeIndex.value = 0;
            if (searchQuery.value.trim()) {
                fetchResults();
            }
        };

        const moveSelection = (direction) => {
            const list = flatItemsList.value;
            if (list.length === 0) return;
            activeIndex.value = (activeIndex.value + direction + list.length) % list.length;
        };

        const confirmSelection = () => {
            const list = flatItemsList.value;
            if (list.length === 0 || activeIndex.value >= list.length) return;

            const selected = list[activeIndex.value];
            if (!searchQuery.value) {
                executeAction(selected);
            } else {
                if (selected.type === 'task') {
                    goToTask(selected.item);
                } else if (selected.type === 'project') {
                    goToProject(selected.item);
                } else if (selected.type === 'label') {
                    goToLabel(selected.item);
                }
            }
        };

        const executeAction = (action) => {
            closePalette();
            if (action.key === 'create_task') {
                emit('trigger-quick-add');
            } else if (action.route) {
                router.push({ name: action.route });
            }
        };

        const goToTask = (task) => {
            closePalette();
            emit('open-task', task.xid);
        };

        const goToProject = (proj) => {
            closePalette();
            router.push({ name: 'admin.enterprise_tasks.project_details', params: { id: proj.xid } });
        };

        const goToLabel = (lbl) => {
            closePalette();
            router.push({ name: 'admin.enterprise_tasks.list', query: { label: lbl.name } });
        };

        const closePalette = () => {
            isOpen.value = false;
            emit('close');
        };

        const getPriorityColor = (prio) => {
            const colors = { P1: 'red', P2: 'orange', P3: 'blue', P4: 'green' };
            return colors[prio] || 'blue';
        };

        return {
            isOpen,
            searchQuery,
            searching,
            activeIndex,
            results,
            staticActions,
            searchRef,
            hasResults,
            getGlobalIndex,
            handleSearch,
            moveSelection,
            confirmSelection,
            executeAction,
            goToTask,
            goToProject,
            goToLabel,
            closePalette,
            getPriorityColor
        };
    }
});
</script>

<style scoped>
.group-title {
    font-size: 11px;
    font-weight: bold;
    color: #9ca3af;
    text-transform: uppercase;
    padding: 10px 18px 4px 18px;
    letter-spacing: 0.05em;
}
.result-item {
    display: flex;
    align-items: center;
    padding: 10px 18px;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    transition: all 0.15s ease;
}
.result-item.active {
    background: #f3f4f6;
    color: #111827;
}
</style>
