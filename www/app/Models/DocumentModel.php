<?php namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table      = "documents";
    protected $primaryKey = "id";
    protected $returnType = "object";

    protected $allowedFields = ["id", "text", "parent", "owner", "public", "type", "position", "options"];

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $createdField  = "created";
    protected $updatedField  = "updated";
    protected $deletedField  = "deleted";

    protected $validationRules = [
        "id" => "required|min_length[20]",
        "text" => "required|alpha_numeric_punct",
        "parent" => "required|alpha_numeric_punct",
        "owner" => "required|integer",
        "type" => "in_list[folder,project,notepad,people,file]",
        "position" => "required|numeric"
    ];

    public function toDB($data) 
    {
        // adding UUID in case it is missing
        if (!isset($data->id)) {
            helper('uuid');
            $data->id = uuid();
        }

        // checking for parent
        if (!isset($data->parent)) {
            $data->parent = 0;
        }

        // moving extra data info
        if (isset($data->data->type)) {
            $data->type = $data->data->type;
        }
        if (isset($data->data->created)) {
            $data->created = $data->data->created;
        }
        if (isset($data->data->updated)) {
            $data->updated = $data->data->updated;
        }
        unset($data->data);

        // Fixing position
        if (!isset($data->position) && isset($data->type)) {
            $data->position = 1;

            $documentModel = new DocumentModel();
            $documentModel
                ->where("parent", $data->parent)
                ->orderBy("position", "desc");
    
            $lastPosition = $documentModel->first();
    
            if ($lastPosition) {
                $data->position = intval($lastPosition->position) + 1;
            }
        }

        // fixing options
        if (isset($data->options)) {
            $data->options = json_encode($data->options);
        } else {
            helper("documents");
            $data->options = json_encode(documents_get_default_options($data->type));
        }

        // fixing visibility
        if (!isset($data->public)) {
            $data->public = 1;
        }

        return $data;
    } 
}