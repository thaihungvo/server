<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;
use App\Models\BoardModel;

class BoardTasks implements FilterInterface
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

        $taskID = $request->uri->getSegment(4);

        $boardModel = new BoardModel();

        $builder = $boardModel->builder();
        $query = $builder->select('boards.*, tasks.deleted AS taskDeleted')
            ->join('boards_members', 'boards_members.board = boards.id', 'left')
            ->join('tasks_order', 'tasks_order.board = boards.id', 'left')
            ->join('tasks', 'tasks.id = tasks_order.task', 'left')
            ->where('boards.deleted', NULL)
            ->where('tasks_order.task', $taskID)
            ->groupStart()
                ->where('boards.owner', $user->id)
                ->orWhere('boards_members.user', $user->id)
                ->orWhere('boards.public', 1)
            ->groupEnd()
            ->limit(1)
            ->get();

        $boards = $query->getResult();
        
        if (!count($boards)) {
            $response->message = 'ERR-BOARDS-NOT-FOUND-MSG';
            return Services::response()
                ->setStatusCode(404)
                ->setJSON($response);
        }

        $board = $boards[0];

        if ($board->taskDeleted) {
            $response->message = 'ERR-TASK-NOT-FOUND-MSG';
            return Services::response()
                ->setStatusCode(404)
                ->setJSON($response);
        }

        $request->board = $board;
        $request->board->task = $taskID;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        // Do something here
    }
}