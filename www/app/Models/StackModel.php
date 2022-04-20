<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BaseModel;
use App\Models\StackCollapsedModel;
use App\Models\PermissionModel;

class StackModel extends BaseModel
{
    protected $table      = "stacks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "project", "tag", "maxTasks", "automation", "position", "sorting", "public", "owner", "created", "updated"];

    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $afterFind = ["formatStacks"];

    protected $validationRules = [
        "id" => "required|min_length[20]",
        "title" => "required|alpha_numeric_punct",
        "project" => "required|min_length[20]",
        "position" => "required"
    ];

    protected function formatStack(&$stack)
    {
        $stack->position = intval($stack->position);
        $stack->collapsed = boolval($stack->collapsed);
        $stack->public = boolval($stack->public);
        $stack->owner = intval($stack->owner);
        $stack->isOwner = $stack->owner == $this->user->id;

        if (isset($stack->tag) && is_string($stack->tag)) {
            $stack->tag = json_decode($stack->tag);
        }

        if (isset($stack->automation) && is_string($stack->automation)) {
            $stack->automation = json_decode($stack->automation);
        }

        if ($stack->tag == null) unset($stack->tag);
        if ($stack->automation == null) unset($stack->automation);
        if ($stack->maxTasks == null) unset($stack->maxTasks);
        if ($stack->position == null) unset($stack->position);
        if ($stack->sorting == null) unset($stack->sorting);

        $stack->tasks = array();

        unset($stack->deleted);
    }

    protected function formatStacks(array $data)
    {
        // format single stack
        if ($data["singleton"] && $data["data"]) {
            $this->formatStack($data["data"]);
        }

        // format list of stacks
        if (!$data["singleton"] && $data["data"]) {
            foreach ($data["data"] as $key => &$stack) {
                $this->formatStack($stack);
            }
        }

        return $data;
    }

    protected function getUserPermissions($permission)
    {
        /*
            FULL - add tasks, change tasks, delete tasks, change stack, delete stack
            EDIT - add tasks, change tasks, delete tasks, NO changing stack, NO deleting stack
            LIMITED - owner can rename the stack
            NONE - Read only
        */
        $can = "";
        $can .= $permission === "FULL" || $permission === "EDIT" ? "A" : "";
        $can .= $permission === "FULL" || $permission === "EDIT" ? "D" : "";
        $can .= $permission === "FULL" || $permission === "EDIT" ? "U" : "";
        return $can;
    }

    protected function getFindQuery()
    {
        /*
            Retrieving stacks that match the following criteria:
            - stack is not deleted
            - stack is public
            - stack is not public and the current user is owner
            - stack is not public and the current user is not owner but it has a permission
        */
        $db = db_connect();
        return $this
            ->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id AND stacks_collapsed.user = '.$db->escape($this->user->id), 'left')
            ->join("permissions", "permissions.resource = stacks.id AND permissions.user = ".$db->escape($this->user->id), 'left')
            ->groupStart()
                ->where("public", 1)
                ->orGroupStart()
                    ->where("public", 0)
                    ->where("owner", $this->user->id)
                ->groupEnd()
                ->orWhere("permissions.permission IS NOT NULL", null)
            ->groupEnd();
    }

    public function getStack($stackId)
    {
        $stack = $this->getFindQuery()->find($stackId);

        if ($stack) {
            $permissionModel = new PermissionModel($this->user);
            $stack->permission = $permissionModel->getPermission($stack->id, $stack->owner);
            $stack->permissions = $this->getUserPermissions($stack->permission);
        }

        return $stack;
    }

    public function getStacks($projectId)
    {
        $stacks = $this->getFindQuery()
            ->where('stacks.project', $projectId)
            ->orderBy('position', 'ASC')
            ->findAll();

        if (count($stacks)) {
            $permissionModel = new PermissionModel($this->user);
            $permissionModel->getPermissions($stacks);

            foreach ($stacks as &$stack) {
                $stack->permissions = $this->getUserPermissions($stack->permission, $stack->public, $stack->isOwner);
            }
        }

        return $stacks;
    }

    public function formatData(&$data)
    {
        if (!isset($data->id)) {
            helper('uuid');
            $data->id = uuid();
        }

        if (isset($data->tag)) {
            $data->tag = json_encode($data->tag);
        } else {
            $data->tag = "";
        }

        if (isset($data->automation)) {
            $data->automation = json_encode($data->automation);
        } else {
            $data->automation = "";
        }

        if (!isset($data->position)) {
            $lastPosition = $this
                ->where("project", $data->project)
                ->orderBy("position", "desc")
                ->first();

            $data->position = intval($lastPosition->position) + 1;
        }

        if (!isset($data->maxTasks)) {
            $data->maxTasks = NULL;
        }

        unset($data->created);
    }

    public function addCollapsedState($stackId, $userId, $state = 0)
    {
        $stackCollapsedModel = new StackCollapsedModel();

        try {
            if (
                $stackCollapsedModel
                    ->where("user", $userId)
                    ->where("stack", $stackId)
                    ->delete() === false
            ) {
                throw new \Exception($stackCollapsedModel->errors());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $collapsed = [
            "stack" => $stackId,
            "collapsed" => $state,
            "user" => $userId
        ];

        try {
            if ($stackCollapsedModel->insert($collapsed) === false) {   
                throw new \Exception(implode(" ", $stackCollapsedModel->errors()));
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}