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
        $documentQuery = $documentBuilder->select("documents.id, documents.title, documents.type, documents.updated, documents.created, documents.folder, documents.order")
            ->join("documents_members", "documents_members.document = documents.id", "left")
            // ->join("documents_order", "documents_members.document = documents.id", "left")
            ->where("documents.deleted", NULL)
            ->groupStart()
                ->where("documents.owner", $user->id)
                ->orWhere("documents_members.user", $user->id)
                ->orWhere("documents.everyone", 1)
            ->groupEnd()
            ->groupBy("documents.id")
            ->orderBy("documents.order", "ASC")
            ->get();
        $documents = $documentQuery->getResult();

        $folders = array();
        foreach ($documents as $document) {
            if ($document->type === $this::TYPE_FOLDER) {
                $folders[] = $document;
            }
        }

        foreach ($folders as &$folder) {
            if (!isset($folder->records)) {
                $folder->records = array();
            }
            unset($folder->order);
            foreach ($documents as &$document) {
                if ($document->type !== $this::TYPE_FOLDER && $document->folder === $folder->id) {
                    unset($document->folder);
                    unset($document->order);
                    $folder->records[] = $document;
                }
            }
        }

        return $this->reply($folders);
    }

	public function one_v1($documentId)
	{
        $user = $this->request->user;
        helper("documents");

        $document = documents_load($documentId, $user);

        return $this->reply($document);
    }

    public function add_v1()
    {
        $user = $this->request->user;
        $documentData = $this->request->getJSON();
        $documentData->owner = $user->id;

        if (!isset($documentData->id)) {
            helper('uuid');
            $documentData->id = uuid();
        }

        helper("documents");

        // check for unknown record types
        if (!documents_validate_type($documentData->type)) {
            return $this->reply("Document type missing or not valid", 500, "ERR-DOCUMENTS-CREATE");
        }

        if ($documentData->type !== $this::TYPE_FOLDER && !isset($documentData->folder)) {
            return $this->reply("Missing folder id", 500, "ERR-DOCUMENTS-CREATE");
        }

        $result = documents_create($documentData);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-DOCUMENTS-CREATE");
        }
        
        $this->addActivity($documentData->folder || "", $documentData->id, $this::ACTION_CREATE, $documentData->type);
        
        return $this->reply(documents_load($documentData->id, $user));
    }

    public function update_v1($documentId)
    {
        $user = $this->request->user;
        $recordData = $this->request->getJSON();

        // check for unknown record types
        if (!isset($documentId)) {
            return $this->reply("Document `id` missing or not valid", 500, "ERR-DOCUMENTS-UPDATE");
        }

        $this->lock($documentId);

        helper("documents");

        // check for unknown record types
        if (!documents_validate_type($recordData->type)) {
            return $this->reply("Document `type` missing or not valid", 500, "ERR-DOCUMENTS-UPDATE");
        }

        if ($recordData->type !== $this::TYPE_FOLDER && !isset($recordData->folder)) {
            return $this->reply("Document `folder` missing or not valid", 500, "ERR-DOCUMENTS-UPDATE");
        }

        $document = documents_load($documentId, $user);
        if (!$document) {
            return $this->reply("Document not found", 404, "ERR-DOCUMENTS-UPDATE");
        }

        $result = documents_update($recordData);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-DOCUMENTS-UPDATE");
        }

        return $this->reply(true);
    }

    public function order_v1()
    {
        $orderData = $this->request->getJSON();
        $db = db_connect();
        
        // get all documents
        $documentModel = new DocumentModel();
        $documents = $documentModel->orderBy("order", "asc")->findAll();

        $foldersNeedUpdate = array();
        $documentsNeedUpdate = array();

        $folderOrder = 1;
        
        foreach ($orderData as $folderID => $documentsOrder) {
            foreach ($documents as &$document) {
                if (
                    $document->type === $this::TYPE_FOLDER &&
                    $document->id === $folderID &&
                    $document->order != $folderOrder
                ) {
                    $document->order = $folderOrder;
                    $foldersNeedUpdate[] = $document;
                }
            }

            $documentOrder = 1;

            foreach ($documentsOrder as $documentID) {
                foreach ($documents as &$document) {
                    if (
                        $document->type !== $this::TYPE_FOLDER &&
                        $document->id === $documentID &&
                        ($document->order != $documentOrder || $document->folder != $folderID)
                    ) {
                        $document->order = $documentOrder;
                        $document->folder = $folderID;
                        $documentsNeedUpdate[] = $document;
                    }
                }

                $documentOrder++;
            }
            

            $folderOrder++;
        }

        // update folders order
        if (count($foldersNeedUpdate)) {
            $foldersOrderQuery = array(
                "INSERT INTO ".$db->prefixTable("documents")." (`id`, `order`) VALUES"
            );

            foreach ($foldersNeedUpdate as $i => $folder) {
                $value = "(". $db->escape($folder->id) .", ". $db->escape($folder->order) .")";
                if ($i < count($foldersNeedUpdate) - 1) {
                    $value .= ",";
                }
                $foldersOrderQuery[] = $value;
            }

            $foldersOrderQuery[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `order`=VALUES(`order`);";
            $foldersQuery = implode(" ", $foldersOrderQuery);

            if (!$db->query($foldersQuery)) {
                return $this->reply("Unable to update folders order", 500, "ERR-DOCUMENTS-REORDER");
            }
        }

        // update documents order
        if (count($documentsNeedUpdate)) {
            $documentsOrderQuery = array(
                "INSERT INTO ".$db->prefixTable("documents")." (`id`, `folder`, `order`) VALUES"
            );

            foreach ($documentsNeedUpdate as $i => $document) {
                $value = "(". $db->escape($document->id) .", ". $db->escape($document->folder) .", ". $db->escape($document->order) .")";
                if ($i < count($documentsNeedUpdate) - 1) {
                    $value .= ",";
                }
                $documentsOrderQuery[] = $value;
            }

            $documentsOrderQuery[] = "ON DUPLICATE KEY UPDATE id=VALUES(id), `folder`=VALUES(`folder`), `order`=VALUES(`order`);";
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

        $result = documents_delete($document);

        if ($result !== true) {
            return $this->reply($result, 500, "ERR-DOCUMENTS-DELETE");
        }

        switch ($document->type) {
            case $this::TYPE_FOLDER:
                break;
            case $this::TYPE_PROJECT:
                $this->addActivity($document->folder, $document->id, $this::ACTION_DELETE, $this::SECTION_DOCUMENT);
                break;
        }

        return $this->reply(true);
    }
}
