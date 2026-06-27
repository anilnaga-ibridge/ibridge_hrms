<?php

namespace App\Events\EnterpriseTasks;

use App\Models\EnterpriseTasks\TaskAttachment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAttachmentUploaded
{
    use Dispatchable, SerializesModels;

    public TaskAttachment $attachment;

    public function __construct(TaskAttachment $attachment)
    {
        $this->attachment = $attachment;
    }
}
