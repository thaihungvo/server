<?php namespace App\Models;

use CodeIgniter\Model;

class TaskExtensionModel extends Model
{
    protected $table      = 'tasks_extensions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $useSoftDeletes = false;

    protected $allowedFields = ['id', 'task', 'title', 'type', 'content', 'options'];

    protected $useTimestamps = true;
    protected $createdField  = 'created';
    protected $updatedField  = '';
    protected $deletedField  = '';

    protected $afterFind = ["formatExtensions"];

    protected $validationRules = [
        'id' => 'required|min_length[20]',
        'task' => 'required|min_length[20]',
        'type' => 'required|string'
    ];

    protected function formatExtension(&$extension)
    {
        $extension->options = json_decode($extension->options);
        $extension->content = json_decode($extension->content);
    }

    protected function formatExtensions(array $data)
    {

        // format list of extensions
        if (!$data["singleton"] && $data["data"]) {
            foreach ($data["data"] as $key => &$extension) {
                $this->formatExtension($extension);
            }
        }

        return $data;
    }

    public function getTaskExtensions($taskId)
    {
        return $this
            ->where("task", $taskId)
            ->findAll();
    }

    public function getTasksExtensions($tasksIds)
    {
        return $this
            ->where("task", $taskId)
            ->findAll();
    }
}