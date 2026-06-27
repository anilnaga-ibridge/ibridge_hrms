<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Projects Hub" class="p-0">
                <template #extra>
                    <a-button type="primary" @click="openCreateModal">
                        <PlusOutlined /> Create Project
                    </a-button>
                </template>
            </a-page-header>
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Enterprise Tasks</a-breadcrumb-item>
                <a-breadcrumb-item>Projects</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <div style="margin: 20px 16px;">
        <a-row :gutter="[16, 16]">
            <a-col v-for="proj in projects" :key="proj.xid" :xs="24" :sm="12" :md="8">
                <a-card :bordered="false" class="project-card" style="border-radius: 12px; position: relative; overflow: hidden; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                    <!-- Colored border indicator -->
                    <div :style="{ background: proj.color ?? '#3b82f6', height: '6px', position: 'absolute', top: 0, left: 0, right: 0 }" />
                    
                    <div style="padding: 12px 0 0 0;">
                        <a-row justify="space-between" align="middle">
                            <a-col>
                                <span style="font-size: 16px; font-weight: bold; color: #1f2937;">
                                    <router-link :to="{ name: 'admin.enterprise_tasks.project_details', params: { id: proj.xid } }">
                                        {{ proj.name }}
                                    </router-link>
                                </span>
                            </a-col>
                            <a-col>
                                <a-dropdown :trigger="['click']">
                                    <a style="color: #9ca3af; font-size: 18px;" @click.prevent><MoreOutlined /></a>
                                    <template #overlay>
                                        <a-menu>
                                            <a-menu-item @click="openEditModal(proj)">Edit</a-menu-item>
                                            <a-menu-item @click="openMembersModal(proj)">Manage Members</a-menu-item>
                                            <a-menu-item @click="confirmDelete(proj)" danger>Delete</a-menu-item>
                                        </a-menu>
                                    </template>
                                </a-dropdown>
                            </a-col>
                        </a-row>

                        <div style="margin-top: 8px; font-size: 13px; color: #6b7280; min-height: 40px;">
                            {{ proj.description ? (proj.description.length > 80 ? proj.description.substring(0, 80) + '...' : proj.description) : 'No description provided.' }}
                        </div>

                        <div style="margin-top: 12px;">
                            <a-row :gutter="12">
                                <a-col :span="12">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Owner</div>
                                    <div style="font-weight: 500; font-size: 13px;">{{ proj.owner ? proj.owner.name : 'Unassigned' }}</div>
                                </a-col>
                                <a-col :span="12" style="text-align: right;">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Visibility</div>
                                    <a-tag :color="proj.visibility === 'public' ? 'green' : (proj.visibility === 'team' ? 'blue' : 'orange')">
                                        {{ proj.visibility }}
                                    </a-tag>
                                </a-col>
                            </a-row>
                        </div>

                        <div style="margin-top: 16px;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                                <span>Progress</span>
                                <span>{{ proj.productivity_percentage }}%</span>
                            </div>
                            <a-progress :percent="proj.productivity_percentage" :stroke-color="proj.color" size="small" :show-info="false" />
                        </div>

                        <div style="margin-top: 16px; border-top: 1px solid #f3f4f6; padding-top: 12px; display: flex; justify-content: space-between; font-size: 12px; color: #6b7280;">
                            <span>Tasks: <strong>{{ proj.total_tasks }}</strong></span>
                            <span>Pending: <strong>{{ proj.pending_tasks }}</strong></span>
                            <span>Members: <strong>{{ proj.members_count }}</strong></span>
                        </div>
                    </div>
                </a-card>
            </a-col>
        </a-row>

        <!-- Empty State -->
        <div v-if="projects.length === 0" style="text-align: center; padding: 60px 0;">
            <a-empty description="No projects found. Create one to get started!" />
        </div>

        <!-- CREATE / EDIT MODAL -->
        <a-modal v-model:open="modalOpen" :title="editMode ? 'Edit Project' : 'Create Project'" @ok="handleModalOk">
            <a-form layout="vertical">
                <a-form-item label="Project Name" required>
                    <a-input v-model:value="form.name" placeholder="Enter project name" />
                </a-form-item>
                <a-form-item label="Project Color">
                    <a-row :gutter="12">
                        <a-col :span="4">
                            <input type="color" v-model="form.color" style="width: 100%; height: 32px; border: 1px solid #d9d9d9; border-radius: 4px;" />
                        </a-col>
                        <a-col :span="20">
                            <a-input v-model:value="form.color" placeholder="HEX color code" />
                        </a-col>
                    </a-row>
                </a-form-item>
                <a-form-item label="Visibility">
                    <a-select v-model:value="form.visibility">
                        <a-select-option value="public">Public</a-select-option>
                        <a-select-option value="team">Team</a-select-option>
                        <a-select-option value="private">Private</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Status">
                    <a-select v-model:value="form.status">
                        <a-select-option value="active">Active</a-select-option>
                        <a-select-option value="completed">Completed</a-select-option>
                        <a-select-option value="archived">Archived</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Description">
                    <a-textarea v-model:value="form.description" placeholder="Project details and scope..." :rows="4" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- MEMBERS MODAL -->
        <a-modal v-model:open="membersModalOpen" title="Manage Project Members" @ok="handleMembersOk">
            <div style="margin-bottom: 16px;">
                <a-row :gutter="12">
                    <a-col :span="16">
                        <a-select
                            v-model:value="newMember.x_user_id"
                            show-search
                            placeholder="Search employee..."
                            style="width: 100%"
                            :filter-option="filterUserOption"
                        >
                            <a-select-option v-for="user in usersList" :key="user.xid" :value="user.xid">
                                {{ user.name }} ({{ user.email }})
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :span="6">
                        <a-select v-model:value="newMember.role" style="width: 100%">
                            <a-select-option value="admin">Admin</a-select-option>
                            <a-select-option value="member">Member</a-select-option>
                            <a-select-option value="read_only">Read Only</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col :span="2">
                        <a-button type="primary" @click="addMemberRow"><PlusOutlined /></a-button>
                    </a-col>
                </a-row>
            </div>

            <a-list bordered :data-source="selectedProjectMembers">
                <template #renderItem="{ item, index }">
                    <a-list-item>
                        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <div>
                                <span style="font-weight: 500;">{{ item.user ? item.user.name : item.name }}</span>
                                <span style="margin-left: 8px; color: #9ca3af; font-size: 11px;">({{ item.role }})</span>
                            </div>
                            <div>
                                <a-button type="link" danger @click="removeMemberRow(index)">Remove</a-button>
                            </div>
                        </div>
                    </a-list-item>
                </template>
            </a-list>
        </a-modal>
    </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue';
import AdminPageHeader from '../../../common/layouts/AdminPageHeader.vue';
import { PlusOutlined, MoreOutlined } from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';

export default defineComponent({
    components: {
        AdminPageHeader,
        PlusOutlined,
        MoreOutlined
    },
    setup() {
        const projects = ref([]);
        const usersList = ref([]);
        const modalOpen = ref(false);
        const editMode = ref(false);
        const editingProjectId = ref(null);
        
        const form = ref({
            name: '',
            color: '#3b82f6',
            visibility: 'public',
            status: 'active',
            description: ''
        });

        // Members modal state
        const membersModalOpen = ref(false);
        const selectedProject = ref(null);
        const selectedProjectMembers = ref([]);
        const newMember = ref({
            x_user_id: undefined,
            role: 'member'
        });

        const fetchProjects = async () => {
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/projects');
                projects.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchUsers = async () => {
            try {
                const response = await axiosAdmin.get('/get-all-employees');
                usersList.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const openCreateModal = () => {
            editMode.value = false;
            form.value = {
                name: '',
                color: '#3b82f6',
                visibility: 'public',
                status: 'active',
                description: ''
            };
            modalOpen.value = true;
        };

        const openEditModal = (project) => {
            editMode.value = true;
            editingProjectId.value = project.xid;
            form.value = {
                name: project.name,
                color: project.color ?? '#3b82f6',
                visibility: project.visibility,
                status: project.status,
                description: project.description
            };
            modalOpen.value = true;
        };

        const handleModalOk = async () => {
            if (!form.value.name) {
                message.error('Project Name is required');
                return;
            }

            try {
                if (editMode.value) {
                    await axiosAdmin.put(`/enterprise-tasks/projects/${editingProjectId.value}`, form.value);
                    message.success('Project updated successfully');
                } else {
                    await axiosAdmin.post('/enterprise-tasks/projects', form.value);
                    message.success('Project created successfully');
                }
                modalOpen.value = false;
                fetchProjects();
            } catch (error) {
                console.error(error);
                message.error('Error saving project');
            }
        };

        const confirmDelete = (project) => {
            Modal.confirm({
                title: 'Are you sure you want to delete this project?',
                content: 'This will delete all tasks and sections inside this project permanently.',
                okText: 'Yes, Delete',
                okType: 'danger',
                cancelText: 'Cancel',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/projects/${project.xid}`);
                        message.success('Project deleted successfully');
                        fetchProjects();
                    } catch (error) {
                        console.error(error);
                        message.error('Error deleting project');
                    }
                }
            });
        };

        // Project Members Management
        const openMembersModal = (project) => {
            selectedProject.value = project;
            selectedProjectMembers.value = project.members.map(m => ({
                x_user_id: m.x_user_id,
                name: m.user ? m.user.name : 'Unknown User',
                role: m.role,
                user: m.user
            }));
            newMember.value = {
                x_user_id: undefined,
                role: 'member'
            };
            membersModalOpen.value = true;
        };

        const filterUserOption = (input, option) => {
            return option.children[0].children.join('').toLowerCase().indexOf(input.toLowerCase()) >= 0;
        };

        const addMemberRow = () => {
            if (!newMember.value.x_user_id) {
                message.warning('Please select an employee');
                return;
            }
            // Check if already added
            if (selectedProjectMembers.value.some(m => m.x_user_id === newMember.value.x_user_id)) {
                message.warning('Employee is already added');
                return;
            }
            const foundUser = usersList.value.find(u => u.xid === newMember.value.x_user_id);
            selectedProjectMembers.value.push({
                x_user_id: newMember.value.x_user_id,
                name: foundUser ? foundUser.name : '',
                role: newMember.value.role,
                user: foundUser
            });
            newMember.value.x_user_id = undefined;
        };

        const removeMemberRow = (index) => {
            selectedProjectMembers.value.splice(index, 1);
        };

        const handleMembersOk = async () => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/projects/${selectedProject.value.xid}/members`, {
                    members: selectedProjectMembers.value
                });
                message.success('Project members updated successfully');
                membersModalOpen.value = false;
                fetchProjects();
            } catch (error) {
                console.error(error);
                message.error('Error updating project members');
            }
        };

        onMounted(() => {
            fetchProjects();
            fetchUsers();
            window.addEventListener('task-created', fetchProjects);
        });

        onUnmounted(() => {
            window.removeEventListener('task-created', fetchProjects);
        });

        return {
            projects,
            usersList,
            modalOpen,
            editMode,
            form,
            openCreateModal,
            openEditModal,
            handleModalOk,
            confirmDelete,
            membersModalOpen,
            selectedProjectMembers,
            newMember,
            openMembersModal,
            filterUserOption,
            addMemberRow,
            removeMemberRow,
            handleMembersOk
        };
    }
});
</script>
