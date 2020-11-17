<?php

use App\Models\StackModel;
use App\Models\TagModel;

if (!function_exists('projects_load_tags'))
{
    function projects_load_tags($id)
    {
        $tagModel = new TagModel();
        $tags = $tagModel->where("project", $id)->findAll();

        foreach ($tags as &$tag) {
            unset($tag->project);
        }

        return $tags;
    }
}

if (!function_exists('projects_load_stacks'))
{
    function projects_load_stacks($id)
    {
        $stackModel = new StackModel();
        $stackBuilder = $stackModel->builder();
        $stackQuery = $stackBuilder->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_order', 'stacks_order.stack = stacks.id', 'left')
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id', 'left')
            ->where('stacks.project', $id)
            ->where('stacks.deleted', NULL)
            ->orderBy('stacks_order.`order`', 'ASC')
            ->get();
        $stacks = $stackQuery->getResult();

        if (count($stacks)) {
            $stacksIDs = [];

            foreach ($stacks as &$stack) {
                $stack->collapsed = boolval($stack->collapsed);
                $stack->tag = json_decode($stack->tag);
                
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
                unset($stack->order);

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

if (!function_exists('projects_load'))
{
    function projects_load($record)
    {
        // load board tags
        $record->tags = projects_load_tags($record->id);
        // load board stacks
        $record->stacks = projects_load_stacks($record->id);

        $record->archived = [];

        return $record;
    }
}