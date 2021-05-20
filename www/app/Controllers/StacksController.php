<?php namespace App\Controllers;

use App\Models\StackModel;
use App\Models\TaskModel;
use App\Models\StackCollapsedModel;

class StacksController extends BaseController
{
    public function add_v1($idProject)
    {
        helper("documents");

        $user = $this->request->user;
        $document = documents_load($idProject, $user);
        
        if (!$document) {
            $this->reply("Project not found", 404, "ERR-STACK-CREATE");
        }

        $stackModel = new StackModel();
        $stackData = $this->request->getJSON();
        $stackData->project = $document->id;

        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
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
            "user" => $user->id
        ];

        try {
            if ($stackCollapsedModel->insert($collapsed) === false) {
                $errors = $stackCollapsedModel->errors();
                return $this->reply($errors, 500, "ERR-STACK-CREATE");    
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        $this->addActivity($document->id, $stackData->id, $this::ACTION_CREATE, $this::SECTION_PROJECT);

        $stack = $stackModel->find($stackData->id);
        return $this->reply($stack);
    }

    public function update_v1($idStack)
    {
        $this->lock($idStack);

        $stackModel = new StackModel();
        $stack = $stackModel->find($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-UPDATE");
        }

        helper("documents");

        $user = $this->request->user;
        $document = documents_load($stack->project, $user);

        $stackData = $this->request->getJSON();
        unset($stackData->created);

        // forcing the stack project id
        $stackData->project = $document->id;

        // saving stack tag/tint color
        if (isset($stackData->tag)) {
            $stackData->tag = json_encode($stackData->tag);
        } else {
            $stackData->tag = "";
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

        $this->addActivity($stack->project, $stack->id, $this::ACTION_UPDATE, $this::SECTION_STACK);

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

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($stack->project, $user);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-DONE");
        }

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

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($stack->project, $user);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-TODO");
        }

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

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($stack->project, $user);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-ALL");
        }

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

        helper("documents");
        $user = $this->request->user;
        $document = documents_load($stack->project, $user);

        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-ARCHIVE-DONE");
        }

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

        $this->addActivity($stack->project, $stack->id, $this::ACTION_DELETE, $this::SECTION_STACK);

        return $this->reply(true);
    }

    public function order_v1($idProject)
    {
        $this->lock($idProject);

        helper("documents");

        $user = $this->request->user;
        $document = documents_load($idProject, $user);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-STACK-ORDER");
        }

        $orderData = $this->request->getJSON();
        $db = db_connect();

        $query = array(
            "INSERT INTO ".$db->prefixTable("stacks")." (`id`, `project`, `position`) VALUES"
        );

        $orders = array();
        foreach ($orderData as $i => $stack) {
            $value = "(". $db->escape($stack) .", ". $db->escape($document->id) .", ". $db->escape($i + 1) .")";
            if ($i < count($orderData) - 1) {
                $value .= ",";
            }
            $query[] = $value;
        }

        $query[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `project`=VALUES(`project`), `position`=VALUES(`position`);";
        $query = implode(" ", $query);

        if (!$db->query($query)) {
            return $this->reply("Unable to update the stacks order", 500, "ERR-STACK-ORDER");
        }

        $this->addActivity("", $document->id, $this::ACTION_UPDATE, $this::SECTION_PROJECT);

        return $this->reply(true);
    }
}