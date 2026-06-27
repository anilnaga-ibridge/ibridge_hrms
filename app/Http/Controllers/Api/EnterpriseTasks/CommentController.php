<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskComment;
use App\Models\EnterpriseTasks\CommentReaction;
use App\Models\EnterpriseTasks\TaskActivity;
use App\Models\EnterpriseTasks\Notification;
use App\Classes\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    private function logActivity($taskId, $type, $description, $properties = null)
    {
        TaskActivity::create([
            'task_id' => $taskId,
            'user_id' => auth('api')->id(),
            'activity_type' => $type,
            'description' => $description,
            'properties' => $properties
        ]);
    }

    private function createNotification($userId, $type, $data)
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'read_at' => null
        ]);
    }

    public function storeComment(Request $request, $taskId)
    {
        $tId = Common::getIdFromHash($taskId);
        $task = Task::findOrFail($tId);

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'x_parent_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $parentId = $request->x_parent_id ? Common::getIdFromHash($request->x_parent_id) : null;

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth('api')->id(),
            'parent_id' => $parentId,
            'comment' => $request->comment,
            'is_pinned' => false
        ]);

        // Mention Notification Logic
        preg_match_all('/@\[([^\]]+)\]\(([^)]+)\)/', $request->comment, $matches);
        if (isset($matches[2]) && count($matches[2]) > 0) {
            foreach ($matches[2] as $userXid) {
                $mentionedUserId = Common::getIdFromHash($userXid);
                if ($mentionedUserId && $mentionedUserId != auth('api')->id()) {
                    $this->createNotification($mentionedUserId, 'comment_mention', [
                        'task_title' => $task->title,
                        'task_xid' => $task->xid,
                        'commenter_name' => auth('api')->user()->name
                    ]);
                }
            }
        }

        $this->logActivity($task->id, 'comment', "Added comment: " . Str::limit($request->comment, 50));

        return response()->json($comment);
    }

    public function updateComment(Request $request, $id)
    {
        $commentId = Common::getIdFromHash($id);
        $comment = TaskComment::findOrFail($commentId);

        if ($request->has('comment')) {
            $comment->update(['comment' => $request->comment]);
        }
        if ($request->has('is_pinned')) {
            $comment->update(['is_pinned' => $request->is_pinned]);
        }

        return response()->json($comment);
    }

    public function destroyComment($id)
    {
        $commentId = Common::getIdFromHash($id);
        $comment = TaskComment::findOrFail($commentId);
        $comment->delete();
        return response()->json(['success' => true]);
    }

    public function toggleReaction(Request $request, $id)
    {
        $commentId = Common::getIdFromHash($id);
        $userId = auth('api')->id();

        $validator = Validator::make($request->all(), [
            'emoji' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existing = CommentReaction::where('comment_id', $commentId)
            ->where('user_id', $userId)
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            CommentReaction::create([
                'comment_id' => $commentId,
                'user_id' => $userId,
                'emoji' => $request->emoji
            ]);
        }

        return response()->json(['success' => true]);
    }
}
