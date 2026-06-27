<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="My Favorites" class="p-0">
                <template #extra>
                    <div style="font-size: 13px; color: #64748b;">
                        Quick access to your most important items
                    </div>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Favorites</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="favorites-container">
        <!-- Tabbed or categorized layout -->
        <a-card :bordered="false" class="premium-card">
            <div class="favorites-grid">
                <!-- Projects Section -->
                <div class="favorites-section">
                    <div class="section-title">
                        <FolderOpenOutlined class="icon-projects" />
                        <h3>Favorited Projects</h3>
                    </div>
                    <div class="favorites-list">
                        <div v-if="projectFavorites.length === 0" class="empty-favorite-item">
                            <span>No favorited projects.</span>
                        </div>
                        <div 
                            v-for="fav in projectFavorites" 
                            :key="fav.xid"
                            class="favorite-card"
                            @click="navigateToFavorite(fav)"
                        >
                            <div class="favorite-icon-circle" :style="{ backgroundColor: getBgColor(fav.details?.color) }">
                                <span class="colored-dot" :style="{ backgroundColor: fav.details?.color || '#3b82f6' }"></span>
                            </div>
                            <div class="favorite-info">
                                <span class="fav-name">{{ fav.details?.name || 'Deleted Project' }}</span>
                                <span class="fav-sub">Project</span>
                            </div>
                            <a-button 
                                type="text" 
                                danger 
                                class="unfav-btn" 
                                @click.stop="removeFavorite(fav)"
                                title="Remove from favorites"
                            >
                                <StarFilled />
                            </a-button>
                        </div>
                    </div>
                </div>

                <!-- Labels Section -->
                <div class="favorites-section">
                    <div class="section-title">
                        <TagOutlined class="icon-labels" />
                        <h3>Favorited Labels</h3>
                    </div>
                    <div class="favorites-list">
                        <div v-if="labelFavorites.length === 0" class="empty-favorite-item">
                            <span>No favorited labels.</span>
                        </div>
                        <div 
                            v-for="fav in labelFavorites" 
                            :key="fav.xid"
                            class="favorite-card"
                            @click="navigateToFavorite(fav)"
                        >
                            <div class="favorite-icon-circle" :style="{ backgroundColor: getBgColor(fav.details?.color) }">
                                <TagFilled :style="{ color: fav.details?.color || '#10b981' }" />
                            </div>
                            <div class="favorite-info">
                                <span class="fav-name">#{{ fav.details?.name || 'Deleted Label' }}</span>
                                <span class="fav-sub">Label</span>
                            </div>
                            <a-button 
                                type="text" 
                                danger 
                                class="unfav-btn" 
                                @click.stop="removeFavorite(fav)"
                                title="Remove from favorites"
                            >
                                <StarFilled />
                            </a-button>
                        </div>
                    </div>
                </div>

                <!-- Views & Filters Section -->
                <div class="favorites-section">
                    <div class="section-title">
                        <FilterOutlined class="icon-filters" />
                        <h3>Favorited Views & Filters</h3>
                    </div>
                    <div class="favorites-list">
                        <div v-if="viewFavorites.length === 0" class="empty-favorite-item">
                            <span>No favorited filters or views.</span>
                        </div>
                        <div 
                            v-for="fav in viewFavorites" 
                            :key="fav.xid"
                            class="favorite-card"
                            @click="navigateToFavorite(fav)"
                        >
                            <div class="favorite-icon-circle bg-purple">
                                <CalendarOutlined v-if="fav.type === 'view'" class="text-purple" />
                                <FilterFilled v-else class="text-purple" />
                            </div>
                            <div class="favorite-info">
                                <span class="fav-name">{{ fav.details?.name || 'Deleted Filter/View' }}</span>
                                <span class="fav-sub">{{ fav.type === 'view' ? 'Saved View' : 'Saved Filter' }}</span>
                            </div>
                            <a-button 
                                type="text" 
                                danger 
                                class="unfav-btn" 
                                @click.stop="removeFavorite(fav)"
                                title="Remove from favorites"
                            >
                                <StarFilled />
                            </a-button>
                        </div>
                    </div>
                </div>
            </div>
        </a-card>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { 
    FolderOpenOutlined, 
    TagOutlined, 
    FilterOutlined, 
    StarFilled,
    TagFilled,
    FilterFilled,
    CalendarOutlined
} from '@ant-design/icons-vue';
import { message } from 'ant-design-vue';
import { useRouter } from 'vue-router';


export default defineComponent({
    components: {
        AdminPageHeader,
        FolderOpenOutlined,
        TagOutlined,
        FilterOutlined,
        StarFilled,
        TagFilled,
        FilterFilled,
        CalendarOutlined
    },
    setup() {
        const favorites = ref([]);
        const router = useRouter();

        const fetchFavorites = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/favorites');
                favorites.value = response || [];
            } catch (error) {
                console.error(error);
                message.error('Failed to load favorites');
            }
        };

        const projectFavorites = computed(() => {
            return favorites.value.filter(fav => fav.type === 'project');
        });

        const labelFavorites = computed(() => {
            return favorites.value.filter(fav => fav.type === 'label');
        });

        const viewFavorites = computed(() => {
            return favorites.value.filter(fav => fav.type === 'view' || fav.type === 'filter');
        });

        const removeFavorite = async (fav) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/favorites/${fav.xid}`);
                message.success('Removed from favorites');
                fetchFavorites();
            } catch (error) {
                console.error(error);
                message.error('Failed to remove favorite');
            }
        };

        const navigateToFavorite = (fav) => {
            if (!fav.details) {
                message.warning('This item is no longer available');
                return;
            }

            if (fav.type === 'project') {
                router.push({
                    name: 'admin.enterprise_tasks.project_details',
                    params: { id: fav.reference_id }
                });
            } else if (fav.type === 'label') {
                router.push({
                    name: 'admin.enterprise_tasks.list',
                    query: { label_ids: [fav.reference_id] }
                });
            } else if (fav.type === 'filter' || fav.type === 'view') {
                router.push({
                    name: 'admin.enterprise_tasks.list',
                    query: { saved_view_xid: fav.reference_id }
                });
            }
        };

        const getBgColor = (color) => {
            if (!color) return 'rgba(59, 130, 246, 0.1)';
            // Make a transparent version of color
            return `${color}15`; 
        };

        onMounted(() => {
            fetchFavorites();
        });

        return {
            favorites,
            projectFavorites,
            labelFavorites,
            viewFavorites,
            removeFavorite,
            navigateToFavorite,
            getBgColor
        };
    }
});
</script>

<style scoped>
.favorites-container {
    padding: 20px 16px;
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.premium-card {
    border-radius: 12px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.favorites-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 2px solid #f1f5f9;
    padding-bottom: 10px;
}

.section-title h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #334155;
}

.section-title .icon-projects { color: #3b82f6; font-size: 18px; }
.section-title .icon-labels { color: #10b981; font-size: 18px; }
.section-title .icon-filters { color: #8b5cf6; font-size: 18px; }

.favorites-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.empty-favorite-item {
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px dashed #e2e8f0;
    text-align: center;
    color: #94a3b8;
    font-size: 13px;
}

.favorite-card {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
}

.favorite-card:hover {
    border-color: #cbd5e1;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.04);
}

.favorite-icon-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
}

.colored-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.bg-purple { background-color: rgba(139, 92, 246, 0.1); }
.text-purple { color: #8b5cf6; }

.favorite-info {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.fav-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.fav-sub {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.unfav-btn {
    color: #eab308 !important;
}

.unfav-btn:hover {
    color: #ef4444 !important;
}
</style>
