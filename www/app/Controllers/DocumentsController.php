<?php namespace App\Controllers;

use App\Models\DocumentModel;
use App\Models\AttachmentModel;
use App\Models\TagModel;
use App\Models\StatusModel;

class DocumentsController extends BaseController
{
    protected $permissionSection = "documents";

    public function all_v1()
	{
        $documentModel = new DocumentModel($this->request->user);
        $response = new \stdClass();
        $response->documents = $documentModel->getDocuments();

        // normalize permissions
        $response->permissions = new \stdClass();
        foreach ($response->documents as &$document) {
            $docId = $document->id;
            $response->permissions->$docId = $document->data->permissions;
            unset($document->data->permissions);
        }


        // load the global tags
        $tagModel = new TagModel();
        $response->tags = $tagModel->where("project", NULL)->findAll();
        foreach ($response->tags as &$tag) {
            unset($tag->project);
        }

        // load the global statuses
        $statusModel = new StatusModel();
        $response->statuses = $statusModel->where("project", NULL)->findAll();
        foreach ($response->statuses as &$status) {
            unset($status->project);
        }

        return $this->reply($response);
    }

	public function one_v1($documentId)
	{
        if (!isset($documentId)) {
            return $this->reply(null, 500, "ERR-DOCUMENTS-GET");
        }

        $document = $this->getExpandedDocument($documentId);
        $this->exists($document);

        return $this->reply($document);
    }

    public function add_v1()
    {
        $documentModel = new DocumentModel($this->request->user);
        $data = $this->request->getJSON();
        $documentModel->formatData($data);

        $this->db->transStart();

        // inserting the new document
        try {
            if ($documentModel->insert($data) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-CREATE");
        }

        // inserting the default document permission
        try {
            $this->addPermission($data->id, $this::PERMISSION_TYPE_DOCUMENT);
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-CREATE");
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return $this->reply(null, 500, "ERR-DOCUMENTS-CREATE");
        }

        // inserting activity
        $this->addActivity(
            $data->id,
            $data->parent,
            $data->id,
            $this::ACTION_CREATE,
            $this::SECTION_DOCUMENTS
        );
        
        $document = $this->getDocument($data->id);
        return $this->reply($document);
    }

    public function update_v1($documentId)
    {
        // check for unknown record types
        if (!isset($documentId)) {
            return $this->reply("Document `id` missing or not valid", 500, "ERR-DOCUMENTS-UPDATE");
        }

        $documentModel = new DocumentModel($this->request->user);
        
        // checking if the requested document exists
        $document = $this->getDocument($documentId);
        $this->exists($document);

        // checking user permissions to update this document
        $this->can("update", $document);

        $data = $this->request->getJSON();
        $data->id = $documentId;
        $documentModel->formatData($data);

        $this->lock($data->id);

        // reverting back to the old parent in case it's missing
        if (!isset($data->parent)) {
            $data->parent = $document->parent;
        }

        // adding updated date in case it's missing
        if (!isset($data->updated)) {
            $data->updated = date("Y-m-d H:i:s");
        }

        unset($data->type); // just in case someone tries to change types
        unset($data->created);

        // checking if someone without privilages changes the visibility
        if (isset($data->public) && $data->public != $document->data->public && $data->owner != $document->data->owner) {
            return $this->reply(null, 403, "You do not have permission to perform this action. 1");
        }

        // checking if someone without privilages changes the owner
        if (isset($data->owner) && $data->owner != $document->data->owner && $data->owner != $document->data->owner) {
            return $this->reply(null, 403, "You do not have permission to perform this action. 2");
        }

        $this->db->transStart();

        // updating the document
        try {
            if ($documentModel->update($document->id, $data) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-UPDATE");
        }

        // in case it's a file document
        // we also need to rename the file
        if ($document->type === "file") {
            $attachmentModel = new AttachmentModel();
            try {
                if ($attachmentModel->wherein("resource", [$document->id])->set("content", $data->text)->update() === false) {
                    return $this->reply($attachmentModel->errors(), 500, "ERR-DOCUMENTS-UPDATE");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-UPDATE");
            }
            
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            return $this->reply(null, 500, "ERR-DOCUMENTS-UPDATE");
        }

        // inserting activity
        $this->addActivity(
            $data->id,
            $data->parent,
            $data->id,
            $this::ACTION_UPDATE,
            $this::SECTION_DOCUMENTS
        );
        return $this->reply(true);
    }

    public function order_v1()
    {
        $orderData = $this->request->getJSON();
        
        // get all documents
        $documentModel = new DocumentModel($this->request->user);
        $documents = $documentModel->orderBy("position", "asc")->findAll();

        $documentsNeedUpdate = array();
        foreach ($orderData as $parentId => $documentsOrder) {
            foreach ($documentsOrder as $documentOrder => $documentId) {
                $document = new \stdClass();
                $document->id = $documentId;
                $document->order = $documentOrder + 1;
                $document->parent = $parentId;
                $documentsNeedUpdate[] = $document;
            }
        }


        // update documents order
        if (count($documentsNeedUpdate)) {
            $documentsOrderQuery = array(
                "INSERT INTO ".$this->db->prefixTable("documents")." (`id`, `parent`, `position`) VALUES"
            );

            foreach ($documentsNeedUpdate as $i => $document) {
                $value = "(". $this->db->escape($document->id) .", ". $this->db->escape($document->parent) .", ". $this->db->escape($document->order) .")";
                if ($i < count($documentsNeedUpdate) - 1) {
                    $value .= ",";
                }
                $documentsOrderQuery[] = $value;
            }

            $documentsOrderQuery[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `parent`=VALUES(`parent`), `position`=VALUES(`position`);";
            $documentsQuery = implode(" ", $documentsOrderQuery);

            if (!$this->db->query($documentsQuery)) {
                return $this->reply("Unable to update documents order", 500, "ERR-DOCUMENTS-REORDER");
            }
        }

        return $this->reply(true);
    }

    public function delete_v1($documentId)
    {        
        $document = $this->getDocument($documentId);
        $this->exists($document);

        // checking user permissions to change this documents options
        $this->can("delete", $document);
        
        // documents that require other to be deleted
        $documentsToCleanUp = array();
        if ($document->type !== "folder") {
            $documentsToCleanUp[] = $document;
        }
        
        $documentModel = new DocumentModel($this->request->user);
        try {
            if ($documentModel->delete([$document->id]) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-DELETE");
        }

        helper("documents");

        // in case the user deleted a folder
        // then also delete all sub documents
        if ($document->type === "folder") {
            $documents = $documentModel->findAll();
            $documentsToDelete = documents_get_tree($documents, $document->id);
            $idsToDelete = array();

            foreach ($documentsToDelete as $documentToDelete) {
                $idsToDelete[] = $documentToDelete->id;
                if ($documentToDelete->type !== "folder") {
                    $documentsToCleanUp[] = $documentToDelete;
                }
            }

            if (count($idsToDelete)) {
                try {
                    if ($documentModel->delete($idsToDelete) === false) {
                        return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-DELETE");
                    }    
                } catch (\Exception $e) {
                    return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-DELETE4");
                }
            }
        }

        // cleaning up
        if (count($documentsToCleanUp)) {
            if (!documents_clean_up($documentsToCleanUp)) {
                return $this->reply("Unable to delete document", 500, "ERR-DOCUMENTS-DELETE");
            }
        }

        $this->addActivity(
            $document->id,
            $document->parent,
            $document->id,
            $this::ACTION_DELETE,
            $this::SECTION_DOCUMENTS
        );

        return $this->reply(true);
    }

    public function update_options_v1($documentId)
    {
        $options = $this->request->getJSON();
        $document = $this->getDocument($documentId);
        $this->exists($document);

        // checking user permissions to change this documents options
        $this->can("update", $document);

        $documentModel = new DocumentModel($this->request->user);
        try {
            if ($documentModel->update($document->id, ["options" => json_encode($options)]) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-OPTIONS");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-OPTIONS");
        }

        return $this->reply(true);
    }

    public function attachments_v1($documentId)
    {        
        $document = $this->getDocument($documentId);
        $this->exists($document);

        $attachmentModel = new AttachmentModel();
        $attachments = $attachmentModel->where("resource", $documentId)->find();

        return $this->reply($attachments);
    }
}
