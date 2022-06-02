<?php namespace App\Controllers;

use App\Models\StatusModel;

class StatusesController extends BaseController
{
    public function all_v1()
    {
        $statusModel = new StatusModel();
        return $this->reply($statusModel->findAll());
    }

    public function add_v1()
    {        
        $statusModel = new StatusModel();
        $statusData = $this->request->getJSON();

        helper('uuid');

        if (!isset($statusData->id)) {
            $statusData->id = uuid();
        }

        try {
            if ($statusModel->insert($statusData) === false) {
                return $this->reply($statusModel->errors(), 500, "ERR-STATUSES-CREATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STATUSES-CREATE");
        }

        return $this->reply($statusData);
    }

    public function update_v1($idStatus)
	{
        $statusModel = new StatusModel();
        $status = $statusModel->find($idStatus);

        if (!$status) {
            return $this->reply(null, 404, "ERR-STATUS-NOT-FOUND");
        }

        $statusData = $this->request->getJSON();

        unset($statusData->id);
        unset($statusData->create);
        if (!isset($statusData->updated)) {
            $statusData->updated = date('Y-m-d H:i:s');
        }

        try {
            if ($statusModel->update($status->id, $statusData) === false) {
                return $this->reply($statusModel->errors(), 500, "ERR-STATUSES-UPDATE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STATUSES-UPDATE");
        }

        return $this->reply(true);
    }

    public function delete_v1($idStatus)
	{
        $statusModel = new StatusModel();
        $status = $statusModel->find($idStatus);

        if (!$status) {
            return $this->reply(null, 404, "ERR-STATUS-NOT-FOUND");
        }

        try {
            if ($statusModel->delete([$idStatus]) === false) {
                return $this->reply($statusModel->errors(), 500, "ERR-STATUS-DELETE");
            }    
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-STATUS-DELETE");
        }

        return $this->reply(true);
    }
}