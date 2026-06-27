<template>
    <a-drawer
        :title="task ? `${task.task_number} - ${task.title}` : 'Loading...'"
        :visible="visible"
        @close="$emit('close')"
        width="800"
        placement="right"
        destroyOnClose
    >
        <template #extra>
            <a-space v-if="task">
                <a-button type="dashed" @click="handleDuplicate">Duplicate</a-button>
                <a-button danger @click="handleDelete">Delete</a-button>
            </a-space>
        </template>

        <div v-if="task" style="padding-bottom: 40px;">
            <a-row :gutter="24">
                <!-- Left panel: descriptions, subtasks, checklists, comments wrapped in tabs -->
                <a-col :span="16">
                    <a-tabs v-model:activeKey="activeTabKey">
                        <a-tab-pane key="details" tab="Details">
                            <div style="padding-top: 12px;">
                                <!-- Title & Edit mode -->
                                <div style="margin-bottom: 20px;">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Task Title</div>
                                    <a-input v-model:value="editForm.title" style="font-size: 16px; font-weight: 500;" @blur="quickUpdate" />
                                </div>

                                <!-- Rich Text / Text Description -->
                                <div style="margin-bottom: 24px;">
                                    <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Description</div>
                                    <a-textarea v-model:value="editForm.description" placeholder="Add a description..." :rows="4" @blur="quickUpdate" />
                                </div>

                                <!-- SUBTASKS SECTION -->
                                <div style="margin-bottom: 28px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                    <a-row justify="space-between" align="middle" style="margin-bottom: 12px;">
                                        <a-col><span style="font-size: 14px; font-weight: bold; color: #374151;">Subtasks</span></a-col>
                                        <a-col>
                                            <a-button size="small" type="primary" ghost @click="subtaskInputVisible = !subtaskInputVisible">
                                                <PlusOutlined /> Add Subtask
                                            </a-button>
                                        </a-col>
                                    </a-row>

                                    <!-- Subtask progress bar -->
                                    <div v-if="task.subtasks && task.subtasks.length > 0" style="margin-bottom: 12px;">
                                        <a-progress :percent="task.completion_percentage" size="small" />
                                    </div>

                                    <!-- Add subtask form inline -->
                                    <div v-if="subtaskInputVisible" style="margin-bottom: 16px; padding: 12px; background: #fafafa; border-radius: 8px;">
                                        <a-form layout="vertical">
                                            <a-form-item label="Subtask Title" required>
                                                <a-input v-model:value="newSubtask.title" placeholder="Subtask name..." />
                                            </a-form-item>
                                            <a-row :gutter="12">
                                                <a-col :span="12">
                                                    <a-form-item label="Priority">
                                                        <a-select v-model:value="newSubtask.priority" style="width: 100%">
                                                            <a-select-option value="P1">P1</a-select-option>
                                                            <a-select-option value="P2">P2</a-select-option>
                                                            <a-select-option value="P3">P3</a-select-option>
                                                            <a-select-option value="P4">P4</a-select-option>
                                                        </a-select>
                                                    </a-form-item>
                                                </a-col>
                                                <a-col :span="12">
                                                    <a-form-item label="Due Date">
                                                        <a-date-picker v-model:value="newSubtask.due_date" style="width: 100%" value-format="YYYY-MM-DD" />
                                                    </a-form-item>
                                                </a-col>
                                            </a-row>
                                            <a-button size="small" type="primary" @click="createSubtask">Create</a-button>
                                            <a-button size="small" style="margin-left: 8px;" @click="subtaskInputVisible = false">Cancel</a-button>
                                        </a-form>
                                    </div>

                                        <!-- Subtasks list -->
                                        <div class="subtasks-list">
                                            <div v-for="sub in task.subtasks" :key="sub.xid" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border: 1px solid #f3f4f6; border-radius: 6px; margin-bottom: 6px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <a-checkbox :checked="sub.status === 'completed'" @change="toggleSubtaskStatus(sub)" />
                                                    <span :style="{ textDecoration: sub.status === 'completed' ? 'line-through' : 'none', color: sub.status === 'completed' ? '#9ca3af' : '#374151' }">
                                                        {{ sub.title }}
                                                    </span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <a-tag :color="getPriorityColor(sub.priority)" size="small">{{ sub.priority }}</a-tag>
                                                    <a-button type="link" size="small" @click="convertSubtask(sub)">Convert to Task</a-button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CHECKLISTS SECTION -->
                                    <div style="margin-bottom: 28px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                        <a-row justify="space-between" align="middle" style="margin-bottom: 12px;">
                                            <a-col><span style="font-size: 14px; font-weight: bold; color: #374151;">Checklists</span></a-col>
                                            <a-col>
                                                <a-button size="small" type="dashed" @click="showAddChecklistModal">
                                                    <PlusOutlined /> Add Checklist
                                                </a-button>
                                            </a-col>
                                        </a-row>

                                        <div v-for="chk in task.checklists" :key="chk.xid" style="margin-bottom: 20px; background: #fafafa; padding: 12px; border-radius: 8px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <span style="font-weight: bold; color: #4b5563;">{{ chk.name }} ({{ chk.completion_percentage }}%)</span>
                                                <a-button size="small" type="link" danger @click="deleteChecklist(chk)">Delete</a-button>
                                            </div>
                                            
                                            <a-progress :percent="chk.completion_percentage" size="small" style="margin-bottom: 12px;" />

                                            <!-- Checklist items list -->
                                            <div v-for="item in chk.items" :key="item.xid" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <a-checkbox :checked="item.is_completed" @change="toggleChecklistItem(item)" />
                                                    <span>{{ item.name }}</span>
                                                </div>
                                                <a-button size="small" type="link" danger @click="deleteChecklistItem(item)">Delete</a-button>
                                            </div>

                                            <!-- Add Checklist Item -->
                                            <div style="margin-top: 8px;">
                                                <a-input-search
                                                    placeholder="Add checklist item..."
                                                    enter-button="Add"
                                                    size="small"
                                                    @search="(val) => addChecklistItem(chk, val)"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ATTACHMENTS SECTION -->
                                    <div style="margin-bottom: 28px; border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                        <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Attachments</div>
                                        <a-upload
                                            name="file"
                                            :customRequest="handleFileUpload"
                                            :showUploadList="false"
                                        >
                                            <a-button type="dashed" style="width: 100%;"><UploadOutlined /> Click to Upload File (Max 10MB)</a-button>
                                        </a-upload>

                                        <div style="margin-top: 12px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                            <div v-for="att in task.attachments" :key="att.xid" style="border: 1px solid #e5e7eb; padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 8px; position: relative;">
                                                <div v-if="isImage(att.file_type)" style="width: 48px; height: 48px; border-radius: 4px; overflow: hidden; background: #eee;">
                                                    <img :src="att.file_url" style="width: 100%; height: 100%; object-fit: cover;" />
                                                </div>
                                                <div v-else style="width: 48px; height: 48px; border-radius: 4px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 10px; color: #6b7280; text-transform: uppercase;">
                                                    {{ att.file_type }}
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-weight: 500; font-size: 12px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <a :href="att.file_url" target="_blank">{{ att.file_name }}</a>
                                                    </div>
                                                    <div style="font-size: 10px; color: #9ca3af;">{{ (att.file_size / 1024).toFixed(1) }} KB</div>
                                                </div>
                                                <a-button type="link" danger size="small" style="position: absolute; right: 4px; top: 4px;" @click="deleteAttachment(att)">Delete</a-button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- COMMENTS SECTION -->
                                    <div style="border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                        <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Comments</div>
                                        
                                        <!-- Add Comment -->
                                        <div style="margin-bottom: 20px;">
                                            <a-textarea v-model:value="commentText" placeholder="Write a comment... (use @name to mention)" :rows="3" />
                                            <div style="margin-top: 8px; text-align: right;">
                                                <a-button type="primary" @click="submitComment">Comment</a-button>
                                            </div>
                                        </div>

                                        <!-- Comments list -->
                                        <a-list item-layout="vertical" :data-source="task.comments">
                                            <template #renderItem="{ item }">
                                                <a-list-item style="padding: 12px 0;">
                                                    <a-comment :author="item.user ? item.user.name : 'Unknown User'" :avatar="item.user ? item.user.profile_image_url : ''">
                                                        <template #content>
                                                            <div style="white-space: pre-wrap;">{{ item.comment }}</div>
                                                            <!-- Reactions Bar -->
                                                            <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                                                                <a-tag v-for="r in item.reactions" :key="r.emoji" style="cursor: pointer;" @click="toggleEmojiReaction(item, r.emoji)">
                                                                    {{ r.emoji }} {{ r.count }}
                                                                </a-tag>
                                                                <!-- Add reaction button -->
                                                                <a-dropdown :trigger="['click']">
                                                                    <a-button size="small" shape="circle"><SmileOutlined /></a-button>
                                                                    <template #overlay>
                                                                        <a-menu @click="(e) => toggleEmojiReaction(item, e.key)">
                                                                            <a-menu-item key="👍">👍 Like</a-menu-item>
                                                                            <a-menu-item key="❤️">❤️ Love</a-menu-item>
                                                                            <a-menu-item key="😂">😂 Laugh</a-menu-item>
                                                                            <a-menu-item key="😮">😮 Surprised</a-menu-item>
                                                                            <a-menu-item key="😢">😢 Sad</a-menu-item>
                                                                            <a-menu-item key="🔥">🔥 Hot</a-menu-item>
                                                                        </a-menu>
                                                                    </template>
                                                                </a-dropdown>
                                                            </div>
                                                        </template>
                                                        <template #datetime>
                                                            <span>{{ formatDateTime(item.created_at) }}</span>
                                                            <a-divider type="vertical" />
                                                            <a-button type="link" size="small" danger @click="deleteComment(item)" style="padding: 0;">Delete</a-button>
                                                        </template>
                                                    </a-comment>
                                                </a-list-item>
                                            </template>
                                        </a-list>
                                    </div>
                                </div>
                            </a-tab-pane>

                            <!-- DEPENDENCIES TAB -->
                            <a-tab-pane key="dependencies" tab="Dependencies">
                                <div style="padding: 16px 0;">
                                    <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Prerequisites (Task must depend on)</div>
                                    
                                    <a-list v-if="dependencies && dependencies.length > 0" item-layout="horizontal" :data-source="dependencies" style="margin-bottom: 24px;">
                                        <template #renderItem="{ item }">
                                            <a-list-item>
                                                <template #actions>
                                                    <a-button type="link" danger size="small" @click="deleteDependency(item)">Delete</a-button>
                                                </template>
                                                <a-list-item-meta>
                                                    <template #title>
                                                        <span style="font-weight: 500;">
                                                            {{ item.depends_on_task ? `${item.depends_on_task.task_number} - ${item.depends_on_task.title}` : 'Unknown Task' }}
                                                        </span>
                                                    </template>
                                                    <template #description>
                                                        <span style="font-size: 12px; color: #4b5563;">
                                                            Type: <a-tag color="blue">{{ getDependencyTypeName(item.dependency_type) }}</a-tag>
                                                            <span v-if="item.lag_days > 0" style="margin-left: 8px;">Lag: {{ item.lag_days }} days</span>
                                                        </span>
                                                    </template>
                                                </a-list-item-meta>
                                            </a-list-item>
                                        </template>
                                    </a-list>
                                    <a-empty v-else description="No prerequisites defined for this task" style="margin-bottom: 24px;" />

                                    <div style="border-top: 1px solid #f3f4f6; padding-top: 20px;">
                                        <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Add Prerequisite Dependency</div>
                                        
                                        <a-form layout="vertical">
                                            <a-form-item label="Select Task" required>
                                                <a-select
                                                    v-model:value="dependencyForm.x_depends_on_task_id"
                                                    show-search
                                                    placeholder="Search project tasks..."
                                                    option-filter-prop="title"
                                                    style="width: 100%"
                                                >
                                                    <a-select-option v-for="t in projectTasks" :key="t.xid" :value="t.xid" :title="t.title">
                                                        {{ t.task_number }} - {{ t.title }} ({{ t.status }})
                                                    </a-select-option>
                                                </a-select>
                                            </a-form-item>

                                            <a-row :gutter="12">
                                                <a-col :span="12">
                                                    <a-form-item label="Dependency Type" required>
                                                        <a-select v-model:value="dependencyForm.dependency_type" style="width: 100%">
                                                            <a-select-option value="finish_to_start">Finish to Start (FS)</a-select-option>
                                                            <a-select-option value="start_to_start">Start to Start (SS)</a-select-option>
                                                            <a-select-option value="finish_to_finish">Finish to Finish (FF)</a-select-option>
                                                            <a-select-option value="start_to_finish">Start to Finish (SF)</a-select-option>
                                                        </a-select>
                                                    </a-form-item>
                                                </a-col>
                                                <a-col :span="12">
                                                    <a-form-item label="Lag Days">
                                                        <a-input-number v-model:value="dependencyForm.lag_days" :min="0" style="width: 100%" />
                                                    </a-form-item>
                                                </a-col>
                                            </a-row>

                                            <a-button type="primary" @click="addDependency">Add Dependency</a-button>
                                        </a-form>
                                    </div>
                                </div>
                            </a-tab-pane>

                            <!-- TIME & WORKLOAD TAB -->
                            <a-tab-pane key="workload" tab="Time & Workload">
                                <div style="padding: 16px 0;">
                                    <!-- Summary cards -->
                                    <a-row :gutter="16" style="margin-bottom: 24px;">
                                        <a-col :span="8">
                                            <a-card size="small" style="text-align: center; background: #fafafa;">
                                                <a-statistic title="Estimated" :value="task.estimated_hours || 0" suffix="h" />
                                            </a-card>
                                        </a-col>
                                        <a-col :span="8">
                                            <a-card size="small" style="text-align: center; background: #f3e8ff;">
                                                <a-statistic title="Actual" :value="task.actual_hours || 0" suffix="h" valueStyle="color: #8b5cf6;" />
                                            </a-card>
                                        </a-col>
                                        <a-col :span="8">
                                            <a-card size="small" style="text-align: center;" :style="{ background: (task.estimated_hours - task.actual_hours) >= 0 ? '#f0fdf4' : '#fef2f2' }">
                                                <a-statistic 
                                                    title="Remaining" 
                                                    :value="Math.max(0, (task.estimated_hours || 0) - (task.actual_hours || 0))" 
                                                    suffix="h" 
                                                    :valueStyle="{ color: (task.estimated_hours - task.actual_hours) >= 0 ? '#16a34a' : '#dc2626' }"
                                                />
                                            </a-card>
                                        </a-col>
                                    </a-row>

                                    <!-- Timer / Actions -->
                                    <div style="margin-bottom: 24px; border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px;">
                                        <div style="font-weight: 500; margin-bottom: 12px;">Track Your Time</div>
                                        <div v-if="activeTimer" style="background: #fee2e2; border: 1px solid #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                                            <div style="font-weight: bold; color: #991b1b; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                <span class="timer-pulse"></span> Timer Running (Started: {{ formatDateTime(activeTimer.start_time) }})
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <a-input v-model:value="timeMemo" placeholder="Add memo..." size="small" />
                                                <a-button type="primary" danger size="small" @click="stopTimer">Stop</a-button>
                                            </div>
                                        </div>
                                        <div v-else style="display: flex; gap: 12px;">
                                            <a-button type="primary" @click="startTimer" style="flex: 1;">
                                                <PlayCircleOutlined /> Start Timer
                                            </a-button>
                                            <a-button type="dashed" @click="showLogTimeModal" style="flex: 1;">
                                                Log Manual Time
                                            </a-button>
                                        </div>
                                    </div>

                                    <!-- Assignee Workload Breakdown -->
                                    <div style="margin-bottom: 24px;">
                                        <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Time Spent by Member</div>
                                        <a-table 
                                            v-if="assigneeWorkload.length > 0" 
                                            :data-source="assigneeWorkload" 
                                            :columns="workloadColumns" 
                                            size="small" 
                                            :pagination="false" 
                                            rowKey="name"
                                        />
                                        <div v-else style="color: #9ca3af; font-style: italic; text-align: center; padding: 12px;">No time logged yet</div>
                                    </div>

                                    <!-- Detailed Time Log History -->
                                    <div>
                                        <div style="font-size: 14px; font-weight: bold; color: #374151; margin-bottom: 12px;">Time Log History</div>
                                        <a-list v-if="task.time_logs && task.time_logs.length > 0" item-layout="horizontal" :data-source="task.time_logs">
                                            <template #renderItem="{ item }">
                                                <a-list-item>
                                                    <a-list-item-meta>
                                                        <template #avatar>
                                                            <a-avatar :src="item.user ? item.user.profile_image_url : ''" />
                                                        </template>
                                                        <template #title>
                                                            <strong>{{ item.user ? item.user.name : 'Unknown User' }}</strong>
                                                            <span style="margin-left: 8px; font-weight: normal; color: #8b5cf6;">{{ (item.duration_minutes / 60).toFixed(2) }}h</span>
                                                        </template>
                                                        <template #description>
                                                            <div>{{ item.memo || 'No memo provided' }}</div>
                                                            <div style="font-size: 10px; color: #9ca3af;">
                                                                {{ formatDateTime(item.start_time) }} - {{ item.end_time ? formatDateTime(item.end_time) : 'Active' }}
                                                            </div>
                                                        </template>
                                                    </a-list-item-meta>
                                                </a-list-item>
                                            </template>
                                        </a-list>
                                        <div v-else style="color: #9ca3af; font-style: italic; text-align: center; padding: 12px;">No detailed history</div>
                                    </div>
                                </div>
                            </a-tab-pane>
                        </a-tabs>
                    </a-col>

                <!-- Right panel: task parameters, timeline, timers -->
                <a-col :span="8" style="border-left: 1px solid #f3f4f6; padding-left: 16px;">
                    <!-- Timers & Time Log -->
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Time Tracking</div>
                        
                        <div v-if="activeTimer" style="background: #fee2e2; border: 1px solid #fca5a5; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                            <div style="font-weight: bold; color: #991b1b; display: flex; align-items: center; gap: 8px;">
                                <span class="timer-pulse"></span> Timer Running
                            </div>
                            <div style="margin-top: 8px; display: flex; gap: 8px;">
                                <a-input v-model:value="timeMemo" placeholder="Add memo..." size="small" />
                                <a-button type="primary" danger size="small" @click="stopTimer">Stop</a-button>
                            </div>
                        </div>

                        <div v-else style="margin-bottom: 12px;">
                            <a-button type="primary" block @click="startTimer">
                                <PlayCircleOutlined /> Start Timer
                            </a-button>
                        </div>

                        <a-row :gutter="12">
                            <a-col :span="12">
                                <a-statistic title="Estimated Hours" :value="task.estimated_hours || 0" suffix="h" />
                            </a-col>
                            <a-col :span="12">
                                <a-statistic title="Actual Hours" :value="task.actual_hours || 0" suffix="h" valueStyle="color: #8b5cf6;" />
                            </a-col>
                        </a-row>

                        <!-- Log manual hours button -->
                        <a-button size="small" type="dashed" block style="margin-top: 12px;" @click="showLogTimeModal">Log Manual Time</a-button>
                    </div>

                    <!-- Status -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Status</div>
                        <a-select v-model:value="editForm.status" style="width: 100%" @change="quickUpdate">
                            <a-select-option value="pending">Pending</a-select-option>
                            <a-select-option value="in_progress">In Progress</a-select-option>
                            <a-select-option value="under_review">Under Review</a-select-option>
                            <a-select-option value="testing">Testing</a-select-option>
                            <a-select-option value="completed">Completed</a-select-option>
                            <a-select-option value="cancelled">Cancelled</a-select-option>
                            <a-select-option value="on_hold">On Hold</a-select-option>
                        </a-select>
                    </div>

                    <!-- Priority -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Priority</div>
                        <a-select v-model:value="editForm.priority" style="width: 100%" @change="quickUpdate">
                            <a-select-option value="P1">P1 Critical</a-select-option>
                            <a-select-option value="P2">P2 High</a-select-option>
                            <a-select-option value="P3">P3 Medium</a-select-option>
                            <a-select-option value="P4">P4 Low</a-select-option>
                        </a-select>
                    </div>

                    <!-- Assignees -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Assignees</div>
                        <a-select v-model:value="editForm.assignees_xids" mode="multiple" placeholder="Search assignees" style="width: 100%" @change="quickUpdate">
                            <a-select-option v-for="u in employees" :key="u.xid" :value="u.xid">{{ u.name }}</a-select-option>
                        </a-select>
                    </div>

                    <!-- Reviewers -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Reviewers</div>
                        <a-select v-model:value="editForm.reviewers_xids" mode="multiple" placeholder="Search reviewers" style="width: 100%" @change="quickUpdate">
                            <a-select-option v-for="u in employees" :key="u.xid" :value="u.xid">{{ u.name }}</a-select-option>
                        </a-select>
                    </div>

                    <!-- Watchers -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Watchers</div>
                        <a-select v-model:value="editForm.watchers_xids" mode="multiple" placeholder="Search watchers" style="width: 100%" @change="quickUpdate">
                            <a-select-option v-for="u in employees" :key="u.xid" :value="u.xid">{{ u.name }}</a-select-option>
                        </a-select>
                    </div>

                    <!-- Project Labels -->
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Labels</div>
                        <a-select v-model:value="editForm.labels_xids" mode="multiple" placeholder="Select labels" style="width: 100%" @change="quickUpdate">
                            <a-select-option v-for="l in labels" :key="l.xid" :value="l.xid">{{ l.name }}</a-select-option>
                        </a-select>
                    </div>

                    <!-- Dates -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase;">Due Date</div>
                        <a-date-picker v-model:value="editForm.due_date" style="width: 100%" value-format="YYYY-MM-DD" @change="quickUpdate" />
                    </div>

                    <!-- ESTIMATE -->
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 6px;">Estimated Hours</div>
                        <a-input-number v-model:value="editForm.estimated_hours" :min="0" style="width: 100%" @blur="quickUpdate" />
                    </div>

                    <!-- Audit Timeline -->
                    <div style="border-top: 1px solid #f3f4f6; padding-top: 16px;">
                        <div style="font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 10px;">Activity Timeline</div>
                        <a-timeline>
                            <a-timeline-item v-for="act in task.activities" :key="act.xid">
                                <div><strong>{{ act.user ? act.user.name : 'System' }}</strong></div>
                                <div style="font-size: 12px; color: #4b5563;">{{ act.description }}</div>
                                <div style="font-size: 10px; color: #9ca3af;">{{ formatDateTime(act.created_at) }}</div>
                            </a-timeline-item>
                        </a-timeline>
                    </div>
                </a-col>
            </a-row>
        </div>

        <!-- ADD CHECKLIST MODAL -->
        <a-modal v-model:open="checklistModalOpen" title="Add Checklist" @ok="createChecklist">
            <a-form layout="vertical">
                <a-form-item label="Checklist Name" required>
                    <a-input v-model:value="checklistForm.name" placeholder="e.g. Acceptance Criteria" />
                </a-form-item>
            </a-form>
        </a-modal>

        <!-- LOG TIME MODAL -->
        <a-modal v-model:open="logTimeModalOpen" title="Log Manual Time" @ok="logManualTime">
            <a-form layout="vertical">
                <a-form-item label="Duration (Minutes)" required>
                    <a-input-number v-model:value="logTimeForm.duration_minutes" :min="1" style="width: 100%" />
                </a-form-item>
                <a-form-item label="Date Logged" required>
                    <a-date-picker v-model:value="logTimeForm.log_date" style="width: 100%" value-format="YYYY-MM-DD" />
                </a-form-item>
                <a-form-item label="Memo / Work Details">
                    <a-textarea v-model:value="logTimeForm.memo" placeholder="Describe the work done..." :rows="3" />
                </a-form-item>
            </a-form>
        </a-modal>
    </a-drawer>
</template>

<script>
import { defineComponent, ref, watch, computed } from 'vue';
import {
    PlusOutlined, UploadOutlined, DragOutlined, SmileOutlined,
    PlayCircleOutlined, MoreOutlined
} from '@ant-design/icons-vue';
import { message, Modal } from 'ant-design-vue';

import dayjs from 'dayjs';

export default defineComponent({
    props: {
        visible: Boolean,
        taskXid: String
    },
    components: {
        PlusOutlined,
        UploadOutlined,
        DragOutlined,
        SmileOutlined,
        PlayCircleOutlined,
        MoreOutlined
    },
    setup(props, { emit }) {
        const task = ref(null);
        const employees = ref([]);
        const labels = ref([]);
        const activeTimer = ref(null);
        
        const activeTabKey = ref('details');
        const dependencies = ref([]);
        const projectTasks = ref([]);
        const dependencyForm = ref({
            x_depends_on_task_id: undefined,
            dependency_type: 'finish_to_start',
            lag_days: 0
        });

        const workloadColumns = [
            { title: 'Team Member', dataIndex: 'name', key: 'name' },
            { title: 'Time Logged (Hours)', dataIndex: 'hours', key: 'hours' }
        ];

        const assigneeWorkload = computed(() => {
            if (!task.value || !task.value.time_logs) return [];
            const breakdown = {};
            task.value.time_logs.forEach(log => {
                const uName = log.user ? log.user.name : 'Unknown';
                const duration = log.duration_minutes || 0;
                if (!breakdown[uName]) {
                    breakdown[uName] = 0;
                }
                breakdown[uName] += duration;
            });
            return Object.keys(breakdown).map(name => ({
                name,
                hours: (breakdown[name] / 60).toFixed(2)
            }));
        });
        
        const editForm = ref({
            title: '',
            description: '',
            status: '',
            priority: '',
            assignees_xids: [],
            reviewers_xids: [],
            watchers_xids: [],
            labels_xids: [],
            due_date: null,
            estimated_hours: 0
        });

        // Subtasks inline form
        const subtaskInputVisible = ref(false);
        const newSubtask = ref({
            title: '',
            priority: 'P3',
            due_date: null
        });

        // Checklist modal
        const checklistModalOpen = ref(false);
        const checklistForm = ref({ name: '' });

        // Time log modal
        const logTimeModalOpen = ref(false);
        const logTimeForm = ref({
            duration_minutes: 0,
            log_date: dayjs().format('YYYY-MM-DD'),
            memo: ''
        });

        // Comments
        const commentText = ref('');

        // Timers state
        const timeMemo = ref('');

        const fetchDependencies = async () => {
            if (!props.taskXid) return;
            try {
                const response = await axiosAdmin.get(`/enterprise-tasks/tasks/${props.taskXid}/dependencies`);
                dependencies.value = response;
            } catch (error) {
                console.error(error);
            }
        };

        const fetchProjectTasks = async () => {
            if (!task.value || !task.value.x_project_id) return;
            try {
                const response = await axiosAdmin.get('/enterprise-tasks/tasks', {
                    params: { x_project_id: task.value.x_project_id }
                });
                projectTasks.value = response.data.filter(t => t.xid !== props.taskXid);
            } catch (error) {
                console.error(error);
            }
        };

        const addDependency = async () => {
            if (!dependencyForm.value.x_depends_on_task_id) {
                message.warning('Please select a prerequisite task');
                return;
            }
            try {
                await axiosAdmin.post('/enterprise-tasks/dependencies', {
                    x_task_id: props.taskXid,
                    x_depends_on_task_id: dependencyForm.value.x_depends_on_task_id,
                    dependency_type: dependencyForm.value.dependency_type,
                    lag_days: dependencyForm.value.lag_days
                });
                message.success('Dependency added successfully');
                dependencyForm.value = {
                    x_depends_on_task_id: undefined,
                    dependency_type: 'finish_to_start',
                    lag_days: 0
                };
                fetchDependencies();
                emit('updated');
            } catch (error) {
                console.error(error);
                const errMsg = error.response?.data?.message || 'Error adding dependency';
                message.error(errMsg);
            }
        };

        const deleteDependency = async (dep) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/dependencies/${dep.xid}`);
                message.success('Dependency deleted');
                fetchDependencies();
                emit('updated');
            } catch (error) {
                console.error(error);
                message.error('Error deleting dependency');
            }
        };

        const getDependencyTypeName = (type) => {
            const names = {
                finish_to_start: 'Finish to Start (FS)',
                start_to_start: 'Start to Start (SS)',
                finish_to_finish: 'Finish to Finish (FF)',
                start_to_finish: 'Start to Finish (SF)'
            };
            return names[type] || type;
        };

        const fetchDetails = async () => {
            if (!props.taskXid) return;
            try {
                const response = await axiosAdmin.get(`/enterprise-tasks/tasks/${props.taskXid}`);
                task.value = response;
                
                // Initialize form values
                editForm.value = {
                    title: response.title,
                    description: response.description,
                    status: response.status,
                    priority: response.priority,
                    assignees_xids: response.assignees.map(a => a.xid),
                    reviewers_xids: response.reviewers.map(r => r.xid),
                    watchers_xids: response.watchers.map(w => w.xid),
                    labels_xids: response.labels.map(l => l.xid),
                    due_date: response.due_date,
                    estimated_hours: response.estimated_hours
                };

                // Check for running timer
                const activeTime = response.time_logs.find(log => !log.end_time);
                activeTimer.value = activeTime ? activeTime : null;

                // Load dependencies and project tasks
                fetchDependencies();
                fetchProjectTasks();

            } catch (error) {
                console.error(error);
                message.error('Error fetching task details');
            }
        };

        const fetchMeta = async () => {
            try {
                const [empRes, labelsRes] = await Promise.all([
                    axiosAdmin.get('/get-all-employees'),
                    axiosAdmin.get('/enterprise-tasks/labels')
                ]);
                employees.value = empRes;
                labels.value = labelsRes;
            } catch (error) {
                console.error(error);
            }
        };

        const quickUpdate = async () => {
            try {
                await axiosAdmin.put(`/enterprise-tasks/tasks/${props.taskXid}`, editForm.value);
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
                const errMsg = error.response?.data?.message || 'Error saving updates';
                message.error(errMsg);

                // Revert status / priority to original values if they failed
                if (task.value) {
                    editForm.value.status = task.value.status;
                    editForm.value.priority = task.value.priority;
                    editForm.value.title = task.value.title;
                    editForm.value.description = task.value.description;
                    editForm.value.due_date = task.value.due_date;
                    editForm.value.estimated_hours = task.value.estimated_hours;
                }
            }
        };

        // Duplicate/Delete
        const handleDuplicate = async () => {
            try {
                const response = await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/duplicate`);
                message.success('Task duplicated');
                emit('updated');
                emit('close');
            } catch (error) {
                console.error(error);
            }
        };

        const handleDelete = () => {
            Modal.confirm({
                title: 'Are you sure you want to delete this task?',
                okText: 'Delete',
                okType: 'danger',
                async onOk() {
                    try {
                        await axiosAdmin.delete(`/enterprise-tasks/tasks/${props.taskXid}`);
                        message.success('Task deleted');
                        emit('updated');
                        emit('close');
                    } catch (error) {
                        console.error(error);
                    }
                }
            });
        };

        // Subtasks
        const createSubtask = async () => {
            if (!newSubtask.value.title) {
                message.warning('Subtask title is required');
                return;
            }
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/subtasks`, newSubtask.value);
                message.success('Subtask added');
                subtaskInputVisible.value = false;
                newSubtask.value = { title: '', priority: 'P3', due_date: null };
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
            }
        };

        const toggleSubtaskStatus = async (sub) => {
            try {
                const status = sub.status === 'completed' ? 'pending' : 'completed';
                await axiosAdmin.put(`/enterprise-tasks/tasks/${sub.xid}`, {
                    ...sub,
                    status: status
                });
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
            }
        };

        const convertSubtask = async (sub) => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${sub.xid}/convert-subtask`);
                message.success('Subtask converted to standalone task');
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
            }
        };

        // Checklists
        const showAddChecklistModal = () => {
            checklistForm.value.name = '';
            checklistModalOpen.value = true;
        };

        const createChecklist = async () => {
            if (!checklistForm.value.name) return;
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/checklists`, checklistForm.value);
                checklistModalOpen.value = false;
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const deleteChecklist = async (chk) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/checklists/${chk.xid}`);
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const addChecklistItem = async (chk, name) => {
            if (!name) return;
            try {
                await axiosAdmin.post(`/enterprise-tasks/checklists/${chk.xid}/items`, { name });
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const toggleChecklistItem = async (item) => {
            try {
                await axiosAdmin.put(`/enterprise-tasks/checklist-items/${item.xid}`, {
                    is_completed: !item.is_completed
                });
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const deleteChecklistItem = async (item) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/checklist-items/${item.xid}`);
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        // Attachments
        const handleFileUpload = async (options) => {
            const formData = new FormData();
            formData.append('file', options.file);
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/attachments`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                message.success('File uploaded successfully');
                fetchDetails();
            } catch (error) {
                console.error(error);
                message.error('Error uploading file');
            }
        };

        const deleteAttachment = async (att) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/attachments/${att.xid}`);
                message.success('Attachment deleted');
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        // Comments & Reactions
        const submitComment = async () => {
            if (!commentText.value) return;
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/comments`, {
                    comment: commentText.value
                });
                commentText.value = '';
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const deleteComment = async (item) => {
            try {
                await axiosAdmin.delete(`/enterprise-tasks/comments/${item.xid}`);
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const toggleEmojiReaction = async (comment, emoji) => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/comments/${comment.xid}/reactions`, { emoji });
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        // Timer
        const startTimer = async () => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/time-logs/start`);
                message.success('Time tracking started');
                fetchDetails();
            } catch (error) {
                console.error(error);
            }
        };

        const stopTimer = async () => {
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/time-logs/stop`, {
                    memo: timeMemo.value
                });
                message.success('Time log saved successfully');
                timeMemo.value = '';
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
            }
        };

        const showLogTimeModal = () => {
            logTimeForm.value = {
                duration_minutes: 0,
                log_date: dayjs().format('YYYY-MM-DD'),
                memo: ''
            };
            logTimeModalOpen.value = true;
        };

        const logManualTime = async () => {
            if (logTimeForm.value.duration_minutes <= 0) return;
            try {
                await axiosAdmin.post(`/enterprise-tasks/tasks/${props.taskXid}/time-logs`, logTimeForm.value);
                message.success('Time logged manually');
                logTimeModalOpen.value = false;
                fetchDetails();
                emit('updated');
            } catch (error) {
                console.error(error);
            }
        };

        // Date Format
        const formatDateTime = (val) => {
            if (!val) return '';
            return dayjs(val).format('DD MMM YYYY, hh:mm A');
        };

        const isImage = (type) => {
            if (!type) return false;
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(type.toLowerCase());
        };

        const getPriorityColor = (priority) => {
            const colors = { P1: 'red', P2: 'orange', P3: 'blue', P4: 'green' };
            return colors[priority] || 'default';
        };

        watch([() => props.visible, () => props.taskXid], ([visibleVal, xidVal]) => {
            if (visibleVal && xidVal) {
                activeTabKey.value = 'details';
                fetchDetails();
                fetchMeta();
            }
        }, { immediate: true });

        return {
            task,
            employees,
            labels,
            activeTimer,
            editForm,
            subtaskInputVisible,
            newSubtask,
            checklistModalOpen,
            checklistForm,
            logTimeModalOpen,
            logTimeForm,
            commentText,
            timeMemo,
            quickUpdate,
            handleDuplicate,
            handleDelete,
            createSubtask,
            toggleSubtaskStatus,
            convertSubtask,
            showAddChecklistModal,
            createChecklist,
            deleteChecklist,
            addChecklistItem,
            toggleChecklistItem,
            deleteChecklistItem,
            handleFileUpload,
            deleteAttachment,
            submitComment,
            deleteComment,
            toggleEmojiReaction,
            startTimer,
            stopTimer,
            showLogTimeModal,
            logManualTime,
            formatDateTime,
            isImage,
            getPriorityColor,
            activeTabKey,
            dependencies,
            projectTasks,
            dependencyForm,
            addDependency,
            deleteDependency,
            getDependencyTypeName,
            workloadColumns,
            assigneeWorkload
        };
    }
});
</script>

<style scoped>
.timer-pulse {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    animation: pulse 1s infinite alternate;
}
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0.3; }
}
</style>
