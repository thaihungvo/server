<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use \Firebase\JWT\JWT;
use Config\Services;
use App\Models\UserModel;

class APIAuth implements FilterInterface
{
    public function before(RequestInterface $request)
    {
        $response = new \stdClass();
        $response->code = 401;
        $response->data = null;

        $tokenRaw = $request->getHeaderLine('Authorization');
        $token = str_replace('Bearer ', '', $tokenRaw);

        if (!strlen($token)) {
            $response->message = 'ERR_AUTH_TOKEN_MISSING';
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        $profile = null;
        try {
            $key = JWT_KEY;
            $profile = JWT::decode($token, $key, array('HS256'));
        } catch (\Exception $e) {
            $response->message = 'ERR_AUTH_SESSION_EXPIRED';
            return Services::response()
                ->setStatusCode(401)    
                ->setJSON($response);
        }

        if (!$profile) {
            $response->message = 'ERR_AUTH_PROFILE_NULL';
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        $userModel = new UserModel();
        $user = $userModel->find($profile->id);
        
        if (!$user) {
            $response->message = 'ERR_AUTH_USER_NOT_FOUND';
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        $request->user = $user;
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response)
    {
        // Do something here
    }
}