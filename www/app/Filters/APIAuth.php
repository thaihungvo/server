<?php namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use \Firebase\JWT\JWT;
use Config\Services;
use App\Models\UserModel;

class APIAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = new \stdClass();
        $response->code = 401;
        $response->data = null;

        $tokenRaw = $request->getHeaderLine("Authorization");
        $token = str_replace("Bearer ", "", $tokenRaw);

        if (!strlen($token) && $request->getGet("auth")) {
            $token = $request->getGet("auth");
        }

        if (!strlen($token)) {
            $response->message = "ERR-AUTH-TOKEN-MISSING";
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        $profile = null;
        try {
            $key = JWT_KEY;
            $profile = JWT::decode($token, $key, array("HS256"));
        } catch (\Exception $e) {
            $response->message = "ERR-AUTH-SESSION-EXPIRED";
            return Services::response()
                ->setStatusCode(401)    
                ->setJSON($response);
        }

        if (!$profile) {
            $response->message = "ERR-AUTH-PROFILE-NULL";
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        $userModel = new UserModel();
        $user = $userModel->find($profile->id);
        
        if (!$user) {
            $response->message = "ERR-AUTH-USER-NOT-FOUND";
            return Services::response()
                ->setStatusCode(401)
                ->setJSON($response);
        }

        // the instance UUID is generated on login
        // used when polling for updates for the same user logged in on different platforms/clients
        $user->instance = $profile->instance;
        $user->id = intval($user->id);
        unset($user->password);

        $request->user = $user;
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do something here
    }
}