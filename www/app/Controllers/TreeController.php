<?php namespace App\Controllers;

use App\Models\RecordModel;
use App\Models\FolderModel;

class TreeController extends BaseController
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
            if (!isset($folder->children)) {
                $folder->children = array();
            }

            foreach ($records as &$record) {
                if ($record->folder === $folder->id) {
                    unset($record->folder);
                    $folder->children[] = $record;
                }
            }
        }

        return $this->reply($folders);
    }
}