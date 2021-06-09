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
    function projects_load_stacks($id)
    {
        $stackModel = new StackModel();
        $stackBuilder = $stackModel->builder();
        $stackQuery = $stackBuilder->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id', 'left')
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

if (!function_exists('projects_load'))
{
    function projects_load($document)
    {
        // load board tags
        $document->tags = projects_load_tags($document->id);
        // load board stacks
        $document->stacks = projects_load_stacks($document->id);

        $document->archived = [];

        return $document;
    }
}

if (!function_exists('projects_update'))
{
    function projects_update($project)
    {
        // helper('uuid');

        // $user = $this->request->user;
        // $recordData = $this->request->getJSON();

        // // create record object
        // $record = new \stdClass();
        // $record->id = $recordData->id;
        // $record->title = $recordData->title;
        // $record->owner = $user->id;
        // $record->type = "project";

        // // in case everyone was not set will enforce it to 1
        // if (!isset($recordData->everyone)) {
        //     $record->everyone = 1;
        // } else {
        //     $record->everyone = intval($recordData->everyone);
        // }

        // // setting the record type
        // if (isset($recordData->type)) {
        //     $record->type = $recordData->type;
        // }

        // // set record ID in case is missing
        // if (!isset($record->id)) {
        //     $record->id = uuid();
        // }

        // // getting the members array
        // // and setting everyone to 0 in case there are members
        // $membersIDs = array();
        // if (isset($recordData->members)) {
        //     $membersIDs = $recordData->members;
        //     $record->everyone = 0;
        // }

        // // $recordData->archived_order = 'title-asc';
        // // if (!isset($recordData->hourlyFee)) {
        // //     $recordData->hourlyFee = 0;
        // // }
        // // if (!isset($recordData->feeCurrency)) {
        // //     $recordData->feeCurrency = "USD";
        // // }
        // // if (isset($recordData->tags)) {
        // //     if (!$this->set_tags($recordData->id, $recordData->tags)) {
        // //         return $this->reply(null, 500, "ERR-BOARD-TAGS");   
        // //     }
        // // }
        
        // // save the record
        // $DocumentModel = new DocumentModel();
        
        // if ($update) {
        //     try {
        //         if ($DocumentModel->update($record->id, $record) === false) {
        //             $errors = $DocumentModel->errors();
        //             // TODO: need to change the error message based on the type of the record updated
        //             return $this->reply($errors, 500, "ERR-RECORD-UPDATE");
        //         }
        //     } catch (\Exception $e) {
        //         // TODO: need to change the error message based on the type of the record updated
        //         return $this->reply($e->getMessage(), 500, "ERR-RECORD-UPDATE");
        //     }

        //     Events::trigger("AFTER_record_UPDATE", $record->id);
        // } else {
        //     try {
        //         if ($DocumentModel->insert($record) === false) {
        //             $errors = $DocumentModel->errors();
        //             // TODO: need to change the error message based on the type of the record created
        //             return $this->reply($errors, 500, "ERR-RECORD-CREATE");
        //         }
        //     } catch (\Exception $e) {
        //         // TODO: need to change the error message based on the type of the record created
        //         return $this->reply($e->getMessage(), 500, "ERR-RECORD-CREATE");
        //     }
        
        //     // assign record to folder
        //     $recordOrder = new \stdClass();
        //     $recordOrder->folder = $recordData->folder;
        //     $recordOrder->record = $record->id;
        //     $recordOrder->order = 1;

        //     // $recordOrderModel = new RecordOrderModel();
        //     // $lastOrder = $recordOrderModel->where("folder", $recordData->folder)
        //     //     ->orderBy("order", "desc")
        //     //     ->first();

        //     // if ($lastOrder) {
        //     //     $recordOrder->order = intval($lastOrder->order) + 1;
        //     // }

        //     // try {
        //     //     if ($recordOrderModel->insert($recordOrder) === false) {
        //     //         $errors = $recordOrderModel->errors();
        //     //         return $this->reply($errors, 500, "ERR-RECORD-ORDER");
        //     //     }
        //     // } catch (\Exception $e) {
        //     //     return $this->reply($e->getMessage(), 500, "ERR-RECORD-ORDER");
        //     // }

        //     // if members are defined then assigned them to the board
        //     if (count($membersIDs)) {
        //         $recordMemberModel = new RecordMemberModel();
        //         $recordMemberBuilder = $recordMemberModel->builder();

        //         $members = array();
        //         foreach ($membersIDs as $userID) {
        //             $members[] = [
        //                 'record' => $record->id,
        //                 'user' => $userID
        //             ];
        //         }

        //         try {
        //             if ($recordMemberBuilder->insertBatch($members) === false) {
        //                 $errors = $recordMemberBuilder->errors();
        //                 return $this->reply($errors, 500, "ERR-RECORD-MEMBERS");    
        //             }
        //         } catch (\Exception $e) {
        //             return $this->reply($e->getMessage(), 500, "ERR-RECORD-MEMBERS");
        //         }
        //     }

        //     Events::trigger("AFTER_record_ADD", $record->id);
        // }

    }
}