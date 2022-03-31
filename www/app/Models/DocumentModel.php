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

    protected $afterFind = ["formatDocuments"];

    protected $validationRules = [
        "text" => "required|alpha_numeric_punct",
        "parent" => "required|alpha_numeric_punct",
        "owner" => "required|integer",
        "type" => "in_list[folder,project,notepad,people,file]",
        "position" => "required|numeric"
    ];

    protected function formatDocuments(array $data)
    {
        helper("documents");
        helper("permissions");
        $user = $this->user;

        // format single document
        if ($data["singleton"] && $data["data"]) {
            if ($data["data"]->options) {
                $data["data"]->options = json_decode($data["data"]->options);
            }

            $data["data"]->parent = intval($data["data"]->parent);
            $data["data"]->data = new \stdClass();
            $data["data"]->data->isOwner = $data["data"]->owner == $user->id;
            $data["data"]->data->owner = intval($data["data"]->owner);
            $data["data"]->data->public = boolval($data["data"]->public);
            $data["data"]->data->type = $data["data"]->type;
            $data["data"]->data->created = $data["data"]->created;
            $data["data"]->data->updated = $data["data"]->updated;
            $data["data"]->data->public = boolval($data["data"]->public);
            $data["data"]->data->counter = new \stdClass();
            $data["data"]->data->counter->total = 0;

            // removing unncessary prop
            unset($data["data"]->deleted);
            unset($data["data"]->position);
            unset($data["data"]->type);
            unset($data["data"]->owner);
            unset($data["data"]->public);
            unset($data["data"]->created);
            unset($data["data"]->updated);
            
            permissions_load_permission($data["data"], $user->id);

            $data["data"]->data->permission = $data["data"]->permission;
            unset($data["data"]->permission);
        }

        // format list of documents
        if (!$data["singleton"] && $data["data"]) {
            $projects = array();
            $people = array();

            foreach ($data["data"] as $key => &$document) {
                $document->public = boolval($document->public);

                if ($document->parent === "0") {
                    $document->parent = 0;
                } else {
                    $document->parent = null;
                }

                if ($document->type === "folder") {
                    $document->droppable = true;
                } else if ($document->type === "project") {
                    $projects[] = $document->id;
                } else if ($document->type === "people") {
                    $people[] = $document->id;
                }

                $document->data = new \stdClass();
                $document->data->type = $document->type;
                unset($document->type);
                $document->data->created = $document->created;
                unset($document->created);
                $document->data->updated = $document->updated;
                unset($document->updated);
                $document->data->public = boolval($document->public);
                unset($document->public);
                $document->data->owner = intval($document->owner);
                $document->data->isOwner = $document->owner == $user->id;
                unset($document->owner);
                unset($document->options);
                unset($document->deleted);
            }

            // load the counters used in the sidebar
            documents_load_counters($data["data"]);
            // load the documents permissions
            permissions_load_permissions($data["data"], $user->id);
        }

        return $data;
    }

    public function formatData(&$data)
    {
        helper("documents");

        // adding UUID in case it is missing
        if (!isset($data->id)) {
            helper('uuid');
            $data->id = uuid();
        }

        // checking for parent
        $data->parent = intval($data->parent);
        if (!isset($data->parent)) {
            $data->parent = 0;
        }

        // fixing options
        if (isset($data->options)) {
            $data->options = json_encode($data->options);
        } else {
            $data->options = json_encode(documents_get_default_options($data->data->type));
        }

        // fixing visibility
        if (!isset($data->data->public)) {
            $data->public = 1;
        } else {
            $data->public = intval($data->data->public);
        }

        // moving extra data info
        if (isset($data->data->type)) {
            $data->type = $data->data->type;
        }

        // setting owner to the current user
        if (!isset($data->data->owner)) {
            $data->owner = $this->user->id;
        } else {
            $data->owner = intval($data->data->owner);
        }

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
    }
}