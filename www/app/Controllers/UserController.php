<?php namespace App\Controllers;

use App\Models\UserModel;
use \Firebase\JWT\JWT;

class UserController extends BaseController
{
	public function login_v1()
	{
        $userData = $this->request->getJSON();

        $userModel = new UserModel();
        $user = $userModel->where('email', $userData->email)->first();

        if (!$user) {
            return $this->reply(null, 401, "ERR-USER-LOGIN-WRONG-EMAIL-PASS");
        }

        if (!password_verify($userData->password, $user->password)){
            return $this->reply(null, 401, "ERR-USER-LOGIN-WRONG-EMAIL-PASS");
        }

        helper('uuid');

        $payload = array(
            "id" => $user->id,
            "email" => $user->email,
            "firstName" => $user->firstName,
            "lastName" => $user->lastName,
            "instance" => uuid()
        );

        unset($user->password);
        $user->instance = $payload["instance"];
        
        $user->token = JWT::encode($payload, JWT_KEY);

        return $this->reply($user);
    }
    
    public function register_v1()
    {
        $userData = $this->request->getJSON();
        // username or password missing
        if (
            !isset($userData->email) ||
            !isset($userData->password) ||
            !isset($userData->firstName) ||
            !isset($userData->lastName)
        ) {
            return $this->reply(null, 400, "ERR-USER-REGISTRATION-MISSING-DATA");
        }

        // password length under 6 chars
        if (strlen($userData->password) < 6) {
            return $this->reply(null, 400, "ERR-USER-REGISTRATION-PASSWORD-LENGTH");
        }

        $userModel = new UserModel();
        $users = $userModel->where('email', $userData->email)->findAll();

        // username already in use
        if (count($users)) {
            return $this->reply(null, 400, "ERR-USER-REGISTRATION-USER-EXISTS");
        }

        $options = [
            'cost' => 12
        ];
        
        helper('uuid');
        $userData->password = password_hash($userData->password, PASSWORD_DEFAULT, $options);
        $userData->id = uuid();
        $userData->system = 1;

        try {
            if ($userModel->insert($userData) === false) {
                return $this->reply($permissionModel->errors(), 500, "ERR-USER-REGISTRATION-SAVE");
            }
        } catch (\Exception $e) {
            return $this->reply($e->getMessage(), 500, "ERR-USER-REGISTRATION-SAVE");
        }
        

        return $this->reply($userData->id, 200, "OK-USER-REGISTRATION-SUCCESS");
    }
}