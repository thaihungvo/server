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
        $document = $this->getDocument($idProject);

        if (!$document) {
            return $this->reply("Project not found", 404, "ERR-STACK-CREATE");
        }
        
        // check if the current user has the permission to add a new stack
        $this->can("add", $document);

        $stackModel = new StackModel($this->request->user);
        $data = $this->request->getJSON();
        $data->project = $document->id;
        $data->owner = $this->request->user->id;
        $data->public = 1;
        $stackModel->formatData($data);

        $this->db->transStart();

        try {
            if ($stackModel->insert($data) === false) {
                return $this->reply($stackModel->errors(), 500, "ERR-STACK-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        try {
            $stackModel->addCollapsedState($data->id, $this->request->user->id);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        // inserting the default document permission
        try {
            $this->addPermission($data->id, $this::PERMISSION_TYPE_STACK);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STACK-CREATE");
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->reply(null, 500, "ERR-STACK-UPDATE");
        }

        $this->addActivity(
            $document->id,
            $document->id, 
            $data->id, 
            $this::ACTION_CREATE, 
            $this::SECTION_STACK
        );

        $stack = $stackModel->getStack($data->id);
        return $this->reply($stack);
    }

    public function get_v1($idStack)
    {
        $stackModel = new StackModel($this->request->user);
        $stack = $stackModel->getStack($idStack);

        if (!$stack) {
            return $this->reply("Stack not found", 404, "ERR-STACK-GET");
        }

        $document = $this->getDocument($stack->project);
        if (!$document) {
            return $this->reply("Stack not found", 404, "ERR-STACK-GET");
        }

        // checking if the current user has the permission to get the stack
        // $this->can("read", $document);

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

        $document = $this->getDocument($stack->project);

        // checking if the current user has the permission to update the stack
        $this->can("update", $document);

        $data = $this->request->getJSON();
        // forcing the stack project id
        $data->project = $document->id;
        // forcing the stack id
        $data->id = $stack->id;
        $stackModel->formatData($data);

        $this->db->transStart();

        // saving collapsed state of the stack for the current user
        if (isset($data->collapsed)) {
            try {
                $stackModel->addCollapsedState($stack->id, $this->request->user->id, intval($data->collapsed));
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-STACK-UPDATE");
            }
        }

        // update the stack data
        if ($stackModel->update($stack->id, $data) === false) {
            return $this->reply($stackModel->errors(), 500, "ERR-STACK-UPDATE");
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return $this->reply(null, 500, "ERR-STACK-UPDATE");
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

        $documentModel = new DocumentModel($this->request->user);
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

        $documentModel = new DocumentModel($this->request->user);
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

        $documentModel = new DocumentModel($this->request->user);
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

        $documentModel = new DocumentModel($this->request->user);
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

        $documentModel = new DocumentModel($this->request->user);
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