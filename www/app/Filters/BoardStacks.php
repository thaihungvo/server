<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;
use App\Models\BoardModel;
use App\Models\StackModel;

class BoardStacks implements FilterInterface
{
    public function before(RequestInterface $request)
    {
        if (isset($request->board) && $request->board) {
            return $request;
        }
        
        $user = $request->user;
        $response = new \stdClass();
        $response->code = 404;
        $response->data = null;

        $stackID = $request->uri->getSegment(4);

        $boardModel = new BoardModel();
        $boardBuilder = $boardModel->builder();
        $boardQuery = $boardBuilder->select('boards.*, stacks.deleted AS stackDeleted')
            ->join('boards_members', 'boards_members.board = boards.id', 'left')
            ->join('stacks', 'stacks.board = boards.id', 'left')
            ->where('boards.deleted', NULL)
            ->where('stacks.id', $stackID)
            ->groupStart()
                ->where('boards.owner', $user->id)
                ->orWhere('boards_members.user', $user->id)
                ->orWhere('boards.public', 1)
            ->groupEnd()
            ->limit(1)
            ->get();

        $boards = $boardQuery->getResult();
        
        if (!count($boards)) {
            $response->message = 'ERR_BOARDS-NOT-FOUND-MSG';
            return Services::response()
                ->setStatusCode(404)
                ->setJSON($response);
        }

        $board = $boards[0];

        if ($board->stackDeleted) {
            $response->message = 'ERR-STACK-NOT-FOUND-MSG';
            return Services::response()
                ->setStatusCode(404)
                ->setJSON($response);
        }

        $request->board = $board;
        $request->board->stack = $stackID;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        // Do something here
    }
}