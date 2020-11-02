<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;


class Task implements FilterInterface
{
    public function before(RequestInterface $request)
    {
        $body = $request->getJSON();
        // if (strlen($body->created) > 20) {
        //     $body->created = substr(str_replace("T", " ", $body->created), 0, 19);
        // }

        // if (strlen($body->updated) > 20) {
        //     $body->updated = substr(str_replace("T", " ", $body->updated), 0, 19);
        // }

        // if (strlen($body->archived) > 20) {
        //     $body->archived = substr(str_replace("T", " ", $body->archived), 0, 19);
        // }

        if (is_array($body->tags)) {
            $body->tags = json_encode($body->tags);
        }
        
        $request->setBody(json_encode($body));

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        //
    }
}