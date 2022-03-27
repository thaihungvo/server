<?php namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\StackModel;
use App\Models\TaskModel;
use App\Models\StackCollapsedModel;

class StacksController extends BaseController
{
    protected $permissionSection = "stacks";

    public function add_v1($idProject)
    {
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($idProject);
        
        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-STACK-CREATE");
        }
        
        // check if the current user has the permission to add a new stack
        $this->can("add", $document);

        $stackModel = new StackModel();
        $stackData = $this->request->getJSON();
        $stackData->project = $document->id;

        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
        }
        if (isset($stackData->automation)) {
            $stackData->automation = json_encode($stackData->automation);
        }

        if (!isset($stackData->position)) {
            $lastPosition = $stackModel
                ->where("project", $document->id)
                ->orderBy("position", "desc")
                ->first();

            $stackData->position = intval($lastPosition->position) + 1;
        }

        if (!isset($stackData->id)) {
            helper('uuid');
            $stackData->id = uuid();
        }

        try {
            if ($stackModel->insert($stackData) === false) {
                $errors = $stackModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        $stackCollapsedModel = new StackCollapsedModel();
        
        // create a default collapsed state
        $collapsed = [
            "stack" => $stackData->id,
            "collapsed" => 0,
            "user" => $this->request->user->id
        ];

        try {
            if ($stackCollapsedModel->insert($collapsed) === false) {
                $errors = $stackCollapsedModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-CREATE");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        $this->addActivity(
            $document->id,
            $document->id, 
            $stackData->id, 
            $this::ACTION_CREATE, 
            $this::SECTION_STACK
        );

        $stack = $stackModel->find($stackData->id);
        return $this->reply($stack);
    }

    public function get_v1($idStack)
    {
        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-GET");
        }

        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        // checking if the current user has the permission to get the stack
        $this->can("read", $document);

        if (isset($stack->tag)) {
            $stack->tag = json_decode($stack->tag);
        }
        if (isset($stack->automation)) {
            $stack->automation = json_decode($stack->automation);
        }

        unset($stack->project);
        $stack->position = intval($stack->position);

        $stackCollapsedModel = new StackCollapsedModel();
        $stackCollapsed = $stackCollapsedModel->where("stack", $idStack)->first();
        $stack->collapsed = (bool)$stackCollapsed->collapsed;

        return $this->reply($stack);
    }

    public function update_v1($idStack)
    {
        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-UPDATE");
        }

        $this->lock($idStack);

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $stackData = $this->request->getJSON();
        if (!isset($stackData->maxTasks)) {
            $stackData->maxTasks = NULL;
        }
        unset($stackData->created);

        // forcing the stack project id
        $stackData->project = $document->id;

        // saving stack tag/tint color
        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
        } else {
            $stackData->tag = "";
        }

        // saving stack automation
        if (isset($stackData->automation)) {
            $stackData->automation = json_encode($stackData->automation);
        } else {
            $stackData->automation = "";
        }

        // saving collapsed state of the stack for the current user
        if (isset($stackData->collapsed)) {
            $stackCollapsedModel = new StackCollapsedModel();
            try {
                if (
                    $stackCollapsedModel
                        ->where("user", $user->id)
                        ->where("stack", $stackData->id)
                        ->delete() === false
                ) {
                    $errors = $stackCollapsedModel->errors();
                    return $this->reply($errors, 500, "ERR-STACK-UPDATE");
                }
                    
                if ($stackCollapsedModel->insert([
                    "stack" => $stackData->id,
                    "collapsed" => intval($stackData->collapsed),
                    "user" => $user->id
                ]) === false) {
                    $errors = $stackCollapsedModel->errors();
                    return $this->reply($errors, 500, "ERR-STACK-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-STACK-UPDATE");
            }
        }

        // update the stack data
        if ($stackModel->update($stack->id, $stackData) === false) {
            return $this->reply($stackModel->errors(), 500, "ERR-STACK-UPDATE");
        }

        $this->addActivity(
            $document->id,
            $stack->project, 
            $stack->id, 
            $this::ACTION_UPDATE, 
            $this::SECTION_STACK
        );

        return $this->reply(true);
    }

    public function done_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-DONE");
        }

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-DONE");
        }

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $taskModel = new TaskModel();

        try {
            if (
                $taskModel->where("stack", $stack->id)
                    ->set([
                        "done" => 1,
                        "progress" => 100
                    ])
                    ->update() === false
            ) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-DONE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-DONE");
        }

        return $this->reply(true);
    }

    public function todo_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-TODO");
        }

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-TODO");
        }

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $taskModel = new TaskModel();

        try {
            if (
                $taskModel->where("stack", $stack->id)
                    ->set([
                        "done" => 0,
                        "progress" => 0
                    ])
                    ->update() === false
            ) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-TODO");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-TODO");
        }

        return $this->reply(true);
    }

    public function archive_all_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-ALL");
        }

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-ALL");
        }

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $taskModel = new TaskModel();

        try {
            if (
                $taskModel->where("stack", $stack->id)
                    ->set(["archived" => date("Y-m-d H:i:s")])
                    ->update() === false
            ) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-ALL");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-ARCHIVE-ALL");
        }

        return $this->reply(true);
    }

    public function archive_done_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-DONE");
        }

        $user = $this->request->user;
        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-DONE");
        }

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $taskModel = new TaskModel();

        try {
            if (
                $taskModel->where("stack", $stack->id)
                    ->where("done", 1)
                    ->set(["archived" => date("Y-m-d H:i:s")])
                    ->update() === false
            ) {
                return $this->reply($taskModel->errors(), 500, "ERR-STACK-ARCHIVE-DONE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-ARCHIVE-DONE");
        }

        return $this->reply(true);
    }

    public function delete_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-DELETE");
        }

        $documentModel = new DocumentModel();
        $documentModel->user = $this->request->user;
        $document = $documentModel->find($stack->project);

        // checking if the current user has the permission to delete the stack
        $this->can("delete", $document);

        // delete all connected tasks
        $taskModel = new TaskModel();
        $taskBuilder = $taskModel->builder();
        $taskBuilder->set("deleted", "NOW()", false)
            ->where("stack", $stack->id)
            ->update();

        // delete selected stack
        try {
            if ($stackModel->delete([$stack->id]) === false) {
                return $this->reply($stackModel->errors(), 500, "ERR-STACK-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-DELETE");
        }

        $this->addActivity(
            $stack->project, 
            $stack->project, 
            $stack->id, 
            $this::ACTION_DELETE, 
            $this::SECTION_DOCUMENT
        );

        return $this->reply(true);
    }
}