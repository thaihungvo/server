<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;
use App\Models\RecordModel;

class Record implements FilterInterface
{
    public function before(RequestInterface $request)
    {
        $user = $request->user;
        $response = new \stdClass();
        $response->code = 404;
        $response->data = null;

        $recordModel = new RecordModel();

        $recordBuilder = $recordModel->builder();
        $recordQuery = $recordBuilder->select("records.*")
            ->join("records_members", "records_members.record = records.id", "left")
            ->where("records.deleted", NULL)
            ->where("records.id", $request->uri->getSegment(4))
            ->groupStart()
                ->where("records.owner", $user->id)
                ->orWhere("records_members.user", $user->id)
                ->orWhere("records.public", 1)
            ->groupEnd()
            ->limit(1)
            ->get();

        $records = $recordQuery->getResult();
        
        if (!count($records)) {
            $response->message = "ERR-RECORDS-NOT-FOUND-MSG";
            return Services::response()
                ->setStatusCode(404)
                ->setJSON($response);
        }

        $request->record = $records[0];
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        // Do something here
    }
}