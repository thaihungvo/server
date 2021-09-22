<?php namespace App\Controllers;

use App\Models\DocumentModel;

class DocumentsController extends BaseController
{
    public function all_v1()
	{
        $user = $this->request->user;

        // get all the documents
        $documentModel = new DocumentModel();
        $documentBuilder = $documentModel->builder();
        $documentQuery = $documentBuilder->select("documents.id, documents.text, documents.type, documents.updated, documents.created, documents.parent")
            ->join("documents_members", "documents_members.document = documents.id", "left")
            ->where("documents.deleted", NULL)
            ->groupStart()
                ->where("documents.owner", $user->id)
                ->orWhere("documents_members.user", $user->id)
                ->orWhere("documents.everyone", 1)
            ->groupEnd()
            ->groupBy("documents.id")
            ->orderBy("documents.position", "ASC")
            ->get();
        $documents = $documentQuery->getResult();

        $folders = array();
        foreach ($documents as &$document) {
            if ($document->parent === "0") {
                $document->parent = 0;
            }

            if ($document->type === $this::TYPE_FOLDER) {
                // $folders[] = $document;
                $document->droppable = true;
            }

            $document->data = new \stdClass();
            $document->data->type = $document->type;
            unset($document->type);
            $document->data->created = $document->created;
            unset($document->created);
            $document->data->updated = $document->updated;
            unset($document->updated);
        }

        $response = new \stdClass();
        $response->documents = $documents;
        $response->tags = array();

        return $this->reply($response);
    }

	public function one_v1($documentId)
	{
        $user = $this->request->user;
        helper("documents");
        return $this->reply(documents_load($documentId, $user));
    }

    public function add_v1()
    {
        $documentModel = new DocumentModel();
        $user = $this->request->user;
        $documentData = $documentModel->toDB($this->request->getJSON());
        $documentData->owner = $user->id;

        // inserting the new document
        try {
            if ($documentModel->insert($documentData) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-CREATE");
        }

        // inserting activity
        $this->addActivity($documentData->parent, $documentData->id, $this::ACTION_CREATE, $this::SECTION_DOCUMENTS);
        return $this->reply(documents_load($documentData->id, $user));
    }

    public function update_v1($documentId)
    {
        $documentModel = new DocumentModel();
        $user = $this->request->user;
        $documentData = $documentModel->toDB($this->request->getJSON());

        // check for unknown record types
        if (!isset($documentId)) {
            return $this->reply("Document `id` missing or not valid", 500, "ERR-DOCUMENTS-UPDATE");
        }

        $this->lock($documentId);

        // checking if the requested document exists
        helper("documents");
        $document = documents_load($documentId, $user);
        if (!$document) {
            return $this->reply("Document not found", 404, "ERR-DOCUMENTS-UPDATE");
        }

        // fixing data id prop
        $documentData->id = $document->id;

        // reverting back to the old parent in case it's missing
        if (!isset($documentData->parent)) {
            $documentData->parent = $document->parent;
        }

        // adding updated date in case it's missing
        if (!isset($documentData->updated)) {
            $documentData->updated = date("Y-m-d H:i:s");
        }

        // just in case someone tries to change types
        unset($documentData->type);
        unset($documentData->created);

        // checking if someone without privilages changes the visibility
        if ($documentData->everyone != $document->everyone && $documentData->owner != $document->owner) {
            $documentData->everyone = $document->everyone;
        }
        // checking that the owner stays the same
        $documentData->owner = $document->owner;

        // updating the document
        try {
            if ($documentModel->update($document->id, $documentData) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-UPDATE");
        }

        // inserting activity
        $this->addActivity($documentData->parent, $documentData->id, $this::ACTION_UPDATE, $this::SECTION_DOCUMENTS);
        return $this->reply(true);
    }

    public function order_v1()
    {
        $orderData = $this->request->getJSON();
        $db = db_connect();
        
        // get all documents
        $documentModel = new DocumentModel();
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
                "INSERT INTO ".$db->prefixTable("documents")." (`id`, `parent`, `position`) VALUES"
            );

            foreach ($documentsNeedUpdate as $i => $document) {
                $value = "(". $db->escape($document->id) .", ". $db->escape($document->parent) .", ". $db->escape($document->order) .")";
                if ($i < count($documentsNeedUpdate) - 1) {
                    $value .= ",";
                }
                $documentsOrderQuery[] = $value;
            }

            $documentsOrderQuery[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `parent`=VALUES(`parent`), `position`=VALUES(`position`);";
            $documentsQuery = implode(" ", $documentsOrderQuery);

            if (!$db->query($documentsQuery)) {
                return $this->reply("Unable to update documents order", 500, "ERR-DOCUMENTS-REORDER");
            }
        }

        return $this->reply(true);
    }

    public function delete_v1($id)
    {
        $user = $this->request->user;
        helper("documents");

        $document = documents_load($id, $user);

        if (!isset($document->id)) {
            return $this->reply("Document not found", 404, "ERR-DOCUMENTS-DELETE");
        }

        // documents that require other to be deleted
        $documentsToCleanUp = array();
        if ($document->type !== "folder") {
            $documentsToCleanUp[] = $document;
        }

        $documentModel = new DocumentModel();
        try {
            if ($documentModel->delete([$document->id]) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-DELETE");
        }

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
                    return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-DELETE");
                }
            }
        }

        // cleaning up
        if (count($documentsToCleanUp)) {
            documents_clean_up($documentsToCleanUp);
        }

        $this->addActivity($document->parent, $document->id, $this::ACTION_DELETE, $this::SECTION_DOCUMENTS);
        return $this->reply(true);
    }

    public function update_options_v1($documentId)
    {
        $options = $this->request->getJSON();
        $user = $this->request->user;
        helper("documents");

        $document = documents_load($documentId, $user);

        if (!isset($document->id)) {
            return $this->reply("Document not found", 404, "ERR-DOCUMENTS-OPTIONS");
        }

        $documentModel = new DocumentModel();
        try {
            if ($documentModel->update($document->id, ["options" => json_encode($options)]) === false) {
                return $this->reply($documentModel->errors(), 500, "ERR-DOCUMENTS-OPTIONS");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-DOCUMENTS-OPTIONS");
        }

        return $this->reply(true);
    }
}
