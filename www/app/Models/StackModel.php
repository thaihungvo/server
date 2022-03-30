<?php namespace App\Models;

use CodeIgniter\Model;
use App\Models\StackCollapsedModel;

class StackModel extends Model
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

    protected function formatStacks(array $data)
    {
        // format single stack
        if ($data["singleton"] && $data["data"]) {
            $data["data"]->position = intval($data["data"]->position);

            if (isset($data["data"]->tag) && is_string($data["data"]->tag)) {
                $data["data"]->tag = json_decode($data["data"]->tag);
            }

            if (isset($data["data"]->automation) && is_string($data["data"]->automation)) {
                $data["data"]->automation = json_decode($data["data"]->automation);
            }

            $stackCollapsedModel = new StackCollapsedModel();
            $stackCollapsed = $stackCollapsedModel->where("stack", $data["data"]->id)->first();
            $data["data"]->collapsed = boolval($stackCollapsed->collapsed);
        }

        return $data;
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