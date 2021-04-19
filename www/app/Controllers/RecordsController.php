<?php namespace App\Controllers;

use CodeIgniter\Events\Events;
use App\Models\FolderModel;
use App\Models\RecordModel;
use App\Models\RecordMemberModel;
use App\Models\RecordOrderModel;

class RecordsController extends BaseController
{
    public function all_v1()
	{
        $user = $this->request->user;

        // get all the folders
        $folderModel = new FolderModel();
        $folderBuilder = $folderModel->builder();
        $folderQuery = $folderBuilder->select("folders.id, folders.title, folders.created, folders.updated")
            ->join("folders_members", "folders_members.folder = folders.id", "left")
            ->where("folders.deleted", NULL)
            ->groupStart()
                ->where("folders.owner", $user->id)
                ->orWhere("folders_members.user", $user->id)
                ->orWhere("folders.everyone", 1)
            ->groupEnd()
            ->groupBy("folders.id")
            ->orderBy("folders.order", "ASC")
            ->get();
        $folders = $folderQuery->getResult();

        // get all the records
        $recordsModel = new RecordModel();
        $recordsBuilder = $recordsModel->builder();
        $recordsQuery = $recordsBuilder->select("records.id, records.title, records.type, records.updated, records.created, records_order.folder")
            ->join("records_members", "records_members.record = records.id", "left")
            ->join("records_order", "records_members.record = records.id", "left")
            ->where("records.deleted", NULL)
            ->groupStart()
                ->where("records.owner", $user->id)
                ->orWhere("records_members.user", $user->id)
                ->orWhere("records.everyone", 1)
            ->groupEnd()
            ->groupBy("records.id")
            ->orderBy("records_order.order", "ASC")
            ->get();
        $records = $recordsQuery->getResult();

        foreach ($folders as &$folder) {
            if (!isset($folder->records)) {
                $folder->records = array();
            }

            foreach ($records as &$record) {
                if ($record->folder === $folder->id) {
                    unset($record->folder);
                    $folder->records[] = $record;
                }
            }
        }

        return $this->reply($folders);
    }

	public function one_v1($id)
	{
        $record = $this->request->record;
        $data = null;

        switch ($record->type) {
            case 'project':
                helper("projects");
                $data = projects_load($record);
                break;
            
            default:
                # code...
                break;
        }

        return $this->reply($data);
    }

    public function add_v1()
    {
        $this->add_update_v1(false);
    }

    public function update_v1($id)
    {
        $this->add_update_v1(true);
    }

    private function add_update_v1($update = false)
    {
        helper('uuid');

        $user = $this->request->user;
        $recordData = $this->request->getJSON();

        // create record object
        $record = new \stdClass();
        $record->id = $recordData->id;
        $record->title = $recordData->title;
        $record->owner = $user->id;
        $record->type = "project";

        // in case everyone was not set will enforce it to 1
        if (!isset($recordData->everyone)) {
            $record->everyone = 1;
        } else {
            $record->everyone = intval($recordData->everyone);
        }

        // setting the record type
        if (isset($recordData->type)) {
            $record->type = $recordData->type;
        }

        // set record ID in case is missing
        if (!isset($record->id)) {
            $record->id = uuid();
        }

        // getting the members array
        // and setting everyone to 0 in case there are members
        $membersIDs = array();
        if (isset($recordData->members)) {
            $membersIDs = $recordData->members;
            $record->everyone = 0;
        }

        // $recordData->archived_order = 'title-asc';
        // if (!isset($recordData->hourlyFee)) {
        //     $recordData->hourlyFee = 0;
        // }
        // if (!isset($recordData->feeCurrency)) {
        //     $recordData->feeCurrency = "USD";
        // }
        // if (isset($recordData->tags)) {
        //     if (!$this->set_tags($recordData->id, $recordData->tags)) {
        //         return $this->reply(null, 500, "ERR-BOARD-TAGS");   
        //     }
        // }
        
        // save the record
        $recordModel = new RecordModel();
        
        if ($update) {
            try {
                if ($recordModel->update($record->id, $record) === false) {
                    $errors = $recordModel->errors();
                    // TODO: need to change the error message based on the type of the record updated
                    return $this->reply($errors, 500, "ERR-RECORD-UPDATE");
                }
            } catch (\Exception $e) {
                // TODO: need to change the error message based on the type of the record updated
                return $this->reply($e->getMessage(), 500, "ERR-RECORD-UPDATE");
            }

            Events::trigger("AFTER_record_UPDATE", $record->id);
        } else {
            try {
                if ($recordModel->insert($record) === false) {
                    $errors = $recordModel->errors();
                    // TODO: need to change the error message based on the type of the record created
                    return $this->reply($errors, 500, "ERR-RECORD-CREATE");
                }
            } catch (\Exception $e) {
                // TODO: need to change the error message based on the type of the record created
                return $this->reply($e->getMessage(), 500, "ERR-RECORD-CREATE");
            }
        
            // assign record to folder
            $recordOrder = new \stdClass();
            $recordOrder->folder = $recordData->folder;
            $recordOrder->record = $record->id;
            $recordOrder->order = 1;

            $recordOrderModel = new RecordOrderModel();
            $lastOrder = $recordOrderModel->where("folder", $recordData->folder)
                ->orderBy("order", "desc")
                ->first();

            if ($lastOrder) {
                $recordOrder->order = intval($lastOrder->order) + 1;
            }

            try {
                if ($recordOrderModel->insert($recordOrder) === false) {
                    $errors = $recordOrderModel->errors();
                    return $this->reply($errors, 500, "ERR-RECORD-ORDER");
                }
            } catch (\Exception $e) {
                return $this->reply($e->getMessage(), 500, "ERR-RECORD-ORDER");
            }

            // if members are defined then assigned them to the board
            if (count($membersIDs)) {
                $recordMemberModel = new RecordMemberModel();
                $recordMemberBuilder = $recordMemberModel->builder();

                $members = array();
                foreach ($membersIDs as $userID) {
                    $members[] = [
                        'record' => $record->id,
                        'user' => $userID
                    ];
                }

                try {
                    if ($recordMemberBuilder->insertBatch($members) === false) {
                        $errors = $recordMemberBuilder->errors();
                        return $this->reply($errors, 500, "ERR-RECORD-MEMBERS");    
                    }
                } catch (\Exception $e) {
                    return $this->reply($e->getMessage(), 500, "ERR-RECORD-MEMBERS");
                }
            }

            Events::trigger("AFTER_record_ADD", $record->id);
        }

        return $this->reply($record, 200, "OK-RECORD-CREATE-SUCCESS");
    }
}
