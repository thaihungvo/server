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
            return $this->reply(null, 401, "ERR_USER_LOGIN_WRONG_EMAIL_PASS");
        }

        if (!password_verify($userData->password, $user->password)){
            return $this->reply(null, 401, "ERR_USER_LOGIN_WRONG_EMAIL_PASS");
        }

        $key = JWT_KEY;
        $payload = array(
            'exp' => time() + (JWT_EXPIRATION_SPAN * 24 * 60 * 60),
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name
        );

        $jwt = JWT::encode($payload, $key);

        return $this->reply($jwt);
    }
    
    public function register_v1()
    {
        $userData = $this->request->getJSON();

        // username or password missing
        if (
            !isset($userData->email) ||
            !isset($userData->password)
        ) {
            return $this->reply(null, 400, "ERR_USER_REGISTRATION_MISSING_DATA");
        }

        // password length under 6 chars
        if (strlen($userData->password) < 6) {
            return $this->reply(null, 400, "ERR_USER_REGISTRATION_PASSWORD_LENGTH");
        }

        $userModel = new UserModel();
        $users = $userModel->where('email', $userData->email)->findAll();

        // username already in use
        if (count($users)) {
            return $this->reply(null, 400, "ERR_USER_REGISTRATION_USER_EXISTS");
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
            return $this->reply(null, 500, "ERR_USER_REGISTRATION_SAVE");
        }

        return $this->reply(null, 200, "OK_USER_REGISTRATION_SUCCESS");
    }
}