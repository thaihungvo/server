<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\BaseModel;
use App\Models\StackCollapsedModel;

class StackModel extends BaseModel
{
    protected $table      = "stacks";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $useSoftDeletes = true;

    protected $allowedFields = ["id", "title", "project", "tag", "maxTasks", "automation", "position", "sorting", "created", "updated"];

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

    public function getStack($stackId)
    {
        return $this->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id AND stacks_collapsed.user = '.$this->user->id, 'left')
            ->find($stackId);
    }

    public function getStacks($projectId)
    {
        return $this->select("stacks.*, stacks_collapsed.collapsed")
            ->join('stacks_collapsed', 'stacks_collapsed.stack = stacks.id AND stacks_collapsed.user = '.$this->user->id, 'left')
            ->where('stacks.project', $projectId)
            ->orderBy('position', 'ASC')
            ->findAll();
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
                throw new ErrorException($stackCollapsedModel->errors());
            }
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
        }

        $collapsed = [
            "stack" => $stackId,
            "collapsed" => $state,
            "user" => $userId
        ];

        try {
            if ($stackCollapsedModel->insert($collapsed) === false) {   
                throw new ErrorException($stackCollapsedModel->errors());
            }
        } catch (\Exception $e) {
            throw new ErrorException($e->getMessage());
        }
    }
}