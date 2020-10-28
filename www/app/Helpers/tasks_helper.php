<?php

use App\Models\TaskModel;
use App\Models\TaskExtensionModel;
use App\Models\AttachmentModel;

if (!function_exists('tasks_load'))
{
    function tasks_load($stacksIDs)
    {
        $taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();
        $taskQuery = $taskBuilder->select("tasks.*, tasks_order.stack, tasks_order.order")
            ->join('tasks_order', 'tasks_order.task = tasks.id', 'left')
            ->whereIn('tasks_order.stack', $stacksIDs)
            ->where('tasks.deleted', NULL)
            ->where('tasks.archived', NULL)
            ->orderBy('tasks_order.`order`', 'ASC')
            ->get();
        $tasks = $taskQuery->getResult();

        // load task assignees
        $tasksIDs = array();
        foreach ($tasks as $task) {
            $tasksIDs[] = $task->id;
        }

        helper('assignees');
        $assignees = tasks_assignees($tasksIDs);

        // connect assignees to tasks
        foreach ($tasks as &$task) {
            foreach ($assignees as &$assignee) {
                if (!isset($task->assignees)) {
                    $task->assignees = array();
                }

                if ($assignee->task === $task->id) {
                    unset($assignee->task);
                    $task->assignees[] = $assignee;
                }
            }
        }

        $extensions = array();
        if (count($tasksIDs)) {
            $taskExtensionModel = new TaskExtensionModel();
            $extensions = $taskExtensionModel->whereIn("task", $tasksIDs)->findAll();
        }

        $attachmentsIDs = array();

        // unwrap the extensions
        foreach ($extensions as &$extension) {
            $extension->options = json_decode($extension->options);
            $extension->content = json_decode($extension->content);
        }

        // load tasks attachments
        $attachments = array();
        if (count($tasksIDs)) {        
            $attachmentModel = new AttachmentModel();
            $attachments = $attachmentModel->whereIn("task", $tasksIDs)->findAll();
        }

        // attach the extensions to every task
        foreach ($tasks as &$task) {
            $task->extensions = array();
            foreach ($extensions as &$extension) {
                if ($extension->task == $task->id) {
                    unset($extension->task);
                    $task->extensions[] = $extension;
                }
            }

            // insert the attachments in the task extension
            foreach ($attachments as $attachment) {
                if ($attachment->task == $task->id && isset($task->extensions)) {
                    unset($attachment->task);

                    foreach ($task->extensions as &$extension) {
                        if ($extension->type == "attachments") {
                            if (!is_array($extension->content)) {
                                $extension->content = array();
                            }

                            $extension->content[] = $attachment;
                        }
                    }
                }
            }

            if (!count($task->extensions)) {
                unset($task->extensions);
            }
        }

        return $tasks;
    }
}

if (!function_exists('task_format'))
{
    function task_format($task) 
	{
		$task->cover = (bool)$task->cover;
        $task->done = (bool)$task->done;
        $task->altTags = (bool)$task->altTags;
        $task->progress = (int)$task->progress;
        if (is_string($task->tags)) {
            $task->tags = json_decode($task->tags);
        }
        // if (is_string($task->info)) {
        //     $task->info = json_decode($task->info);
        // }

        return $task;
    }
}

if (!function_exists('task_last_updated'))
{
    function task_last_updated($taskID) 
	{
		$taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();

        $taskQuery = $taskBuilder->select("updated")
            ->where("id", $taskID)
            ->limit(1)
            ->get();
        
        $tasks = $taskQuery->getResult();

        if (!count($tasks)) {
            return null;
        }

        return $tasks[0]->updated;
    }
}