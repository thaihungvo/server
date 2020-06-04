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

        $key = JWT_KEY;
        $payload = array(
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        );

        unset($user->password);
        
        $user->token = JWT::encode($payload, $key);

        return $this->reply($user);
    }
    
    public function register_v1()
    {
        $userData = $this->request->getJSON();

        // username or password missing
        if (
            !isset($userData->email) ||
            !isset($userData->password)
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
            'salt' => uniqid(mt_rand(), true),
            'cost' => 12
        ];
        $hash = password_hash($userData->password, PASSWORD_DEFAULT, $options);

        $data = [
            'email'    => $userData->email,
            'password' => $hash
        ];
        
        if (!$userModel->insert($data)) {
            return $this->reply(null, 500, "ERR-USER-REGISTRATION-SAVE");
        }

        return $this->reply(null, 200, "OK-USER-REGISTRATION-SUCCESS");
    }
}