<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;


class Task implements FilterInterface
{
    public function before(RequestInterface $request)
    {
        $body = $request->getJSON();
        if (is_array($body->tags)) {
            $body->tags = json_encode($body->tags);
        }
        
        $request->setBody(json_encode($body));

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        // Do something here
    }
}