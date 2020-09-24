<?php namespace App\Controllers;

use App\Models\UserModel;

class MembersController extends BaseController
{
	public function all_v1()
	{
        $userModel = new UserModel();

        $users = $userModel->findAll();

        foreach ($users as &$user) {
            unset($user->password);
            unset($user->created);
            unset($user->updated);

            $user->initials = ucfirst(substr($user->firstName, 0, 1).substr($user->lastName, 0, 1));
        }

        return $this->reply($users);
    }
}