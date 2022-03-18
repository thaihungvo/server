<?php

use App\Models\StackModel;
use App\Models\TagModel;
use App\Models\StatusModel;
use App\Models\TaskModel;

if (!function_exists("projects_expand")) {
    function projects_expand(&$document, $user) {
        // load project tags
        $tagModel = new TagModel();
        $tags = $tagModel->where("project", $document->id)->findAll();
        foreach ($tags as &$tag) {
            unset($tag->project);
        }
        $document->tags = $tags;

        // load project statuses
        $statusModel = new StatusModel();
        $statuses = $statusModel->where("project", $document->id)->findAll();
        foreach ($statuses as &$status) {
            unset($status->project);
        }
        $document->statuses = $statuses;

        // load project stacks
        $document->stacks = projects_load_stacks($document->id, $user);
        // TODO: moved archived task to their place
        $document->archived = [];

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

if (!function_exists('projects_load_stacks'))
{
    function projects_load_stacks($id, $user)
    {
        $stackModel = new StackModel();
        $stackBuilder = $stackModel->builder();
        $stackQuery = $stackBuilder->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id AND stacks_collapsed.user = '.$user->id, 'left')
            ->where('stacks.project', $id)
            ->where('stacks.deleted', NULL)
            ->orderBy('position', 'ASC')
            ->get();
        $stacks = $stackQuery->getResult();

        if (count($stacks)) {
            $stacksIDs = [];

            foreach ($stacks as &$stack) {
                $stack->collapsed = boolval($stack->collapsed);
                $stack->tag = json_decode($stack->tag);
                $stack->automation = json_decode($stack->automation);
                
                unset($stack->project);
                unset($stack->deleted);

                $stacksIDs[] = $stack->id;
            }

            helper('tasks');

            // load all tasks
            $tasks = tasks_load($stacksIDs);

            // connect tasks to stacks
            foreach ($stacks as &$stack) {
                // remove the order property from the stack
                unset($stack->position);

                $stack->tasks = [];
                foreach ($tasks as $task) {                    
                    if ($task->stack === $stack->id) {
                        $stack->tasks[] = task_format($task);
                    }
                }
            }
        }

        return $stacks;
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