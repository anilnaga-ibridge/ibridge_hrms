<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnterpriseTasks\ProjectController;
use App\Http\Controllers\Api\EnterpriseTasks\TaskController;
use App\Http\Controllers\Api\EnterpriseTasks\CommentController;
use App\Http\Controllers\Api\EnterpriseTasks\NotificationController;
use App\Http\Controllers\Api\EnterpriseTasks\CalendarController;
use App\Http\Controllers\Api\EnterpriseTasks\AIController;
use App\Http\Controllers\Api\EnterpriseTasks\SyncController;
use App\Http\Controllers\Api\EnterpriseTasks\GamificationController;
use App\Http\Controllers\Api\EnterpriseTasks\AdminController;
use App\Http\Controllers\Api\EnterpriseTasks\EnterpriseTasksController;

Route::group([
    'prefix' => 'api/v1/enterprise-tasks',
    'middleware' => ['api', 'api.auth.check']
], function () {
    // Dashboard
    Route::get('dashboard/personal', [EnterpriseTasksController::class, 'personalDashboard']);
    Route::get('dashboard/manager', [EnterpriseTasksController::class, 'managerDashboard']);
    Route::get('dashboard/admin', [EnterpriseTasksController::class, 'adminDashboard']);

    // Projects
    Route::get('projects', [ProjectController::class, 'indexProjects']);
    Route::get('projects-inbox', [TaskController::class, 'getInboxProject']);
    Route::get('projects/{id}', [ProjectController::class, 'showProject']);

    // Project Sections
    Route::post('projects/{id}/sections', [ProjectController::class, 'storeSection']);
    Route::put('sections/{id}', [ProjectController::class, 'updateSection']);
    Route::delete('sections/{id}', [ProjectController::class, 'destroySection']);
    Route::post('projects/{id}/sections/reorder', [ProjectController::class, 'reorderSections']);

    // Labels
    Route::get('labels', [ProjectController::class, 'indexLabels']);
    Route::post('labels', [ProjectController::class, 'storeLabel']);
    Route::put('labels/{id}', [ProjectController::class, 'updateLabel']);
    Route::delete('labels/{id}', [ProjectController::class, 'destroyLabel']);

    // Tasks & Subtasks
    Route::get('tasks', [TaskController::class, 'indexTasks']);
    Route::post('tasks', [TaskController::class, 'storeTask']);
    Route::get('tasks/{id}', [TaskController::class, 'showTask']);
    Route::put('tasks/{id}', [TaskController::class, 'updateTask']);
    Route::delete('tasks/{id}', [TaskController::class, 'destroyTask']);
    
    // Task Operations
    Route::post('tasks/{id}/duplicate', [TaskController::class, 'duplicateTask']);
    Route::post('tasks/{id}/move', [TaskController::class, 'moveTask']);
    Route::post('tasks/bulk-update', [TaskController::class, 'bulkUpdateTasks']);
    Route::post('tasks/bulk-delete', [TaskController::class, 'bulkDeleteTasks']);
    Route::post('tasks/{id}/toggle-complete', [TaskController::class, 'toggleTaskComplete']);
    
    // Subtask Conversion
    Route::post('tasks/{id}/subtasks', [TaskController::class, 'storeSubtask']);
    Route::post('tasks/{id}/convert-subtask', [TaskController::class, 'convertSubtask']);

    // Checklists
    Route::post('tasks/{id}/checklists', [TaskController::class, 'storeChecklist']);
    Route::delete('checklists/{id}', [TaskController::class, 'destroyChecklist']);
    Route::post('checklists/{id}/items', [TaskController::class, 'storeChecklistItem']);
    Route::put('checklist-items/{id}', [TaskController::class, 'updateChecklistItem']);
    Route::delete('checklist-items/{id}', [TaskController::class, 'destroyChecklistItem']);
    Route::post('checklists/{id}/items/reorder', [TaskController::class, 'reorderChecklistItems']);

    // Comments & Reactions
    Route::post('tasks/{id}/comments', [CommentController::class, 'storeComment']);
    Route::put('comments/{id}', [CommentController::class, 'updateComment']);
    Route::delete('comments/{id}', [CommentController::class, 'destroyComment']);
    Route::post('comments/{id}/reactions', [CommentController::class, 'toggleReaction']);

    // Attachments
    Route::post('tasks/{id}/attachments', [TaskController::class, 'storeAttachment']);
    Route::delete('attachments/{id}', [TaskController::class, 'destroyAttachment']);

    // Reminders
    Route::post('tasks/{id}/reminders', [TaskController::class, 'storeReminder']);
    Route::delete('reminders/{id}', [TaskController::class, 'destroyReminder']);

    // Time Tracking
    Route::post('tasks/{id}/time-logs/start', [TaskController::class, 'startTimeLog']);
    Route::post('tasks/{id}/time-logs/stop', [TaskController::class, 'stopTimeLog']);
    Route::post('tasks/{id}/time-logs', [TaskController::class, 'storeTimeLog']);
    Route::get('time-logs', [TaskController::class, 'indexTimeLogs']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'indexNotifications']);
    Route::post('notifications/mark-read', [NotificationController::class, 'markNotificationsRead']);

    // Saved Filters
    Route::get('saved-filters', [TaskController::class, 'indexSavedFilters']);
    Route::post('saved-filters', [TaskController::class, 'storeSavedFilter']);
    Route::delete('saved-filters/{id}', [TaskController::class, 'destroySavedFilter']);

    // Reports
    Route::get('reports', [TaskController::class, 'getReports']);
    Route::get('reports/export', [TaskController::class, 'exportReport']);
    Route::get('reports/pdf', [TaskController::class, 'exportPdfReport']);

    // Task Dependencies
    Route::get('tasks/{id}/dependencies', [TaskController::class, 'indexDependencies']);
    Route::post('dependencies', [TaskController::class, 'storeDependency']);
    Route::delete('dependencies/{id}', [TaskController::class, 'destroyDependency']);
    Route::get('projects/{id}/critical-path', [TaskController::class, 'criticalPath']);

    // Automation Rules
    Route::get('rules', [TaskController::class, 'indexRules']);
    Route::post('rules', [TaskController::class, 'storeRule']);
    Route::put('rules/{id}', [TaskController::class, 'updateRule']);
    Route::delete('rules/{id}', [TaskController::class, 'destroyRule']);

    // Task Templates
    Route::get('templates', [TaskController::class, 'indexTemplates']);
    Route::post('templates', [TaskController::class, 'storeTemplate']);
    Route::put('templates/{id}', [TaskController::class, 'updateTemplate']);
    Route::delete('templates/{id}', [TaskController::class, 'destroyTemplate']);
    Route::post('templates/{id}/apply', [TaskController::class, 'applyTemplate']);

    // Saved Views
    Route::get('saved-views', [TaskController::class, 'indexSavedViews']);
    Route::post('saved-views', [TaskController::class, 'storeSavedView']);
    Route::put('saved-views/{id}', [TaskController::class, 'updateSavedView']);
    Route::delete('saved-views/{id}', [TaskController::class, 'destroySavedView']);

    // Productivity Rankings
    Route::get('productivity-rankings', [TaskController::class, 'productivityRankings']);

    // TODOIST PREMIUM & JIRA ENTERPRISE ADDITIONAL ROUTES
    Route::post('tasks/quick-create', [TaskController::class, 'quickCreateTask']);
    Route::get('global-search', [TaskController::class, 'globalSearch']);
    
    // Favorites
    Route::get('favorites', [TaskController::class, 'indexFavorites']);
    Route::post('favorites', [TaskController::class, 'storeFavorite']);
    Route::delete('favorites/{id}', [TaskController::class, 'destroyFavorite']);

    // Undo System
    Route::post('undo/{xid}', [TaskController::class, 'executeUndo']);

    // Achievements & Streaks
    Route::get('achievements', [GamificationController::class, 'indexAchievements']);

    // Goals
    Route::get('goals', [GamificationController::class, 'indexGoals']);
    Route::post('goals', [GamificationController::class, 'storeGoal']);
    Route::put('goals/{id}', [GamificationController::class, 'updateGoal']);
    Route::delete('goals/{id}', [GamificationController::class, 'destroyGoal']);

    // Pomodoro Sessions
    Route::post('pomodoro/start', [GamificationController::class, 'startPomodoro']);
    Route::post('pomodoro/{id}/complete', [GamificationController::class, 'completePomodoro']);
    Route::get('pomodoro/stats', [GamificationController::class, 'pomodoroStats']);

    // Notification Preferences
    Route::get('notification-preferences', [NotificationController::class, 'indexNotificationPreferences']);
    Route::post('notification-preferences', [NotificationController::class, 'saveNotificationPreferences']);

    // ====================================================
    // NEW CHANNELS / ROUTINGS FOR ICS CALENDAR & AI & SYNC
    // ====================================================
    Route::get('calendar/export.ics', [CalendarController::class, 'exportICS']);
    Route::get('calendar/ical-url', [CalendarController::class, 'icalSubscriptionUrl']);

    // AI Task Assistant
    Route::post('ai/generate-subtasks', [AIController::class, 'generateSubtasks']);
    Route::post('ai/suggest-priority', [AIController::class, 'suggestPriority']);
    Route::post('ai/suggest-deadline', [AIController::class, 'suggestDeadline']);
    Route::post('ai/generate-description', [AIController::class, 'generateDescription']);
    Route::post('ai/standup-summary', [AIController::class, 'standupSummary']);
    Route::get('ai/smart-schedule', [AIController::class, 'smartSchedule']);

    // Offline First Sync
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull', [SyncController::class, 'pull']);

    // Admin / Observability Dashboard
    Route::get('admin/logs', [AdminController::class, 'getLogs']);
    Route::get('admin/error-summary', [AdminController::class, 'getErrorSummary']);
    Route::get('admin/feature-flags', [AdminController::class, 'getFeatureFlags']);
    Route::post('admin/feature-flags/toggle', [AdminController::class, 'toggleFeatureFlag']);
});

// Public feed access for ICS Calendar Feed (does not require auth middleware, protected by access token)
Route::get('api/v1/enterprise-tasks/calendar/feed/{token}.ics', [CalendarController::class, 'exportICS'])
    ->name('enterprise-tasks.calendar.ics');
