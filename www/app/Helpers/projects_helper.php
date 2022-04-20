<?php

use App\Models\StackModel;
use App\Models\TagModel;
use App\Models\StatusModel;
use App\Models\TaskModel;

if (!function_exists("projects_expand")) {
    function projects_expand(&$document, $user) {
        // load project tags
        $tagModel = new TagModel();
        $document->tags = $tagModel
            ->where("project", $document->id)
            ->findAll();
            
        foreach ($document->tags as &$tag) {
            unset($tag->project);
        }

        // load project statuses
        $statusModel = new StatusModel();
        $document->statuses = $statusModel->where("project", $document->id)->findAll();
        foreach ($document->statuses as &$status) {
            unset($status->project);
        }

        // load project stacks
        $stackModel = new StackModel($user);
        $document->stacks = $stackModel->getStacks($document->id);
        
        if (count($document->stacks)) {
            $stacksIDs = array_map(fn($stack) => $stack->id, $document->stacks);

            // load all tasks
            $taskModel = new TaskModel($user);
            $tasks = $taskModel->getTasksByStacks($stacksIDs);

            // connect tasks to stacks
            foreach ($document->stacks as &$stack) {
                foreach ($tasks as $task) {                    
                    if ($task->stack === $stack->id) {
                        $stack->tasks[] = $task;
                    }
                }
            }
        }
        

        // TODO: moved archived task to their place
        $document->archived = [];

        $permissions = $document->permissions;
        $docId = $document->id;
        $document->permissions = new \stdClass();
        $document->permissions->$docId = $permissions;

        foreach ($document->stacks as &$stack) {
            $stackId = $stack->id;
            $document->permissions->$stackId = $stack->permissions;
            unset($stack->permissions);

            foreach ($stack->tasks as &$task) {
                $taskId = $task->id;
                $document->permissions->$taskId = $task->permissions;
                unset($task->permissions);
            }
        }

        unset($document->order);
        unset($document->deleted);
    }
}

if (!function_exists('projects_add_tags'))
{
    function projects_add_tags($projectId, $tags)
    {
        $tagModel = new TagModel();

        try {
            if ($tagModel->where("project", $projectId)->delete() === false) {
                return $tagModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        if (!count($tags)) {
            return true;
        }

        foreach ($tags as &$tag) {
            $tag->project = $projectId;
        }

        $tagBuilder = $tagModel->builder();

        try {
            if ($tagBuilder->insertBatch($tags) === false) {
                return $tagModel->errors();
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }
}


if (!function_exists("projects_clean_up")) 
{
    function projects_clean_up($document) 
    {
        // delete all stacks
        $stackModel = new StackModel();
        try {
            if ($stackModel->where("project", $document->id)->delete() === false) return false;
        } catch (\Exception $e) {
            return false;
        }

        // delete all tasks
        $taskModel = new TaskModel();
        try {
            if ($taskModel->where("project", $document->id)->delete() === false) return false;
        } catch (\Exception $e) {
            return false;
        }

        // delete all tags
        $tagModel = new TagModel();
        try {
            if ($tagModel->where("project", $document->id)->delete() === false) return false;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}