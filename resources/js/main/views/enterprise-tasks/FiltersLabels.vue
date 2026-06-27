<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Filters & Labels" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Filters & Labels</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div class="filters-labels-container">
        <a-row :gutter="24">
            <!-- 1. Filters Column -->
            <a-col :xs="24" :lg="12">
                <a-card :bordered="false" class="premium-card">
                    <!-- User Profile Header -->
                    <div class="user-profile-header">
                        <a-avatar :size="48" :src="user.profile_image_url" class="user-avatar">
                            <template #icon><UserOutlined /></template>
                        </a-avatar>
                        <div class="user-info">
                            <h2 class="user-name">{{ user.name }}</h2>
                            <span class="user-role">Workspace Owner</span>
                        </div>
                    </div>

                    <a-divider style="margin: 16px 0;" />

                    <!-- My Filters Section -->
                    <div class="section-header">
                        <div class="header-title">
                            <h3>My Filters</h3>
                            <a-tag :color="savedFilters.length >= 3 ? 'warning' : 'processing'" class="utilization-tag">
                                Used: {{ savedFilters.length }}/3
                            </a-tag>
                        </div>
                        <a-button 
                            v-if="savedFilters.length < 3"
                            type="link" 
                            size="small" 
                            @click="openAddFilterModal"
                            class="add-btn"
                        >
                            <PlusOutlined /> Add Filter
                        </a-button>
                    </div>

                    <!-- Personal Filters List -->
                    <div class="filters-list">
                        <div v-if="savedFilters.length === 0" class="empty-state">
                            <SlidersOutlined style="font-size: 28px; color: #94a3b8; margin-bottom: 8px;" />
                            <p>No custom filters created yet.</p>
                        </div>
                        <div 
                            v-for="filter in savedFilters" 
                            :key="filter.xid" 
                            class="item-row filter-item"
                            @click="applyFilter(filter)"
                        >
                            <div class="item-left">
                                <SlidersOutlined class="item-icon text-coral" />
                                <div class="item-details">
                                    <span class="item-name">{{ filter.name }}</span>
                                    <span class="item-subtext">{{ formatCriteria(filter.filter_criteria) }}</span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <a-button 
                                    type="text" 
                                    danger 
                                    size="small" 
                                    @click.stop="deleteFilter(filter.xid)"
                                >
                                    <DeleteOutlined />
                                </a-button>
                            </div>
                        </div>
                    </div>

                    <a-divider style="margin: 24px 0;" />

                    <!-- Anil Team Section -->
                    <div class="user-profile-header team-header">
                        <a-avatar :size="40" class="team-avatar">
                            <template #icon><TeamOutlined /></template>
                        </a-avatar>
                        <div class="user-info">
                            <h2 class="user-name">Anil Team</h2>
                            <span class="user-role">Shared Team Filters</span>
                        </div>
                    </div>

                    <a-divider style="margin: 16px 0;" />

                    <div class="section-header">
                        <div class="header-title">
                            <h3>Anil Team Filters</h3>
                            <a-tag color="default" class="utilization-tag">
                                Used: 0/3
                            </a-tag>
                        </div>
                    </div>

                    <!-- Team Filters List (Empty Placeholder) -->
                    <div class="team-filters-list empty-placeholder-box">
                        <TeamOutlined style="font-size: 32px; color: #cbd5e1; margin-bottom: 12px;" />
                        <p class="placeholder-text">Your team’s list of filters will show up here.</p>
                    </div>
                </a-card>
            </a-col>

            <!-- 2. Labels Column -->
            <a-col :xs="24" :lg="12">
                <a-card :bordered="false" class="premium-card">
                    <div class="section-header flex-column-start">
                        <div class="header-main-title">
                            <h2 class="section-main-heading">Labels</h2>
                            <p class="section-sub-heading">Organize your tasks with color-coded custom labels.</p>
                        </div>
                        <a-button type="primary" size="middle" @click="openAddLabelModal" class="premium-add-btn">
                            <PlusOutlined /> Add Label
                        </a-button>
                    </div>

                    <a-divider style="margin: 16px 0 24px 0;" />

                    <!-- Labels List -->
                    <div class="labels-list">
                        <div v-if="labels.length === 0" class="empty-placeholder-box labels-empty">
                            <TagOutlined style="font-size: 36px; color: #cbd5e1; margin-bottom: 12px;" />
                            <p class="placeholder-text">Your list of labels will show up here.</p>
                        </div>
                        <div 
                            v-for="label in labels" 
                            :key="label.xid" 
                            class="item-row label-item"
                        >
                            <div class="item-left">
                                <span class="color-dot" :style="{ backgroundColor: label.color }"></span>
                                <span class="item-name font-medium">{{ label.name }}</span>
                            </div>
                            <div class="item-actions">
                                <a-space>
                                    <a-button 
                                        type="text" 
                                        size="small" 
                                        @click="openEditLabelModal(label)"
                                    >
                                        <EditOutlined />
                                    </a-button>
                                    <a-button 
                                        type="text" 
                                        danger 
                                        size="small" 
                                        @click="deleteLabel(label.xid)"
                                    >
                                        <DeleteOutlined />
                                    </a-button>
                                </a-space>
                            </div>
                        </div>
                    </div>
                </a-card>
            </a-col>
        </a-row>
    </div>

    <!-- Add/Edit Label Modal -->
    <a-modal
        v-model:visible="labelModalVisible"
        :title="editingLabel ? 'Edit Label' : 'Add Label'"
        @ok="saveLabel"
        :confirmLoading="savingLabel"
        destroyOnClose
        class="premium-modal"
    >
        <a-form layout="vertical">
            <a-form-item label="Label Name" required>
                <a-input v-model:value="labelForm.name" placeholder="e.g. High Priority, Personal, Review" />
            </a-form-item>
            <a-form-item label="Label Color" required>
                <div class="color-selector">
                    <span 
                        v-for="preset in colorPresets" 
                        :key="preset.value" 
                        class="color-option"
                        :style="{ backgroundColor: preset.value }"
                        :class="{ active: labelForm.color === preset.value }"
                        @click="labelForm.color = preset.value"
                        :title="preset.name"
                    >
                        <span v-if="labelForm.color === preset.value" class="check-mark">✓</span>
                    </span>
                </div>
            </a-form-item>
        </a-form>
    </a-modal>

    <!-- Add Filter Modal -->
    <a-modal
        v-model:visible="filterModalVisible"
        title="Add Saved Filter"
        @ok="saveFilter"
        :confirmLoading="savingFilter"
        destroyOnClose
        class="premium-modal"
    >
        <a-form layout="vertical">
            <a-form-item label="Filter Name" required>
                <a-input v-model:value="filterForm.name" placeholder="e.g. My P1 Tasks, Project X Review" />
            </a-form-item>
            
            <a-form-item label="Filter Criteria - Priority">
                <a-select v-model:value="filterForm.criteria.priority" placeholder="Any Priority" allowClear>
                    <a-select-option value="P1">P1 (Critical)</a-select-option>
                    <a-select-option value="P2">P2 (High)</a-select-option>
                    <a-select-option value="P3">P3 (Medium)</a-select-option>
                    <a-select-option value="P4">P4 (Low)</a-select-option>
                </a-select>
            </a-form-item>

            <a-form-item label="Filter Criteria - Status">
                <a-select v-model:value="filterForm.criteria.status" placeholder="Any Status" allowClear>
                    <a-select-option value="pending">Pending</a-select-option>
                    <a-select-option value="in_progress">In Progress</a-select-option>
                    <a-select-option value="under_review">Under Review</a-select-option>
                    <a-select-option value="completed">Completed</a-select-option>
                </a-select>
            </a-form-item>

            <a-form-item label="Filter Criteria - Project">
                <a-select v-model:value="filterForm.criteria.x_project_id" placeholder="Any Project" allowClear>
                    <a-select-option v-for="proj in projects" :key="proj.xid" :value="proj.xid">
                        {{ proj.name }}
                    </a-select-option>
                </a-select>
            </a-form-item>
        </a-form>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { 
    PlusOutlined, 
    DeleteOutlined, 
    EditOutlined, 
    SlidersOutlined, 
    TagOutlined, 
    TeamOutlined, 
    UserOutlined,
    InfoCircleOutlined
} from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';
import common from '../../../common/composable/common';

export default defineComponent({
    components: {
        AdminPageHeader,
        PlusOutlined,
        DeleteOutlined,
        EditOutlined,
        SlidersOutlined,
        TagOutlined,
        TeamOutlined,
        UserOutlined,
        InfoCircleOutlined
    },
    setup() {
        const router = useRouter();
        const { user } = common();

        const savedFilters = ref([]);
        const labels = ref([]);
        const projects = ref([]);

        // Label management state
        const labelModalVisible = ref(false);
        const editingLabel = ref(null);
        const savingLabel = ref(false);
        const labelForm = ref({
            name: '',
            color: '#ef4444'
        });

        // Filter management state
        const filterModalVisible = ref(false);
        const savingFilter = ref(false);
        const filterForm = ref({
            name: '',
            criteria: {
                priority: undefined,
                status: undefined,
                x_project_id: undefined
            }
        });

        // Colors preset
        const colorPresets = [
            { name: 'Red', value: '#ef4444' },
            { name: 'Orange', value: '#f97316' },
            { name: 'Yellow', value: '#eab308' },
            { name: 'Green', value: '#22c55e' },
            { name: 'Teal', value: '#14b8a6' },
            { name: 'Blue', value: '#3b82f6' },
            { name: 'Purple', value: '#a855f7' },
            { name: 'Grey', value: '#6b7280' }
        ];

        // Fetch data
        const fetchSavedFilters = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/saved-filters');
                savedFilters.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchLabels = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/labels');
                labels.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchProjects = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        // Open Filter modal
        const openAddFilterModal = () => {
            filterForm.value = {
                name: '',
                criteria: {
                    priority: undefined,
                    status: undefined,
                    x_project_id: undefined
                }
            };
            filterModalVisible.value = true;
        };

        // Save Saved Filter
        const saveFilter = async () => {
            if (!filterForm.value.name.trim()) {
                message.warning('Please enter filter name');
                return;
            }

            savingFilter.value = true;
            try {
                // Build filter criteria object
                const filterCriteria = {};
                if (filterForm.value.criteria.priority) filterCriteria.priority = filterForm.value.criteria.priority;
                if (filterForm.value.criteria.status) filterCriteria.status = filterForm.value.criteria.status;
                if (filterForm.value.criteria.x_project_id) filterCriteria.x_project_id = filterForm.value.criteria.x_project_id;

                await axiosAdmin.post('/enterprise-tasks/saved-filters', {
                    name: filterForm.value.name,
                    filter_criteria: filterCriteria
                });
                message.success('Filter saved successfully');
                filterModalVisible.value = false;
                fetchSavedFilters();
            } catch (error) {
                console.error(error);
                message.error('Error saving filter');
            } finally {
                savingFilter.value = false;
            }
        };

        // Delete Filter
        const deleteFilter = (xid) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this filter?',
                okText: 'Delete',
                okType: 'danger',
                cancelText: 'Cancel',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/saved-filters/${xid}`);
                        message.success('Filter deleted');
                        fetchSavedFilters();
                    } catch (error) {
                        console.error(error);
                        message.error('Error deleting filter');
                    }
                }
            });
        };

        // Apply saved filter and redirect
        const applyFilter = (filter) => {
            router.push({
                name: 'admin.enterprise_tasks.list',
                query: filter.filter_criteria
            });
        };

        // Format criteria object to readable text
        const formatCriteria = (criteria) => {
            if (!criteria || Object.keys(criteria).length === 0) return 'No conditions';
            const parts = [];
            if (criteria.priority) parts.push(`Priority: ${criteria.priority}`);
            if (criteria.status) parts.push(`Status: ${criteria.status}`);
            if (criteria.x_project_id) {
                const proj = projects.value.find(p => p.xid === criteria.x_project_id);
                parts.push(`Project: ${proj ? proj.name : 'Selected'}`);
            }
            return parts.join(' ‧ ');
        };

        // Label management
        const openAddLabelModal = () => {
            editingLabel.value = null;
            labelForm.value = {
                name: '',
                color: '#ef4444'
            };
            labelModalVisible.value = true;
        };

        const openEditLabelModal = (label) => {
            editingLabel.value = label;
            labelForm.value = {
                name: label.name,
                color: label.color
            };
            labelModalVisible.value = true;
        };

        const saveLabel = async () => {
            if (!labelForm.value.name.trim()) {
                message.warning('Please enter label name');
                return;
            }

            savingLabel.value = true;
            try {
                if (editingLabel.value) {
                    await axiosAdmin.put(`/enterprise-tasks/labels/${editingLabel.value.xid}`, {
                        name: labelForm.value.name,
                        color: labelForm.value.color
                    });
                    message.success('Label updated successfully');
                } else {
                    await axiosAdmin.post('/enterprise-tasks/labels', {
                        name: labelForm.value.name,
                        color: labelForm.value.color
                    });
                    message.success('Label created successfully');
                }
                labelModalVisible.value = false;
                fetchLabels();
            } catch (error) {
                console.error(error);
                message.error('Error saving label');
            } finally {
                savingLabel.value = false;
            }
        };

        const deleteLabel = (xid) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this label?',
                content: 'This will remove the label from all tasks too.',
                okText: 'Delete',
                okType: 'danger',
                cancelText: 'Cancel',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/labels/${xid}`);
                        message.success('Label deleted');
                        fetchLabels();
                    } catch (error) {
                        console.error(error);
                        message.error('Error deleting label');
                    }
                }
            });
        };

        onMounted(() => {
            fetchSavedFilters();
            fetchLabels();
            fetchProjects();
        });

        return {
            user,
            savedFilters,
            labels,
            projects,
            colorPresets,

            // Filter
            filterModalVisible,
            savingFilter,
            filterForm,
            openAddFilterModal,
            saveFilter,
            deleteFilter,
            applyFilter,
            formatCriteria,

            // Label
            labelModalVisible,
            editingLabel,
            savingLabel,
            labelForm,
            openAddLabelModal,
            openEditLabelModal,
            saveLabel,
            deleteLabel
        };
    }
});
</script>

<style scoped>
.filters-labels-container {
    margin: 20px 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.premium-card {
    border-radius: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
    background: #ffffff;
    padding: 12px 8px;
    min-height: 520px;
}

/* User Profile Header */
.user-profile-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 4px 8px;
}

.user-avatar {
    border: 2px solid #f1f5f9;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.25;
}

.user-role {
    font-size: 12px;
    color: #64748b;
}

.team-header {
    padding-top: 10px;
}

.team-avatar {
    background-color: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Section Header */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 0 8px;
}

.flex-column-start {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
}

.header-main-title {
    display: flex;
    flex-direction: column;
}

.section-main-heading {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.section-sub-heading {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748b;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-title h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.utilization-tag {
    font-size: 11px;
    border-radius: 9999px;
    padding: 0 8px;
    line-height: 18px;
}

.add-btn {
    color: #db4c3f;
    font-weight: 500;
    padding: 0;
}

.add-btn:hover {
    color: #b93a30;
}

.premium-add-btn {
    background-color: #db4c3f;
    border-color: #db4c3f;
    border-radius: 8px;
    font-weight: 500;
}

.premium-add-btn:hover, .premium-add-btn:focus {
    background-color: #b93a30;
    border-color: #b93a30;
}

/* Lists */
.filters-list, .labels-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 120px;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 14px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
    cursor: pointer;
}

.item-row:hover {
    background: #f1f5f9;
    border-color: #e2e8f0;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.item-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.item-icon {
    font-size: 16px;
}

.text-coral {
    color: #db4c3f;
}

.item-details {
    display: flex;
    flex-direction: column;
}

.item-name {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.font-medium {
    font-weight: 550;
}

.item-subtext {
    font-size: 11px;
    color: #64748b;
    margin-top: 2px;
}

.color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

/* Empty states */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: #64748b;
    font-size: 13px;
    text-align: center;
}

.empty-placeholder-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    background-color: #fafbfd;
}

.labels-empty {
    min-height: 280px;
    margin-top: 8px;
}

.placeholder-text {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

/* Color Selector */
.color-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 8px 0;
}

.color-option {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.color-option:hover {
    transform: scale(1.1);
}

.color-option.active {
    transform: scale(1.1);
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #db4c3f;
}

.check-mark {
    color: #ffffff;
    font-weight: bold;
    font-size: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.premium-modal :deep(.ant-modal-content) {
    border-radius: 16px;
    overflow: hidden;
}

.premium-modal :deep(.ant-modal-header) {
    border-bottom: 1px solid #f1f5f9;
}

.premium-modal :deep(.ant-modal-footer) {
    border-top: 1px solid #f1f5f9;
}
</style>
